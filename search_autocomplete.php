<?php
include 'db_connection.php';

$query = $_GET['query'];

$stmt = $pdo->prepare("SELECT name FROM products WHERE name LIKE ? LIMIT 5");
$stmt->execute(["%$query%"]);
$results = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($results);