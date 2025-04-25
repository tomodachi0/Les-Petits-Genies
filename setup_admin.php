<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Create admin user with proper password hash
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// First, try to delete existing admin user if exists
$query = "DELETE FROM users WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();

// Insert new admin user
$query = "INSERT INTO users (username, password, email) VALUES (:username, :password, :email)";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':password', $hashed_password);
$stmt->bindParam(':email', $email);

if ($stmt->execute()) {
    echo "Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
} else {
    echo "Error creating admin user.";
}
?> 