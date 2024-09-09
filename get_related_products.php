<?php
include 'db_connection.php';

$productId = $_GET['product_id'];

$stmt = $pdo->prepare("SELECT subcategory_id FROM products WHERE id = ?");
$stmt->execute([$productId]);
$subcategoryId = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM products WHERE subcategory_id = ? AND id != ? ORDER BY RAND() LIMIT 4");
$stmt->execute([$subcategoryId, $productId]);
$relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($relatedProducts as $product) {
    echo "
    <div class='col-md-3 mb-4'>
        <div class='card product-card'>
            <div class='product-image' style='background-image: url({$product['image_url']});'>
                <button class='wishlist-btn' data-product-id='{$product['id']}'><i class='far fa-heart'></i></button>
                <button class='quick-view-btn' data-product-id='{$product['id']}'>Quick View</button>
            </div>
            <div class='card-body'>
                <h5 class='card-title'>{$product['name']}</h5>
                <p class='card-text'>$" . number_format($product['price'], 2) . "</p>
                <button class='btn btn-primary btn-sm add-to-cart-btn' data-product-id='{$product['id']}'>Add to Cart</button>
            </div>
        </div>
    </div>";
}



