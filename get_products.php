<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

require_once 'db_connection.php';

header('Content-Type: application/json');

session_start();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (isset($_GET['category_id'])) {
    $where[] = "p.category_id = ?";
    $params[] = $_GET['category_id'];
}
if (isset($_GET['subcategory_id'])) {
    $where[] = "p.subcategory_id = ?";
    $params[] = $_GET['subcategory_id'];
}

if (isset($_GET['minPrice']) && isset($_GET['maxPrice'])) {
    $where[] = "p.price BETWEEN ? AND ?";
    $params[] = $_GET['minPrice'];
    $params[] = $_GET['maxPrice'];
}

if (isset($_GET['colors']) && is_array($_GET['colors'])) {
    $colorPlaceholders = implode(',', array_fill(0, count($_GET['colors']), '?'));
    $where[] = "p.id IN (SELECT product_id FROM product_colors WHERE color_code IN ($colorPlaceholders))";
    $params = array_merge($params, $_GET['colors']);
}

if (isset($_GET['sizes']) && is_array($_GET['sizes'])) {
    $sizePlaceholders = implode(',', array_fill(0, count($_GET['sizes']), '?'));
    $where[] = "p.id IN (SELECT product_id FROM product_sizes WHERE size IN ($sizePlaceholders))";
    $params = array_merge($params, $_GET['sizes']);
}

if (isset($_GET['search'])) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $searchTerm = "%{$_GET['search']}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $whereClause 
        ORDER BY p.id 
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productsWithWishlist = [];
    foreach ($products as $product) {
        $isInWishlist = false;
        if (isset($_SESSION['user_id'])) {
            $wishlistStmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
            $wishlistStmt->execute([$_SESSION['user_id'], $product['id']]);
            $isInWishlist = $wishlistStmt->rowCount() > 0;
        }

        $product['is_in_wishlist'] = $isInWishlist;
        $productsWithWishlist[] = $product;
    }

    // Count total products for pagination
    $countSql = "SELECT COUNT(DISTINCT p.id) FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET params
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $perPage);

    echo json_encode([
        'products' => $productsWithWishlist,
        'totalPages' => $totalPages,
        'totalProducts' => $totalProducts
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
