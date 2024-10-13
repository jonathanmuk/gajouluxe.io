<?php
error_log('Received POST data: ' . print_r($_POST, true));
if (empty($_POST['wishlist_id'])) {
    error_log('Error: wishlist_id is empty or not set');
    echo json_encode(['success' => false, 'message' => 'Wishlist ID is missing']);
    exit;
}

$wishlistId = $_POST['wishlist_id'];
error_log('Attempting to remove wishlist item with ID: ' . $wishlistId);

session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];
// Debug: Log the received data
error_log('Received POST data: ' . print_r($_POST, true));

if (isset($_POST['wishlist_id'])) {
    $wishlistId = $_POST['wishlist_id'];

    // Debug: Log the wishlist ID
    error_log('Attempting to remove wishlist item with ID: ' . $wishlistId);

    if (isset($_SESSION['user_id'])) {
        // For logged-in users
        $userId = $_SESSION['user_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$wishlistId, $userId]);

            // Debug: Log the SQL query result
            error_log('SQL query result: ' . ($result ? 'true' : 'false') . ', Rows affected: ' . $stmt->rowCount());

            if ($result && $stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Item removed from wishlist';
            } else {
                $response['message'] = 'Item not found in wishlist';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
            // Debug: Log the database error
            error_log('Database error: ' . $e->getMessage());
        }
    } else {
        // For non-logged-in users
        if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
            $index = array_search($wishlistId, $_SESSION['wishlist']);
            if ($index !== false) {
                unset($_SESSION['wishlist'][$index]);
                $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index the array
                $response['success'] = true;
                $response['message'] = 'Item removed from wishlist';
            } else {
                $response['message'] = 'Item not found in wishlist';
            }
        } else {
            $response['message'] = 'Wishlist is empty';
        }
    }
} else {
    $response['message'] = 'Invalid request: wishlist_id is missing';
}
// Debug: Log the response
error_log('Sending response: ' . print_r($response, true));

echo json_encode($response);