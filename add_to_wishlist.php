<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = $_POST['product_id'];
$color = $_POST['color'] ?? null;
$size = $_POST['size'] ?? null;

try {
    // Check if the product is already in the wishlist with the same color and size
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
    $stmt->execute([$userId, $productId, $color, $size]);
    $existingItem = $stmt->fetch();

    if (!$existingItem) {
        // Add new item to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, color, size) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$userId, $productId, $color, $size]);

        if ($result) {
            // Fetch the product details
            $stmt = $pdo->prepare("
                SELECT p.*, w.id as wishlist_id, w.color, w.size, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p
                LEFT JOIN reviews r ON p.id = r.product_id
                LEFT JOIN wishlist w ON p.id = w.product_id AND w.user_id = ?
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$userId, $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // Format the product data
            $product['price'] = number_format($product['price'], 2);
            $product['avg_rating'] = round($product['avg_rating'], 1);

            // Fetch related products
            $stmt = $pdo->prepare("
                SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.category_id = ? AND p.id != ? AND p.id NOT IN (SELECT product_id FROM wishlist WHERE user_id = ?)
                GROUP BY p.id
                ORDER BY RAND()
                LIMIT 4
            ");
            $stmt->execute([$product['category_id'], $productId, $userId]);
            $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true, 
                'message' => 'Item added to wishlist',
                'product' => $product,
                'relatedProducts' => $relatedProducts
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add item to wishlist']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Item already in wishlist']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
