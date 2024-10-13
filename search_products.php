<?php
include 'db_connection.php';

$query = $_GET['query'];
$sql = "SELECT * FROM products WHERE name LIKE :query LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute(['query' => "%$query%"]);

while ($row = $stmt->fetch()) {
    echo "<div class='search-result-item' data-id='{$row['id']}'>{$row['name']}</div>";
}
