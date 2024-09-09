<?php
session_start();
include 'check_admin.php';
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['slug'])) {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    
    if (empty($name) || empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and slug cannot be empty']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
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
