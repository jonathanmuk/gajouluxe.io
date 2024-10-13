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

        $stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        if ($stmt->rowCount() > 0) {
            // Remove from wishlist
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $response['action'] = 'removed';
        } else {
            // Add to wishlist
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $product_id]);
            $response['action'] = 'added';
        }

        // Get the updated wishlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['wishlistCount'] = $stmt->fetchColumn();

        // Get the updated list of wishlist items
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['wishlistItems'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Get the cart count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['cartCount'] = $stmt->fetchColumn();

        // Get the cart items
        $stmt = $pdo->prepare("SELECT product_id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['cartItems'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        // Non-logged-in user
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }

        if (in_array($product_id, $_SESSION['wishlist'])) {
            $key = array_search($product_id, $_SESSION['wishlist']);
            unset($_SESSION['wishlist'][$key]);
            $response['action'] = 'removed';
        } else {
            $_SESSION['wishlist'][] = $product_id;
            $response['action'] = 'added';
        }

        $response['wishlistCount'] = count($_SESSION['wishlist']);
        $response['wishlistItems'] = array_values($_SESSION['wishlist']);

        // For non-logged-in users, use session cart if it exists
        $response['cartCount'] = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
        $response['cartItems'] = isset($_SESSION['cart']) ? array_values($_SESSION['cart']) : [];
    }

    $response['success'] = true;
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

echo json_encode($response);
