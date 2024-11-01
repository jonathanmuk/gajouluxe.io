<?php
session_start();
error_reporting(E_ERROR);
ini_set('display_errors', 0);

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

    try {
        // Get cart count and items
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, GROUP_CONCAT(product_id) as items FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cartResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['cartCount'] = (int)$cartResult['count'];
        $response['cartItems'] = $cartResult['items'] ? explode(',', $cartResult['items']) : [];

        // Get wishlist count and items
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, GROUP_CONCAT(product_id) as items FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wishlistResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['wishlistCount'] = (int)$wishlistResult['count'];
        $response['wishlistItems'] = $wishlistResult['items'] ? explode(',', $wishlistResult['items']) : [];
    } catch (PDOException $e) {
        $response['success'] = false;
        $response['error'] = 'Database error';
    }
} else {
    // For non-logged-in users, use session data
    $response['cartCount'] = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    $response['wishlistCount'] = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
    $response['cartItems'] = isset($_SESSION['cart']) ? array_keys($_SESSION['cart']) : [];
    $response['wishlistItems'] = isset($_SESSION['wishlist']) ? array_values($_SESSION['wishlist']) : [];
}

echo json_encode($response);
exit;
