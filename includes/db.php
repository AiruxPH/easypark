<?php
// Use environment variables for sensitive credentials
$host = getenv('DB_HOST') ?: '194.59.164.68';
$db   = getenv('DB_NAME') ?: 'u130348899_easypark_db2';
$user = getenv('DB_USER') ?: 'u130348899_randythegreat2';
$pass = getenv('DB_PASS') ?: 'RandyBOY999999@';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $user, $pass);

    // Enable exceptions for errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    // Disable error display in production
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);

    // Log the error internally
    error_log("Database connection failed: " . $e->getMessage());

    // Display a generic error message to the user
    die("❌ A database error occurred. Please try again later.");
}
?>