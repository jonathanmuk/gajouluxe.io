<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

function getCartItemCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

if (isset($_SESSION['user_id'])) {
    $count = getCartItemCount($pdo, $_SESSION['user_id']);
} else {
    $count = 0;
}

echo json_encode(['count' => $count]);
