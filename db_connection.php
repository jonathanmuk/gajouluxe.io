<?php
// AWS RDS Database configuration
$host = 'gajouluxe.cnaio0yay63k.eu-north-1.rds.amazonaws.com';  // Your RDS endpoint
$dbname = 'gajouluxe';    // Your database name
$username = 'admin';     // Your RDS username
$password = '12345678';  // Your RDS password

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// Options for PDO connection
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
