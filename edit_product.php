 <?php
// edit_product.php - Edit Product (Admin Only) - Supports ALL image types

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'config.php';
check_login();

// KINGA: ADMIN PEKEE
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    $_SESSION['flash_message'] = 'Access denied! Only admins can edit products.';
    $_SESSION['flash_type'] = 'error';
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$message_key = '';

// GET PRODUCT ID
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    $_SESSION['flash_message_key'] = 'ep_error_invalid_id';
    $_SESSION['flash_type'] = 'error';
    header('Location: products.php');
    exit();
}

// FETCH PRODUCT
$stmt = $conn->prepare("
    SELECT p.*, mc.category_name 
    FROM product p 
    LEFT JOIN menu_category mc ON p.category_id = mc.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $_SESSION['flash_message_key'] = 'ep_error_not_found';
    $_SESSION['flash_type'] = 'error';
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {

    $product_name = trim($_POST['product_name'] ?? '');
    $category_id  = intval($_POST['category_id'] ?? 0);
    $price        = floatval($_POST['price'] ?? 0);
    $quantity     = intval($_POST['quantity'] ?? 0);

    if (empty($product_name)) {
        $message_key = 'ep_error_name_required';
        $message_type = 'error';
    } elseif (mb_strlen($product_name) > 255) {
        $message_key = 'ep_error_name_long';
        $message_type = 'error';
    } elseif ($category_id <= 0) {
        $message_key = 'ep_error_category';
        $message_type = 'error';
    } elseif ($price < 0) {
        $message_key = 'ep_error_price';
        $message_type = 'error';
    } elseif ($quantity < 0) {
        $message_key = 'ep_error_quantity';
        $message_type = 'error';
    } else {
        $new_image_path = $product['image'];
        $old_image_to_delete = null;

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {

            $allowed = [
                'jpg', 'jpeg', 'jpe', 'jfif', 'jif', 'jfi',
                'png', 'apng', 'gif', 'webp',
                'bmp', 'dib', 'tiff', 'tif',
                'ico', 'cur', 'heic', 'heif',
                'avif', 'svg', 'svgz',
                'pbm', 'pgm', 'ppm', 'pnm', 'xbm', 'xpm'
            ];

            $filename = $_FILES['product_image']['name'];
            $filesize = $_FILES['product_image']['size'];
            $filetmp  = $_FILES['product_image']['tmp_name'];
            $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $message_key = 'ep_error_invalid_type';
                $message_type = 'error';
            } elseif ($filesize > 10 * 1024 * 1024) {
                $message_key = 'ep_error_file_large';
                $message_type = 'error';
            } elseif (!in_array($ext, ['svg', 'svgz', 'ico', 'cur']) && !@getimagesize($filetmp)) {
                $message_key = 'ep_error_invalid_image';
                $message_type = 'error';
            } else {
                $upload_dir = __DIR__ . '/uploads/products/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0755, true);
                }

                $new_filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $upload_path  = 'uploads/products/' . $new_filename;
                $full_path    = $upload_dir . $new_filename;

                if (move_uploaded_file($filetmp, $full_path)) {
                    if (!empty($product['image']) && file_exists($product['image'])) {
                        $old_image_to_delete = $product['image'];
                    }
                    $new_image_path = $upload_path;
                } else {
                    $message_key = 'ep_error_upload';
                    $message_type = 'error';
                }
            }
        }

        if ($message_type !== 'error') {
            $update_stmt = $conn->prepare("
                UPDATE product 
                SET product_name = ?, category_id = ?, price = ?, quantity = ?, image = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param(
                "sidisi",
                $product_name,
                $category_id,
                $price,
                $quantity,
                $new_image_path,
                $product_id
            );

            if ($update_stmt->execute()) {
                if ($old_image_to_delete && file_exists($old_image_to_delete)) {
                    @unlink($old_image_to_delete);
                }

                if (function_exists('log_activity')) {
                    log_activity($conn, 'update', 'product', $product_id,
                        "name:$product_name;price:$price;qty:$quantity");
                }

                $_SESSION['flash_message'] = "Product '$product_name' updated successfully!";
                $_SESSION['flash_type'] = 'success';

                $update_stmt->close();
                header('Location: products.php');
                exit();
            } else {
                $message = 'Error updating product: ' . $update_stmt->error;
                $message_type = 'error';

                if ($new_image_path && $new_image_path !== $product['image'] && file_exists($new_image_path)) {
                    @unlink($new_image_path);
                }
            }
            $update_stmt->close();
        }
    }

    // Re-fetch product
    $stmt = $conn->prepare("SELECT p.*, mc.category_name FROM product p LEFT JOIN menu_category mc ON p.category_id = mc.id WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// GET CATEGORIES
$categories = mysqli_query($conn, "SELECT id, category_name FROM menu_category ORDER BY category_name");
if (!$categories) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'ep_error_invalid_id'     => 'Invalid product ID.',
        'ep_error_not_found'      => 'Product not found.',
        'ep_error_name_required'  => 'Product name is required!',
        'ep_error_name_long'      => 'Product name is too long (max 255 chars)!',
        'ep_error_category'       => 'Please select a category!',
        'ep_error_price'          => 'Price cannot be negative!',
        'ep_error_quantity'       => 'Quantity cannot be negative!',
        'ep_error_invalid_type'   => 'Invalid file type!',
        'ep_error_file_large'     => 'File too large! Maximum 10MB.',
        'ep_error_invalid_image'  => 'File is not a valid image!',
        'ep_error_upload'         => 'Error uploading new image!',
    ],
    'sw' => [
        'ep_error_invalid_id'     => 'Kitambulisho cha bidhaa si sahihi.',
        'ep_error_not_found'      => 'Bidhaa haipatikani.',
        'ep_error_name_required'  => 'Jina la bidhaa linahitajika!',
        'ep_error_name_long'      => 'Jina la bidhaa ni refu mno (upeo herufi 255)!',
        'ep_error_category'       => 'Tafadhali chagua kategoria!',
        'ep_error_price'          => 'Bei haiwezi kuwa hasi!',
        'ep_error_quantity'       => 'Kiasi hakiwezi kuwa hasi!',
        'ep_error_invalid_type'   => 'Aina ya faili si sahihi!',
        'ep_error_file_large'     => 'Faili ni kubwa mno! Upeo 10MB.',
        'ep_error_invalid_image'  => 'Faili si picha sahihi!',
        'ep_error_upload'         => 'Kosa la kupakia picha mpya!',
    ]
];
$message = $message_key && isset($messageTranslations[$lang][$message_key])
    ? $messageTranslations[$lang][$message_key]
    : $message;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="ep_title">Edit Product - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
    <style>
        .compact-form { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-row { display: grid; grid-template-columns: 180px 1fr; gap: 20px; align-items: start; }
        .image-section { text-align: center; }
        .image-preview-box {
            width: 180px; height: 180px; border-radius: 10px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 3rem; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #E07B39; margin: 0 auto 10px; position: relative;
        }
        .image-preview-box img { width: 100%; height: 100%; object-fit: cover; }
        .current-image-label {
            display: inline-block; background: #f0f0f0; color: #555;
            padding: 3px 10px; border-radius: 10px; font-size: 10px;
            font-weight: 600; margin-bottom: 5px;
        }
        .file-input {
            width: 100%; max-width: 180px; padding: 6px;
            font-size: 11px; border: 1px solid #ddd;
            border-radius: 5px; margin-bottom: 5px;
        }
        .compact-form .form-group { margin-bottom: 12px; }
        .compact-form .form-group label {
            display: block; margin-bottom: 4px;
            font-size: 13px; font-weight: 600; color: #333;
        }
        .compact-form .form-control {
            width: 100%; padding: 8px 10px; font-size: 14px;
            border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;
        }
        .compact-form .form-control:focus {
            outline: none; border-color: #E07B39;
            box-shadow: 0 0 0 3px rgba(224, 123, 57, 0.15);
        }
        .form-row-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-actions { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        .small-note { color: #888; font-size: 11px; display: block; margin-top: 3px; }
        .product-meta {
            background: #fff8f0; border-left: 4px solid #E07B39;
            padding: 10px 14px; border-radius: 6px;
            font-size: 0.85em; color: #666; margin-bottom: 15px;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .form-row-inline { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-edit"></i>
                    <span data-i18n="ep_title">Edit Product</span>
                </h1>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="ep_back">Back to Products</span>
                </a>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-box"></i>
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </h2>
                    <span style="color: #999; font-size: 0.9em;">
                        <span data-i18n="ep_id">ID:</span>
                        #<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?>
                    </span>
                </div>

                <div class="compact-form">
                    <div class="product-meta">
                        <i class="fas fa-info-circle"></i>
                        <strong data-i18n="ep_current">Current:</strong>
                        <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?> |
                        $<?php echo number_format($product['price'], 2); ?> |
                        <?php echo $product['quantity']; ?>
                        <span data-i18n="ep_units">units in stock</span>
                    </div>

                    <form method="POST" enctype="multipart/form-data" action="edit_product.php?id=<?php echo $product_id; ?>">
                        <div class="form-row">

                            <div class="image-section">
                                <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">
                                    <i class="fas fa-image"></i>
                                    <span data-i18n="ep_product_image">Product Image</span>
                                </label>

                                <div class="image-preview-box">
                                    <img id="imagePreview"
                                         src="<?php echo !empty($product['image']) && file_exists($product['image'])
                                            ? htmlspecialchars($product['image']) : ''; ?>"
                                         alt="Preview"
                                         style="<?php echo !empty($product['image']) && file_exists($product['image'])
                                            ? 'display:block;' : 'display:none;'; ?>">
                                    <i id="imageIcon" class="fas fa-utensils"
                                       style="<?php echo !empty($product['image']) && file_exists($product['image'])
                                            ? 'display:none;' : 'display:block;'; ?>"></i>
                                </div>

                                <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                                    <span class="current-image-label">
                                        <i class="fas fa-check"></i>
                                        <span data-i18n="ep_current_image">Current image</span>
                                    </span>
                                <?php endif; ?>

                                <input type="file" name="product_image" id="imageInput"
                                       class="file-input" accept="image/*"
                                       onchange="previewImage(this)">
                                <span class="small-note">
                                    <span data-i18n="ep_leave_empty">Leave empty to keep current image</span><br>
                                    <span data-i18n="ep_max_size">Max 10MB</span><br>
                                    <strong data-i18n="ep_all_types">All image types</strong>
                                </span>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-tag"></i>
                                        <span data-i18n="ep_product_name">Product Name *</span>
                                    </label>
                                    <input type="text" name="product_name" class="form-control"
                                           data-i18n="ep_product_name_ph" data-i18n-placeholder
                                           placeholder="e.g., Chicken Biryani"
                                           maxlength="255" required
                                           value="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-list"></i>
                                        <span data-i18n="ep_category">Category *</span>
                                    </label>
                                    <select name="category_id" class="form-control" required>
                                        <option value="" data-i18n="ep_select_category">Select Category</option>
                                        <?php
                                        if ($categories && mysqli_num_rows($categories) > 0):
                                            mysqli_data_seek($categories, 0);
                                            while($category = mysqli_fetch_assoc($categories)):
                                        ?>
                                        <option value="<?php echo $category['id']; ?>"
                                            <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                        <?php
                                            endwhile;
                                        endif;
                                        ?>
                                    </select>
                                </div>

                                <div class="form-row-inline">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-dollar-sign"></i>
                                            <span data-i18n="ep_price">Price ($) *</span>
                                        </label>
                                        <input type="number" name="price" class="form-control"
                                               step="0.01" min="0" placeholder="0.00" required
                                               value="<?php echo htmlspecialchars($product['price']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-cubes"></i>
                                            <span data-i18n="ep_quantity">Stock Quantity *</span>
                                        </label>
                                        <input type="number" name="quantity" class="form-control"
                                               min="0" required
                                               value="<?php echo htmlspecialchars($product['quantity']); ?>">
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" name="update_product" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <span data-i18n="ep_update">Update Product</span>
                                    </button>
                                    <a href="products.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                        <span data-i18n="ep_cancel">Cancel</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
    function previewImage(input) {
        var preview = document.getElementById('imagePreview');
        var icon = document.getElementById('imageIcon');

        if (input.files && input.files[0]) {
            if (input.files[0].size > 10 * 1024 * 1024) {
                alert('<?= $lang === "sw" ? "Faili ni kubwa mno! Upeo 10MB." : "File too large! Maximum 10MB." ?>');
                input.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                icon.style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    window.addEventListener('languageChanged', () => {
        if (typeof applyTranslations === 'function') {
            applyTranslations();
        }
    });
    </script>
</body>
</html>