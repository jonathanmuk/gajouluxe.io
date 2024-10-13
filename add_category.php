<?php
session_start();
include 'check_admin.php';
include 'db_connection.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['slug']) && isset($_FILES['category_image'])) {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    
    if (empty($name) || empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and slug cannot be empty']);
        exit;
    }

    // Handle image upload
    if ($_FILES['category_image']['error'] == 0) {
        $uploadDir = 'uploads/categories/';
        
        // Check if the directory exists, if not, create it
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                echo json_encode(['error' => 'Failed to create upload directory']);
                exit;
            }
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['category_image']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['category_image']['tmp_name'], $targetPath)) {
            $image_path = $targetPath;
        } else {
            echo json_encode(['error' => 'Failed to upload image: ' . error_get_last()['message']]);
            exit;
        }
    } else {
        echo json_encode(['error' => 'No image uploaded or upload error: ' . $_FILES['category_image']['error']]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $image_path]);
        $id = $pdo->lastInsertId();

        echo json_encode(['id' => $id, 'name' => $name, 'slug' => $slug, 'image_url' => $image_path]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request: ' . json_encode($_POST) . ', Files: ' . json_encode($_FILES)]);
}
