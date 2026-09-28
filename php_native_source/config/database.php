<?php
/**
 * Database Configuration
 * ICOEP - Integrated Campus Operations and Engagement Portal
 * 
 * PDO connection with security-first defaults.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'icoep_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection (singleton pattern)
 */
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Use real prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error but don't expose details to user
            error_log("Database connection failed: " . $e->getMessage());
            die("A database error occurred. Please contact the system administrator.");
        }
    }
    
    return $pdo;
}
