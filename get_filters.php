<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
require_once 'db_connection.php';

// Now $pdo is available for use

try {
    // Query your database to get unique colors, sizes, min and max prices
    $colors_query = "SELECT DISTINCT color_code FROM product_colors WHERE color_name IS NOT NULL AND color_name != ''";
    $sizes_query = "SELECT DISTINCT size FROM product_sizes WHERE size IS NOT NULL AND size != ''";
    $price_query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products";

    $colors_stmt = $pdo->query($colors_query);
    $sizes_stmt = $pdo->query($sizes_query);
    $price_stmt = $pdo->query($price_query);

    $colors = $colors_stmt->fetchAll(PDO::FETCH_COLUMN);
    $sizes = $sizes_stmt->fetchAll(PDO::FETCH_COLUMN);
    $price_row = $price_stmt->fetch();

    $response = [
        'colors' => $colors,
        'sizes' => $sizes,
        'minPrice' => floatval($price_row['min_price']),
        'maxPrice' => floatval($price_row['max_price'])
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    // If there's an error in the queries, return an error response
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
