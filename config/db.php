<?php
/**
 * Database Configuration and Connection
 * Using PDO for secure prepared statements.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change if necessary
define('DB_PASS', '');     // Change if necessary
define('DB_NAME', 'mail_sender_db');

try {
    // Create PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // Hide details in production, keeping for dev ease
    die("Database connection failed: " . $e->getMessage());
}
?>
