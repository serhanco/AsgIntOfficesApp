<?php
/**
 * Admin Authentication Helper
 * Acıbadem International Offices App
 */

// We need DB access
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';

/** True when the visitor reached us over HTTPS (also behind Cloudflare / a proxy that terminates TLS). */
function request_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    return strpos($_SERVER['HTTP_CF_VISITOR'] ?? '', '"scheme":"https"') !== false;
}

// Harden the session cookie before starting the session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Admin pages must never be cached, framed, indexed or leak their address
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Frame-Options: DENY');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

// Sessions end after 2 hours without activity, or 12 hours after login
const ADMIN_IDLE_SEC     = 7200;
const ADMIN_ABSOLUTE_SEC = 43200;
if (isset($_SESSION['admin_user_id'])) {
    $now = time();
    if ($now - ($_SESSION['admin_last_seen'] ?? $now) > ADMIN_IDLE_SEC || $now - ($_SESSION['admin_login_at'] ?? $now) > ADMIN_ABSOLUTE_SEC) {
        $_SESSION = [];
        session_destroy();
        session_start();
    } else {
        $_SESSION['admin_last_seen'] = $now;
    }
}

// Login throttling: max failed attempts per IP within the window
const ADMIN_LOGIN_MAX_ATTEMPTS = 5;
const ADMIN_LOGIN_WINDOW_SEC   = 900; // 15 minutes

/**
 * CSRF token for the current session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden input to drop into every admin POST form
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Reject any POST request that does not carry a valid CSRF token.
 * Runs automatically for every admin page that includes this file.
 */
function verify_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        die('Geçersiz güvenlik anahtarı. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}
verify_csrf();

/** Is $ip inside the CIDR range? Works for IPv4 and IPv6. */
function ip_in_cidr(string $ip, string $cidr): bool {
    [$net, $bits] = explode('/', $cidr) + [1 => null];
    $ipBin = @inet_pton($ip);
    $netBin = @inet_pton($net);
    if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) return false;
    $bits = (int)$bits;
    $bytes = intdiv($bits, 8);
    if ($bytes && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) return false;
    $rest = $bits % 8;
    if ($rest === 0) return true;
    $mask = (0xFF << (8 - $rest)) & 0xFF;
    return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
}

/**
 * The visitor's real IP. Behind Cloudflare every request arrives from a Cloudflare address,
 * so CF-Connecting-IP is trusted only when the connection really comes from Cloudflare.
 */
function client_ip(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
    if ($cf === '' || filter_var($cf, FILTER_VALIDATE_IP) === false) return $remote;
    $cloudflare = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];
    foreach ($cloudflare as $range) if (ip_in_cidr($remote, $range)) return $cf;
    return $remote;
}

// Failed logins are counted per IP and per username (so a spread-out attack on one account is also slowed)
const ADMIN_LOGIN_USER_MAX_ATTEMPTS = 10;

function login_attempts_file(string $who = ''): string {
    $key = $who === '' ? 'ip:' . client_ip() : 'user:' . strtolower($who);
    return sys_get_temp_dir() . '/asg_admin_login_' . hash('sha256', $key . (defined('APP_SECRET') ? APP_SECRET : ''));
}

function recent_login_failures(string $who = ''): array {
    $file = login_attempts_file($who);
    if (!is_file($file)) return [];
    $times = json_decode((string)@file_get_contents($file), true);
    if (!is_array($times)) return [];
    $cutoff = time() - ADMIN_LOGIN_WINDOW_SEC;
    return array_values(array_filter($times, fn($t) => is_int($t) && $t > $cutoff));
}

function is_login_locked(string $username = ''): bool {
    if (count(recent_login_failures()) >= ADMIN_LOGIN_MAX_ATTEMPTS) return true;
    return $username !== '' && count(recent_login_failures($username)) >= ADMIN_LOGIN_USER_MAX_ATTEMPTS;
}

function record_login_failure(string $username = ''): void {
    foreach ($username === '' ? [''] : ['', $username] as $who) {
        $times = recent_login_failures($who);
        $times[] = time();
        @file_put_contents(login_attempts_file($who), json_encode($times), LOCK_EX);
    }
}

function clear_login_failures(string $username = ''): void {
    @unlink(login_attempts_file());
    if ($username !== '') @unlink(login_attempts_file($username));
}

function admin_login(string $username, string $password): bool {
    $db = getDb();
    $stmt = $db->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Always run one hash check, so a wrong username takes as long as a wrong password
    $hash = $user['password_hash'] ?? '$2y$10$iOQBHnwTPqHTtwLJ61A03e17yMvbw3h7ve6RwEkcmpfNNxV5Y6As.';
    $ok = password_verify($password, $hash) && $user;

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_at'] = time();
        $_SESSION['admin_last_seen'] = time();
        clear_login_failures($username);
        return true;
    }

    record_login_failure($username);
    return false;
}

function admin_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $c['path'], 'secure' => $c['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_user_id']);
}

function require_admin_login(): void {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
