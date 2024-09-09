<?php
session_start();
include 'check_admin.php';
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['slug']) && isset($_POST['category_id'])) {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $categoryId = $_POST['category_id'];
    
    if (empty($name) || empty($slug) || !is_numeric($categoryId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO subcategories (name, slug, category_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $categoryId]);
        $id = $pdo->lastInsertId();

        echo json_encode(['id' => $id, 'name' => $name, 'slug' => $slug]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
