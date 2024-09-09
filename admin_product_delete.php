<?php
session_start();
include 'check_admin.php';
include 'db_connection.php';

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$productId = $_GET['id'] ?? null;

if (!$productId) {
    header('Location: admin_products.php');
    exit;
}

try {
    // Start a transaction
    $pdo->beginTransaction();

    // Delete related records first
    $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
    $stmt->execute([$productId]);

    $stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
    $stmt->execute([$productId]);

    $stmt = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
    $stmt->execute([$productId]);

    $stmt = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
    $stmt->execute([$productId]);

    // Finally, delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    // Commit the transaction
    $pdo->commit();

    header('Location: admin_products.php?delete_success=1');
} catch (Exception $e) {
    // If there's an error, roll back the transaction
    $pdo->rollBack();
    header('Location: admin_products.php?delete_error=1');
}
exit;