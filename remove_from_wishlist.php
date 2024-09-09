<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$wishlistId = $_POST['wishlist_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$wishlistId, $userId]);

    if ($result) {
        // Check if any rows were actually deleted
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Item removed from wishlist']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in wishlist']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item from wishlist']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
