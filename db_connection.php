<?php
// Database configuration
$host = 'localhost';     // Usually 'localhost' if the database is on the same server
$dbname = 'gajoue';
$username = 'root';
$password = '';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// Options for PDO connection
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Set default fetch mode to associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                    // Use real prepared statements
];

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If there's an error in the connection, stop the script and display the error.
    die("Connection failed: " . $e->getMessage());
}