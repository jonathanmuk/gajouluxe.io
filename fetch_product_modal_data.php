<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    try {
        // Fetch basic product information
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Fetch variants (colors, sizes, and stock)
            $stmt = $pdo->prepare("
                SELECT pv.*, pc.color_name, pc.color_code
                FROM product_variants pv
                JOIN product_colors pc ON pv.color_id = pc.id
                WHERE pv.product_id = ?
            ");
            $stmt->execute([$product_id]);
            $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch images for each variant
            $stmt = $pdo->prepare("
                SELECT pi.image_path, pc.color_name
                FROM product_images pi
                JOIN product_colors pc ON pi.color_id = pc.id
                WHERE pi.product_id = ?
            ");
            $stmt->execute([$product_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch reviews
            $stmt = $pdo->prepare("
                SELECT r.*, u.username
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ?
            ");
            $stmt->execute([$product_id]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize the data
            $product['variants'] = [];
            $product['stock'] = [];
            foreach ($variants as $variant) {
                $color = $variant['color_name'];
                if (!isset($product['variants'][$color])) {
                    $product['variants'][$color] = [
                        'color_name' => $color,
                        'color_code' => $variant['color_code'],
                        'sizes' => [],
                        'images' => array_filter($images, function($img) use ($color) {
                            return $img['color_name'] === $color;
                        })
                    ];
                }
                $product['variants'][$color]['sizes'][] = $variant['size'];
                $product['stock'][$color][$variant['size']] = $variant['stock_quantity'];
            }
            $product['variants'] = array_values($product['variants']);

            $product['reviews'] = $reviews;

            echo json_encode($product);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No product ID provided']);
}
