<?php
// Example database configuration template
// Copy this file to config/database.php and set your local or production database credentials

$host     = 'localhost';
$db_name  = 'car_workshop_db';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log technical error details securely on the server
    error_log("Database connection error: " . $e->getMessage());

    // Show a clean and safe message to visitors without exposing credentials
    die("Database connection failed. Please ensure the MySQL service is running and the database exists.");
}

// Helper function to return the PDO instance
function getDBConnection() {
    global $pdo;
    return $pdo;
}
