<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($categoryId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['subcategories' => $subcategories]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid category ID']);
}
