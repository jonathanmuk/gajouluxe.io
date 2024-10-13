<?php
include 'db_connection.php';

$category_query = "SELECT * FROM categories LIMIT 5";
try {
    $stmt = $pdo->query($category_query);
    if ($stmt) {
        while ($category = $stmt->fetch()) {
            echo '<div class="category-item" data-aos="zoom-in">';
            echo '<img src="' . htmlspecialchars($category['image_url']) . '" class="img-fluid" alt="' . htmlspecialchars($category['name']) . '">';
            echo '<div class="category-overlay">';
            echo '<h3>' . htmlspecialchars($category['name']) . '</h3>';
            echo '</div>';
            echo '</div>';
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
