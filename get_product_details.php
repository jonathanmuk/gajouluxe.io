<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

// Log all incoming data
error_log('GET data: ' . print_r($_GET, true));
error_log('POST data: ' . print_r($_POST, true));
error_log('REQUEST data: ' . print_r($_REQUEST, true));

header('Content-Type: application/json');

// Check for product_id in GET, POST, and REQUEST
$productId = $_GET['product_id'] ?? $_POST['product_id'] ?? $_REQUEST['product_id'] ?? null;

error_log('Extracted Product ID: ' . ($productId ?? 'null'));

if (!$productId) {
    echo json_encode(['error' => 'Product ID not provided']);
    exit;
}

try {
    // Fetch product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    // Fetch product images
    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch product colors
    $stmt = $pdo->prepare("SELECT DISTINCT color FROM product_variants WHERE product_id = ?");
    $stmt->execute([$productId]);
    $colors = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch product sizes
    $stmt = $pdo->prepare("SELECT DISTINCT size FROM product_variants WHERE product_id = ?");
    $stmt->execute([$productId]);
    $sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Prepare the response
    $response = [
        'id' => $product['id'],
        'name' => $product['name'],
        'description' => $product['description'],
        'price' => $product['price'],
        'images' => $images,
        'colors' => $colors,
        'sizes' => $sizes
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    echo json_encode(['error' => 'An unexpected error occurred']);
}