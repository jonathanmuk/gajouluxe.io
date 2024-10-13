<?php
include 'db_connection.php';

$id = $_GET['id'];
$sql = "SELECT * FROM products WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);

if ($product = $stmt->fetch()) {
    echo "<div class='product-item' data-aos='zoom-in'>";
    echo "<div class='product-image' style='background-image: url(\"{$product['image_url']}\");'></div>";
    echo "<div class='product-info'>";
    echo "<h5>{$product['name']}</h5>";
    echo "<p>{$product['description']}</p>";
    echo "<p class='text-muted'>Shs " . number_format($product['price']) . "</p>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<p>Product not found.</p>";
}
