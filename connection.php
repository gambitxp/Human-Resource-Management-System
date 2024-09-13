<?php
// connection.php

// Database credentials
$host = 'localhost';
$dbname = 'user_auth';
$username = 'root';
$password = '';

try {
    // Create a new PDO instance and set error mode to exception
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Uncomment the following line for debugging (optional)
    // echo "Connected successfully";
} catch (PDOException $e) {
    // Display error message if connection fails
    die("Database connection failed: " . $e->getMessage());
}
?>