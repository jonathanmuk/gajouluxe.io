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

// Fetch product colors with images and sizes
$stmt = $pdo->prepare("
    SELECT pc.*, pi.id as image_id, pi.image_path, pi.is_primary, ps.size, pv.stock_quantity
    FROM product_colors pc
    LEFT JOIN product_images pi ON pc.id = pi.color_id
    LEFT JOIN product_sizes ps ON pc.id = ps.color_id
    LEFT JOIN product_variants pv ON pc.id = pv.color_id AND ps.id = pv.size_id
    WHERE pc.product_id = ?
");
$stmt->execute([$productId]);
$productData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group the data by color
$groupedProductData = [];
foreach ($productData as $row) {
    $colorId = $row['id'];
    if (!isset($groupedProductData[$colorId])) {
        $groupedProductData[$colorId] = [
            'id' => $row['id'],
            'color_name' => $row['color_name'],
            'color_code' => $row['color_code'],
            'images' => [],
            'sizes' => []
        ];
    }
    if ($row['image_id']) {
        $groupedProductData[$colorId]['images'][] = [
            'id' => $row['image_id'],
            'url' => $row['image_path'],
            'is_primary' => $row['is_primary']
        ];
    }
    if ($row['size']) {
        $groupedProductData[$colorId]['sizes'][$row['size']] = $row['stock_quantity'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update product details
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, subcategory_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['product_name'],
            $_POST['product_description'],
            $_POST['product_price'],
            $_POST['subcategory_id'],
            $productId
        ]);

        // Get existing color IDs for this product
        $stmt = $pdo->prepare("SELECT id FROM product_colors WHERE product_id = ?");
        $stmt->execute([$productId]);
        $existingColorIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Process each variant
        $processedColorIds = [];
        foreach ($_POST['variants'] as $variantData) {
            if (isset($variantData['id']) && in_array($variantData['id'], $existingColorIds)) {
                // Update existing color
                $stmt = $pdo->prepare("UPDATE product_colors SET color_name = ?, color_code = ? WHERE id = ?");
                $stmt->execute([$variantData['color_name'], $variantData['color_code'], $variantData['id']]);
                $colorId = $variantData['id'];
            } else {
                // Insert new color
                $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $variantData['color_name'], $variantData['color_code']]);
                $colorId = $pdo->lastInsertId();
            }
            $processedColorIds[] = $colorId;

            // Delete existing images for this color
            $stmt = $pdo->prepare("DELETE FROM product_images WHERE color_id = ?");
            $stmt->execute([$colorId]);

            // Insert new images
            foreach ($variantData['images'] as $imageUrl) {
                $isPrimary = ($imageUrl === $_POST['primary_image']) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, color_id, image_url, is_primary) VALUES (?, ?, ?, ?)");
                $stmt->execute([$productId, $colorId, $imageUrl, $isPrimary]);
            }

        // Delete existing sizes and variants for this color
        $stmt = $pdo->prepare("DELETE FROM product_sizes WHERE color_id = ?");
        $stmt->execute([$colorId]);
        $stmt = $pdo->prepare("DELETE FROM product_variants WHERE color_id = ?");
        $stmt->execute([$colorId]);

        // Insert new sizes and variants
        foreach ($variantData['sizes'] as $size => $quantity) {
            if ($quantity > 0) {
                $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, color_id, size) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $colorId, $size]);
                $sizeId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, color_id, size_id, stock_quantity) VALUES (?, ?, ?, ?)");
                $stmt->execute([$productId, $colorId, $sizeId, $quantity]);
            }
        }
    }

    // Delete colors that were not processed (i.e., removed by the user)
    $colorsToDelete = array_diff($existingColorIds, $processedColorIds);
    foreach ($colorsToDelete as $colorId) {
        // Delete associated images, sizes, and variants first
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE color_id = ?");
        $stmt->execute([$colorId]);
        $stmt = $pdo->prepare("DELETE FROM product_sizes WHERE color_id = ?");
        $stmt->execute([$colorId]);
        $stmt = $pdo->prepare("DELETE FROM product_variants WHERE color_id = ?");
        $stmt->execute([$colorId]);

        // Now delete the color
        $stmt = $pdo->prepare("DELETE FROM product_colors WHERE id = ?");
        $stmt->execute([$colorId]);
    }

        // Commit transaction
        $pdo->commit();

        header('Location: admin_products.php?success=1');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "An error occurred: " . $e->getMessage();
    }
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
        body { background-color: #f8f9fa; }
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
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="btn btn-danger m-3">Logout</a>
    <div class="admin-container">
        <h1 class="mb-4">Edit Product</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
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

            <h3 class="mt-4">Colors, Images, and Sizes</h3>
            <div id="variantContainer">
                <?php foreach ($groupedProductData as $colorId => $colorData): ?>
                    <div class="variant-group mb-4">
                        <input type="hidden" name="variants[<?php echo $colorId; ?>][id]" value="<?php echo $colorId; ?>">
                        <div class="mb-3">
                            <label class="form-label">Color Name</label>
                            <input type="text" class="form-control color-name" name="variants[<?php echo $colorId; ?>][color_name]" value="<?php echo htmlspecialchars($colorData['color_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color Code</label>
                            <input type="color" class="form-control form-control-color color-code" name="variants[<?php echo $colorId; ?>][color_code]" value="<?php echo $colorData['color_code']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Images</label>
                            <div class="image-container">
                                <?php foreach ($colorData['images'] as $image): ?>
                                    <div class="image-group">
                                        <img src="<?php echo $image['url']; ?>" class="image-preview" alt="Product Image">
                                        <input type="hidden" name="variants[<?php echo $colorId; ?>][images][]" value="<?php echo $image['url']; ?>">
                                        <button type="button" class="btn btn-sm btn-danger remove-image">Remove</button>
                                        <div class="form-check">
                                            <input class="form-check-input primary-image" type="radio" name="primary_image" value="<?php echo $image['url']; ?>" <?php echo $image['is_primary'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Primary Image</label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="file" class="form-control mt-2 add-image" accept="image/*" multiple>
                        </div>
                        <div class="mb-3">
                            <h5>Sizes and Quantities</h5>
                            <div class="size-container">
                                <?php foreach ($colorData['sizes'] as $size => $quantity): ?>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control size-name" name="variants[<?php echo $colorId; ?>][sizes][<?php echo $size; ?>]" value="<?php echo $size; ?>" readonly>
                                        <input type="number" class="form-control size-quantity" name="variants[<?php echo $colorId; ?>][quantities][<?php echo $size; ?>]" value="<?php echo $quantity; ?>" min="0">
                                        <button type="button" class="btn btn-danger remove-size">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-secondary add-size">Add Size</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary mb-3" id="addVariant">Add Color Variant</button>

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
            // Add new color variant
            $('#addVariant').click(function() {
                const variantGroup = $('.variant-group').first().clone();
                variantGroup.find('input').val('');
                variantGroup.find('.image-container').empty();
                variantGroup.find('.size-container').empty();
                variantGroup.find('input[name$="[id]"]').remove(); // Remove the ID field for new variants
                const newIndex = Date.now(); // Use timestamp as a unique identifier
                variantGroup.find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/variants\[\d+\]/, 'variants[new_' + newIndex + ']');
                        $(this).attr('name', name);
                    }
                });
                $('#variantContainer').append(variantGroup);
            });

            // Add new size
            $(document).on('click', '.add-size', function() {
                const sizeContainer = $(this).siblings('.size-container');
                const newSize = $('<div class="input-group mb-2">' +
                    '<input type="text" class="form-control size-name" name="size_name[]" required>' +
                    '<input type="number" class="form-control size-quantity" name="size_quantity[]" value="0" min="0">' +
                    '<button type="button" class="btn btn-danger remove-size">Remove</button>' +
                    '</div>');
                sizeContainer.append(newSize);
            });

            // Remove size
            $(document).on('click', '.remove-size', function() {
                $(this).closest('.input-group').remove();
            });

            // Add new images
            $(document).on('change', '.add-image', function(e) {
                const files = e.target.files;
                const imageContainer = $(this).siblings('.image-container');
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const newImage = $('<div class="image-group">' +
                            '<img src="' + e.target.result + '" class="image-preview" alt="Product Image">' +
                            '<input type="hidden" name="new_images[]" value="' + e.target.result + '">' +
                            '<button type="button" class="btn btn-sm btn-danger remove-image">Remove</button>' +
                            '<div class="form-check">' +
                            '<input class="form-check-input primary-image" type="radio" name="primary_image" value="' + e.target.result + '">' +
                            '<label class="form-check-label">Primary Image</label>' +
                            '</div>' +
                            '</div>');
                        imageContainer.append(newImage);
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Remove image
            $(document).on('click', '.remove-image', function() {
                $(this).closest('.image-group').remove();
            });

            // Update form before submission
            $('form').submit(function() {
                $('.variant-group').each(function(index) {
                    $(this).find('input, select').each(function() {
                        let name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/variants\[\d+\]/, 'variants[' + index + ']');
                            $(this).attr('name', name);
                        }
                    });
                });
            });

            // Ensure at least one primary image is selected
            $(document).on('change', '.primary-image', function() {
                if ($('.primary-image:checked').length === 0) {
                    $(this).prop('checked', true);
                }
            });
        });
    </script>
</body>
</html>
