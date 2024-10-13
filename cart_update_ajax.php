<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'action' => '', 'cartCount' => 0, 'wishlistCount' => 0, 'cartItems' => [], 'wishlistItems' => []];

if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    $response['message'] = 'Product ID is missing';
    echo json_encode($response);
    exit;
}

$product_id = $_POST['product_id'];

if (!filter_var($product_id, FILTER_VALIDATE_INT) || $product_id <= 0) {
    $response['message'] = 'Invalid Product ID';
    echo json_encode($response);
    exit;
}

try {
    // Check if the product exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_exists = $stmt->fetchColumn() > 0;

    if (!$product_exists) {
        $response['message'] = 'Product does not exist';
        echo json_encode($response);
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        // Logged-in user
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch();

        if ($existing_item) {
            // Remove item from cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $response['action'] = 'removed';
        } else {
            // Add item to cart
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$user_id, $product_id]);
            $response['action'] = 'added';
        }

        // Get the updated cart count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['cartCount'] = $stmt->fetchColumn();

        // Get the updated list of cart items
        $stmt = $pdo->prepare("SELECT product_id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['cartItems'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Get the wishlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['wishlistCount'] = $stmt->fetchColumn();

        // Get the wishlist items
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['wishlistItems'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        // Non-logged-in user
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (in_array($product_id, $_SESSION['cart'])) {
            $key = array_search($product_id, $_SESSION['cart']);
            unset($_SESSION['cart'][$key]);
            $response['action'] = 'removed';
        } else {
            $_SESSION['cart'][] = $product_id;
            $response['action'] = 'added';
        }

        $response['cartCount'] = count($_SESSION['cart']);
        $response['cartItems'] = array_values($_SESSION['cart']);

        // For non-logged-in users, use session wishlist if it exists
        $response['wishlistCount'] = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
        $response['wishlistItems'] = isset($_SESSION['wishlist']) ? array_values($_SESSION['wishlist']) : [];
    }

    $response['success'] = true;
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

echo json_encode($response);
