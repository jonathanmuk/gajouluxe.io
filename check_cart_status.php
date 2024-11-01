<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = ['inCart' => false];

if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $response['inCart'] = $stmt->fetchColumn() > 0;
    } else {
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if ($item['product_id'] == $product_id) {
                    $response['inCart'] = true;
                    break;
                }
            }
        }
    }
}

echo json_encode($response);
