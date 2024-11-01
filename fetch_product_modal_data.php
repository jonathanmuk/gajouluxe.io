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
            // Fetch colors
            $stmt = $pdo->prepare("
                SELECT pc.id AS color_id, pc.color_name, pc.color_code,
                       GROUP_CONCAT(DISTINCT ps.size ORDER BY ps.size) AS sizes
                FROM product_colors pc
                LEFT JOIN product_sizes ps ON pc.product_id = ps.product_id AND pc.id = ps.color_id
                WHERE pc.product_id = ?
                GROUP BY pc.id, pc.color_name, pc.color_code
            ");
            $stmt->execute([$product_id]);
            $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch textures
            $stmt = $pdo->prepare("
                SELECT pt.id AS texture_id, pt.texture_name, pt.texture_sample_path,
                       GROUP_CONCAT(DISTINCT ps.size ORDER BY ps.size) AS sizes
                FROM product_textures pt
                LEFT JOIN product_sizes ps ON pt.product_id = ps.product_id AND pt.id = ps.texture_id
                WHERE pt.product_id = ?
                GROUP BY pt.id, pt.texture_name, pt.texture_sample_path
            ");
            $stmt->execute([$product_id]);
            $textures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch images for colors
            $stmt = $pdo->prepare("
                SELECT pi.image_path, pc.id AS color_id, pc.color_name
                FROM product_images pi
                JOIN product_colors pc ON pi.color_id = pc.id
                WHERE pi.product_id = ?
            ");
            $stmt->execute([$product_id]);
            $colorImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch images for textures
            $stmt = $pdo->prepare("
                SELECT pi.image_path, pt.id AS texture_id, pt.texture_name
                FROM product_images pi
                JOIN product_textures pt ON pi.texture_id = pt.id
                WHERE pi.product_id = ?
            ");
            $stmt->execute([$product_id]);
            $textureImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize the data
            $product['variants'] = [];

            // Add color variants
            foreach ($colors as $color) {
                $color_id = $color['color_id'];
                $variant_images = array_filter($colorImages, function($img) use ($color_id) {
                    return $img['color_id'] == $color_id;
                });

                $product['variants'][] = [
                    'color_id' => $color_id,
                    'color_name' => $color['color_name'],
                    'color_code' => $color['color_code'],
                    'texture_sample_path' => null,
                    'sizes' => explode(',', $color['sizes']),
                    'images' => array_values($variant_images)
                ];
            }

            // Add texture variants
            foreach ($textures as $texture) {
                $texture_id = $texture['texture_id'];
                $variant_images = array_filter($textureImages, function($img) use ($texture_id) {
                    return $img['texture_id'] == $texture_id;
                });

                $product['variants'][] = [
                    'color_id' => $texture_id, // Using texture_id as color_id for consistency
                    'color_name' => $texture['texture_name'],
                    'color_code' => null,
                    'texture_sample_path' => $texture['texture_sample_path'],
                    'sizes' => explode(',', $texture['sizes']),
                    'images' => array_values($variant_images)
                ];
            }

            // Format prices and add other necessary data
            $product['formatted_price'] = 'Shs.' . number_format($product['price'], 0);
            if ($product['is_sale']) {
                $product['formatted_sale_price'] = 'Shs.' . number_format($product['sale_price'], 0);
            }

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
