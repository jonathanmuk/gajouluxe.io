<?php
session_start();
require_once 'db_connection.php';
header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetchColumn();
}

echo json_encode(['count' => $count]);
