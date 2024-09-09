<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = $_POST['product_id'];
$quantity = $_POST['quantity'];

$stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
$result = $stmt->execute([$quantity, $userId, $productId]);

echo json_encode(['success' => $result]);