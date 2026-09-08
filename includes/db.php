<?php
/**
 * Database Connection (PDO Singleton)
 * Acıbadem International Offices App
 */

if (!defined('APP_INSTALLED') || !APP_INSTALLED) {
    header('Location: install.php');
    exit;
}

class Database {
    private static ?PDO $instance = null;
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                die('Database connection error. Please check your configuration.');
            }
        }
        return self::$instance;
    }
    
    // Prevent cloning and unserialization
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() { throw new \Exception('Cannot unserialize singleton'); }
}

function getDb(): PDO {
    return Database::getInstance();
}
