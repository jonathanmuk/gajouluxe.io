<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => true,
    'cartCount' => 0,
    'wishlistCount' => 0,
    'cartItems' => [],
    'wishlistItems' => []
];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Get cart count and items
    $stmt = $pdo->prepare("SELECT COUNT(*), GROUP_CONCAT(product_id) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cartResult = $stmt->fetch(PDO::FETCH_NUM);
    $response['cartCount'] = $cartResult[0];
    $response['cartItems'] = $cartResult[1] ? explode(',', $cartResult[1]) : [];

    // Get wishlist count and items
    $stmt = $pdo->prepare("SELECT COUNT(*), GROUP_CONCAT(product_id) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wishlistResult = $stmt->fetch(PDO::FETCH_NUM);
    $response['wishlistCount'] = $wishlistResult[0];
    $response['wishlistItems'] = $wishlistResult[1] ? explode(',', $wishlistResult[1]) : [];
} else {
    // For non-logged-in users, use session data
    $response['cartCount'] = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    $response['wishlistCount'] = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
    $response['cartItems'] = isset($_SESSION['cart']) ? array_values($_SESSION['cart']) : [];
    $response['wishlistItems'] = isset($_SESSION['wishlist']) ? array_values($_SESSION['wishlist']) : [];
}

echo json_encode($response);
