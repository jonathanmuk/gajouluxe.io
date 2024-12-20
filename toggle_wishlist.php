<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your wishlist.']);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        // Remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $action = 'removed';
    } else {
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        $action = 'added';
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'action' => $action]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Wishlist toggle error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
