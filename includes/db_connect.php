<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'children_edu');
define('DB_USER', 'root');   
define('DB_PASS', '');       
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 * 
 * @return PDO Database connection object
 */
function getDbConnection() {
    static $pdo;
    
    if (!$pdo) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
           
            error_log("Connection failed: " . $e->getMessage());
            die("Sorry, there was a problem connecting to the database.");
        }
    }
    
    return $pdo;
} 