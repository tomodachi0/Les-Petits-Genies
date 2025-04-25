<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Test database connection
echo "Testing database connection...<br>";
if ($db) {
    echo "Database connection successful!<br><br>";
    
    // Check if users table exists
    $query = "SHOW TABLES LIKE 'users'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Users table exists.<br>";
        
        // Check admin user
        $query = "SELECT * FROM users WHERE username = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Admin user found:<br>";
            echo "Username: " . $user['username'] . "<br>";
            echo "Email: " . $user['email'] . "<br>";
            echo "Password hash: " . $user['password'] . "<br>";
        } else {
            echo "Admin user not found in database.<br>";
        }
    } else {
        echo "Users table does not exist.<br>";
    }
} else {
    echo "Database connection failed!<br>";
    echo "Please check your database configuration in config/database.php<br>";
}
?> 