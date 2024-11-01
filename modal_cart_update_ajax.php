<?php
session_start();
include 'db_connection.php';

error_log("Received POST data: " . print_r($_POST, true));

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'action' => '', 'cartCount' => 0, 'cartItems' => []];

// Check if product_id is set and not empty
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    error_log("Missing product_id: " . print_r($_POST, true));
    $response['message'] = 'Missing product ID';
    echo json_encode($response);
    exit;
}

// Check other required parameters
if (!isset($_POST['color_id']) || empty($_POST['color_id']) || 
    !isset($_POST['size']) || empty($_POST['size']) || 
    !isset($_POST['quantity'])) {
    error_log("Missing parameters: " . print_r($_POST, true));
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$product_id = $_POST['product_id'];
$color_id = $_POST['color_id'];
$size = $_POST['size'];
$quantity = intval($_POST['quantity']);

// Validate product_id and color_id
if (!filter_var($product_id, FILTER_VALIDATE_INT) || $product_id <= 0 ||
    !filter_var($color_id, FILTER_VALIDATE_INT) || $color_id <= 0) {
    $response['message'] = 'Invalid parameters';
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

        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND color_id = ? AND size = ?");
        $stmt->execute([$user_id, $product_id, $color_id, $size]);
        $existing_item = $stmt->fetch();

        if ($existing_item) {
            $new_quantity = $existing_item['quantity'] + $quantity;
            if ($new_quantity <= 0) {
                // Remove item from cart
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND color_id = ? AND size = ?");
                $stmt->execute([$user_id, $product_id, $color_id, $size]);
                $response['action'] = 'removed';
            } else {
                // Update quantity
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND color_id = ? AND size = ?");
                $stmt->execute([$new_quantity, $user_id, $product_id, $color_id, $size]);
                $response['action'] = 'updated';
            }
        } else {
            // Add item to cart
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, color_id, size, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $color_id, $size, $quantity]);
            $response['action'] = 'added';
        }

        // Get the updated cart count and items
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['cartCount'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT product_id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $response['cartItems'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Non-logged-in user
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $item_index = false;
        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['product_id'] == $product_id && $item['color_id'] == $color_id && $item['size'] == $size) {
                $item_index = $index;
                break;
            }
        }

        if ($item_index !== false) {
            $_SESSION['cart'][$item_index]['quantity'] += $quantity;
            if ($_SESSION['cart'][$item_index]['quantity'] <= 0) {
                unset($_SESSION['cart'][$item_index]);
                $response['action'] = 'removed';
            } else {
                $response['action'] = 'updated';
            }
        } else {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'color_id' => $color_id,
                'size' => $size,
                'quantity' => $quantity
            ];
            $response['action'] = 'added';
        }

        $response['cartCount'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
        $response['cartItems'] = array_unique(array_column($_SESSION['cart'], 'product_id'));
    }

    $response['success'] = true;
    $response['message'] = $response['action'] === 'removed' ? 'Item removed from cart' : 'Item added to cart successfully';
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

echo json_encode($response);
