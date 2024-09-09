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

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: admin_products.php');
    exit;
}

// Fetch subcategories for the dropdown
$stmt = $pdo->query("SELECT * FROM subcategories");
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->execute([$productId]);
$productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product colors
$stmt = $pdo->prepare("SELECT * FROM product_colors WHERE product_id = ?");
$stmt->execute([$productId]);
$productColors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product sizes
$stmt = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
$stmt->execute([$productId]);
$productSizes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['product_name'];
    $productDescription = $_POST['product_description'];
    $productPrice = $_POST['product_price'];
    $subcategoryId = $_POST['subcategory_id'];

    // Update product
    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, subcategory_id = ? WHERE id = ?");
    $stmt->execute([$productName, $productDescription, $productPrice, $subcategoryId, $productId]);

    // Handle product images (you may want to add/remove images)

    // Update colors
    $stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
    $stmt->execute([$productId]);

    foreach ($_POST['colors'] as $key => $colorName) {
        $colorCode = $_POST['color_codes'][$key];
        $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
        $stmt->execute([$productId, $colorName, $colorCode]);
    }

    // Update sizes
    $stmt = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
    $stmt->execute([$productId]);

    foreach ($_POST['sizes'] as $size) {
        $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size) VALUES (?, ?)");
        $stmt->execute([$productId, $size]);
    }

    // Update variants (you may want to add/remove variants)

    // Redirect to the product list page
    header('Location: admin_products.php?success=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Gajou Luxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .color-preview {
            width: 30px;
            height: 30px;
            display: inline-block;
            margin-left: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<a href="logout.php" class="btn btn-danger">Logout</a>
    <div class="admin-container">
        <h1 class="mb-4">Edit Product</h1>
        <form method="POST">
            <div class="mb-3">
                <label for="product_name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="product_description" class="form-label">Description</label>
                <textarea class="form-control" id="product_description" name="product_description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="product_price" class="form-label">Price</label>
                <input type="number" class="form-control" id="product_price" name="product_price" step="0.01" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="subcategory_id" class="form-label">Subcategory</label>
                <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id']; ?>" <?php echo ($subcategory['id'] == $product['subcategory_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subcategory['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3 class="mt-4">Colors</h3>
            <div id="colorContainer">
                <?php foreach ($productColors as $color): ?>
                    <div class="mb-3 color-group">
                        <div class="input-group">
                            <input type="text" class="form-control color-name" name="colors[]" value="<?php echo htmlspecialchars($color['color_name']); ?>" required>
                            <input type="color" class="form-control form-control-color color-code" name="color_codes[]" value="<?php echo $color['color_code']; ?>" required>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary mb-3" id="addColor">Add Color</button>

            <h3 class="mt-4">Sizes</h3>
            <div id="sizeContainer">
                <?php foreach ($productSizes as $size): ?>
                    <div class="mb-3 size-group">
                        <input type="text" class="form-control" name="sizes[]" value="<?php echo htmlspecialchars($size['size']); ?>" required>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary mb-3" id="addSize">Add Size</button>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="admin_products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#addColor').click(function() {
                const colorGroup = $('.color-group').first().clone();
                colorGroup.find('input').val('');
                $('#colorContainer').append(colorGroup);
            });

            $('#addSize').click(function() {
                const sizeGroup = $('.size-group').first().clone();
                sizeGroup.find('input').val('');
                $('#sizeContainer').append(sizeGroup);
            });
        });
    </script>
</body>
</html>