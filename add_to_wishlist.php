<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'product' => null, 'relatedProducts' => []];

if (isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    $color = $_POST['color'] ?? null;
    $size = $_POST['size'] ?? null;

    if (isset($_SESSION['user_id'])) {
        // For logged-in users
        $userId = $_SESSION['user_id'];
        try {
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
            $stmt->execute([$userId, $productId, $color, $size]);
            $existingItem = $stmt->fetch();

            if (!$existingItem) {
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, color, size) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$userId, $productId, $color, $size]);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Item added to wishlist';
                } else {
                    $response['message'] = 'Failed to add item to wishlist';
                }
            } else {
                $response['message'] = 'Item already in wishlist';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        // For non-logged-in users
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }
        if (!in_array($productId, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'][] = $productId;
            $response['success'] = true;
            $response['message'] = 'Item added to wishlist';
        } else {
            $response['message'] = 'Item already in wishlist';
        }
    }

    if ($response['success']) {
        // Fetch product details and related products
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $product['price'] = number_format($product['price']);
                $product['avg_rating'] = round($product['avg_rating'], 1);
                $response['product'] = $product;

                $stmt = $pdo->prepare("
                    SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                    FROM products p
                    LEFT JOIN reviews r ON p.id = r.product_id
                    WHERE p.category_id = ? AND p.id != ?
                    GROUP BY p.id
                    ORDER BY RAND()
                    LIMIT 4
                ");
                $stmt->execute([$product['category_id'], $productId]);
                $response['relatedProducts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $response['message'] .= ' Error fetching product details: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);