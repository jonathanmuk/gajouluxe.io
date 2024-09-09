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
$color = $_POST['color'] ?? null;
$size = $_POST['size'] ?? null;




// Check if the product is already in the cart with the same color and size
$stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
$stmt->execute([$userId, $productId, $color, $size]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Update quantity if the product is already in the cart
    $newQuantity = $existingItem['quantity'] + 1;
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
    $result = $stmt->execute([$newQuantity, $userId, $productId, $color, $size]);
} else {
    // Add new item to cart
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, color, size) VALUES (?, ?, 1, ?, ?)");
    $result = $stmt->execute([$userId, $productId, $color, $size]);
}

echo json_encode(['success' => $result]);