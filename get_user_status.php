<?php
session_start();
include 'db_connection.php';

$response = [
    'loggedIn' => false,
    'username' => '',
    'cartItems' => 0
];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Get username
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get cart items count
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartCount = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['loggedIn'] = true;
    $response['username'] = $user['username'];
    $response['cartItems'] = $cartCount['total'] ?? 0;
}

header('Content-Type: application/json');
echo json_encode($response);
