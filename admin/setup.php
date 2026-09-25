<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDb();
    
    // Create table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Check if user exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $insert->execute(['admin', $hash]);
        echo "Admin user created successfully! Username: admin, Password: admin123<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    
    echo "Setup complete. <a href='login.php'>Go to Login</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
