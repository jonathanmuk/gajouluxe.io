<?php
include 'db_connection.php';

header('Content-Type: application/json');

$productId = $_GET['product_id'] ?? null;
$colorId = $_GET['color_id'] ?? null;

if (!$productId || !$colorId) {
    echo json_encode(['success' => false, 'message' => 'Missing product_id or color_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT vi.image_url
        FROM variant_images vi
        JOIN product_variants pv ON vi.variant_id = pv.id
        WHERE pv.product_id = ? AND pv.color_id = ?
    ");
    $stmt->execute([$productId, $colorId]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'images' => $images]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}