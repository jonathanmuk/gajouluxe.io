<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cartCount' => 0];

if (isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    $color = $_POST['color'] ?? null;
    $size = $_POST['size'] ?? null;

    if (isset($_SESSION['user_id'])) {
        // For logged-in users
        $userId = $_SESSION['user_id'];
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
            $stmt->execute([$userId, $productId, $color, $size]);
            $existingItem = $stmt->fetch();

            if ($existingItem) {
                $response['success'] = false;
                $response['message'] = 'Item already in cart';
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, color, size) VALUES (?, ?, 1, ?, ?)");
                $stmt->execute([$userId, $productId, $color, $size]);
                
                $response['success'] = true;
                $response['message'] = 'Item added to cart successfully';
            }

            $pdo->commit();

            // Get updated cart count
            $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $response['cartCount'] = $stmt->fetchColumn() ?: 0;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['message'] = 'An error occurred. Please try again.';
            error_log("Add to cart error: " . $e->getMessage());
        }
    } else {
        // For non-logged-in users
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $cartKey = $productId . '-' . $color . '-' . $size;
        if (isset($_SESSION['cart'][$cartKey])) {
            $response['success'] = false;
            $response['message'] = 'Item already in cart';
        } else {
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $productId,
                'color' => $color,
                'size' => $size,
                'quantity' => 1
            ];
            $response['success'] = true;
            $response['message'] = 'Item added to cart successfully';
        }
        $response['cartCount'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
} else {
    $response['message'] = 'Product ID is required';
}

echo json_encode($response);