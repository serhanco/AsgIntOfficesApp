<?php
/**
 * Admin Authentication Helper
 * Acıbadem International Offices App
 */

// We need DB access
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // client_ip()

/** True when the visitor reached us over HTTPS (also behind Cloudflare / a proxy that terminates TLS). */
function request_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    return strpos($_SERVER['HTTP_CF_VISITOR'] ?? '', '"scheme":"https"') !== false;
}

// Roles: 'admin' sees everything. 'contributor' (Katkıda bulunan) only enters events as drafts; an admin reviews and publishes them.
// Before patch_20261127_contributor_role.sql the role column does not exist and everyone is an admin, as before.
const ADMIN_ROLES = ['admin' => 'Yönetici', 'contributor' => 'Katkıda bulunan'];
// Pages a contributor may open (everything else sends them to the events list)
const CONTRIBUTOR_PAGES = ['events.php', 'event_edit.php', 'admins.php', 'index.php'];

function admin_role_of($row): string {
    $r = is_array($row) ? (string)($row['role'] ?? 'admin') : 'admin';
    return isset(ADMIN_ROLES[$r]) ? $r : 'admin';
}

function admin_roles_ready(): bool {
    static $ready = null;
    if ($ready === null) {
        try { $ready = (bool)getDb()->query("SHOW COLUMNS FROM admin_users LIKE 'role'")->fetchColumn(); } catch (\Throwable $e) { $ready = false; }
    }
    return $ready;
}

function is_contributor(): bool {
    return ($_SESSION['admin_role'] ?? 'admin') === 'contributor';
}

/** Full admin (not a contributor): may open every screen and publish */
function is_full_admin(): bool {
    return is_admin_logged_in() && !is_contributor();
}

/** Side menu items for the current user */
function admin_nav(): array {
    if (is_contributor()) return ['events.php' => 'Etkinlikler', 'admins.php' => 'Şifremi değiştir'];
    return ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'event_requests.php' => 'Talepler', 'settings.php' => 'Ayarlar', 'admins.php' => 'Yöneticiler', '../apply_update.php' => 'Güncellemeler'];
}

/** Side menu (desktop) with logout, for the pages a contributor can see */
function admin_sidebar(string $current): string {
    $out = '<aside class="w-64 shrink-0 bg-gray-900 text-white md:sticky md:top-0 md:h-screen flex-col hidden md:flex"><div class="h-16 shrink-0 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div><nav class="flex-1 min-h-0 overflow-y-auto px-4 py-6 space-y-2">';
    foreach (admin_nav() as $href => $label) {
        $out .= '<a href="' . $href . '" class="block px-4 py-2 rounded-md ' . ($href === $current ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white') . '">' . $label . '</a>';
    }
    $out .= '<a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Siteyi gör ↗</a></nav>';
    $out .= '<div class="p-4 shrink-0 border-t border-gray-800 text-xs text-gray-400"><div class="mb-2 truncate">' . htmlspecialchars((string)($_SESSION['admin_username'] ?? ''), ENT_QUOTES, 'UTF-8') . ' · ' . ADMIN_ROLES[is_contributor() ? 'contributor' : 'admin'] . '</div>';
    $out .= '<form method="POST" action="index.php">' . csrf_field() . '<input type="hidden" name="action" value="logout"><button type="submit" class="block w-full text-center px-4 py-2 border border-gray-600 rounded-md text-sm text-gray-300 hover:bg-gray-800 hover:text-white">Çıkış Yap</button></form></div></aside>';
    return $out;
}

/** Menu strip for phones */
function admin_mobile_nav(string $current): string {
    $out = '<nav class="md:hidden bg-gray-900 text-sm flex overflow-x-auto">';
    foreach (admin_nav() as $href => $label) {
        $out .= '<a href="' . $href . '" class="px-4 py-3 whitespace-nowrap ' . ($href === $current ? 'text-white bg-gray-800' : 'text-gray-300') . '">' . $label . '</a>';
    }
    $out .= '<form method="POST" action="index.php" class="ml-auto">' . csrf_field() . '<input type="hidden" name="action" value="logout"><button type="submit" class="px-4 py-3 text-gray-300 whitespace-nowrap">Çıkış</button></form></nav>';
    return $out;
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
        // The account must still exist and its password must be unchanged since this login
        // (so deleting an admin or resetting a password ends that person's open sessions)
        $st = getDb()->prepare('SELECT * FROM admin_users WHERE id = ?');
        $st->execute([(int)$_SESSION['admin_user_id']]);
        $me = $st->fetch();
        $hash = $me['password_hash'] ?? '';
        if (!$hash || !hash_equals((string)($_SESSION['admin_pw_fp'] ?? ''), hash('sha256', $hash))) {
            $_SESSION = [];
            session_destroy();
            session_start();
        } else {
            // Read on every request, so a role change on Yöneticiler takes effect at once
            $_SESSION['admin_role'] = admin_role_of($me);
        }
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
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Always run one hash check, so a wrong username takes as long as a wrong password
    $hash = $user['password_hash'] ?? '$2y$10$iOQBHnwTPqHTtwLJ61A03e17yMvbw3h7ve6RwEkcmpfNNxV5Y6As.';
    $ok = password_verify($password, $hash) && $user;

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = admin_role_of($user);
        $_SESSION['admin_pw_fp'] = hash('sha256', $user['password_hash']);
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
    // Contributors see only the event screens and their own password
    if (is_contributor() && !in_array(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')), CONTRIBUTOR_PAGES, true)) {
        header('Location: events.php');
        exit;
    }
}
