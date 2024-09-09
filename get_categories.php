<?php
include 'db_connection.php';

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $category) {
    echo "<button class='btn category-btn m-2' data-category-id='{$category['id']}'>{$category['name']}</button>";
}