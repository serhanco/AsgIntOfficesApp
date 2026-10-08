<?php
/**
 * Admin Authentication Helper
 * Acıbadem International Offices App
 */

// We need DB access
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';

// Harden the session cookie before starting the session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
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

/**
 * File that stores failed login timestamps for the client IP
 */
function login_attempts_file(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return sys_get_temp_dir() . '/asg_admin_login_' . hash('sha256', $ip . (defined('APP_SECRET') ? APP_SECRET : ''));
}

function recent_login_failures(): array {
    $file = login_attempts_file();
    if (!is_file($file)) return [];
    $times = json_decode((string)@file_get_contents($file), true);
    if (!is_array($times)) return [];
    $cutoff = time() - ADMIN_LOGIN_WINDOW_SEC;
    return array_values(array_filter($times, fn($t) => is_int($t) && $t > $cutoff));
}

function is_login_locked(): bool {
    return count(recent_login_failures()) >= ADMIN_LOGIN_MAX_ATTEMPTS;
}

function record_login_failure(): void {
    $times = recent_login_failures();
    $times[] = time();
    @file_put_contents(login_attempts_file(), json_encode($times), LOCK_EX);
}

function clear_login_failures(): void {
    @unlink(login_attempts_file());
}

function admin_login(string $username, string $password): bool {
    $db = getDb();
    $stmt = $db->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $username;
        clear_login_failures();
        return true;
    }

    record_login_failure();
    return false;
}

function admin_logout(): void {
    unset($_SESSION['admin_user_id']);
    unset($_SESSION['admin_username']);
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
