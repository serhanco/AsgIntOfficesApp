<?php
/**
 * Admin Authentication Helper
 * Acıbadem International Offices App
 */

// We need DB access
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';

session_start();

function admin_login(string $username, string $password): bool {
    $db = getDb();
    $stmt = $db->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $username;
        return true;
    }
    
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
