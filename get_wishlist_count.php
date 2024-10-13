<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['count' => $result['count'] ?? 0]);
} catch (PDOException $e) {
    error_log("Wishlist count error: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while fetching the wishlist count.', 'count' => 0]);
}
