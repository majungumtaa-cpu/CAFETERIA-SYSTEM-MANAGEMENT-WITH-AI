<?php
// add_product.php - Add New Product + Recent Products Display

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'config.php';
check_login();

// KINGA: ADMIN PEKEE
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    $_SESSION['flash_message'] = 'Access denied! Only admins can add products.';
    $_SESSION['flash_type'] = 'error';
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$message_key = '';
$debug_info = '';

// ================================================
// HAKIKISHA FOLDER IPO
// ================================================
$upload_dir = __DIR__ . '/uploads/products/';

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

if (!is_writable($upload_dir)) {
    @chmod($upload_dir, 0755);
}

// ================================================
// HANDLE FORM SUBMISSION
// ================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {

    $product_name = trim($_POST['product_name'] ?? '');
    $category_id  = intval($_POST['category_id'] ?? 0);
    $price        = floatval($_POST['price'] ?? 0);
    $quantity     = intval($_POST['quantity'] ?? 0);

    if (empty($product_name)) {
        $message_key = 'ap_error_name_required';
        $message_type = 'error';
    } elseif (mb_strlen($product_name) > 255) {
        $message_key = 'ap_error_name_long';
        $message_type = 'error';
    } elseif ($category_id <= 0) {
        $message_key = 'ap_error_category';
        $message_type = 'error';
    } elseif ($price < 0) {
        $message_key = 'ap_error_price';
        $message_type = 'error';
    } elseif ($quantity < 0) {
        $message_key = 'ap_error_quantity';
        $message_type = 'error';
    } else {
        $image_path = null;
        $uploaded_file_full_path = null;

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
                $message_key = 'ap_error_invalid_type';
                $message_type = 'error';
            } elseif ($filesize > 10 * 1024 * 1024) {
                $message_key = 'ap_error_file_large';
                $message_type = 'error';
            } elseif (!in_array($ext, ['svg', 'svgz', 'ico', 'cur']) && !@getimagesize($filetmp)) {
                $message_key = 'ap_error_invalid_image';
                $message_type = 'error';
            } else {
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0755, true);
                }

                $new_filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $upload_path  = 'uploads/products/' . $new_filename;
                $full_path    = $upload_dir . $new_filename;

                if (move_uploaded_file($filetmp, $full_path)) {
                    $image_path = $upload_path;
                    $uploaded_file_full_path = $full_path;
                } else {
                    $message_key = 'ap_error_upload';
                    $message_type = 'error';
                }
            }
        }

        if ($message_type !== 'error') {
            $stmt = $conn->prepare("
                INSERT INTO product (product_name, category_id, price, quantity, image) 
                VALUES (?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                $message = 'Database prepare error: ' . $conn->error;
                $message_type = 'error';
            } else {
                $stmt->bind_param("sidis", $product_name, $category_id, $price, $quantity, $image_path);

                if ($stmt->execute()) {
                    $product_id = $stmt->insert_id;

                    if (function_exists('log_activity')) {
                        log_activity($conn, 'add', 'product', $product_id, "name:$product_name;price:$price");
                    }

                    $_SESSION['flash_message'] = "Product '$product_name' added successfully!";
                    $_SESSION['flash_type'] = 'success';

                    $stmt->close();

                    header('Location: add_product.php?added=1');
                    exit();
                } else {
                    $message = 'Error adding product: ' . $stmt->error;
                    $message_type = 'error';

                    if ($uploaded_file_full_path && file_exists($uploaded_file_full_path)) {
                        @unlink($uploaded_file_full_path);
                    }
                }
                $stmt->close();
            }
        }
    }
}

// ================================================
// GET CATEGORIES
// ================================================
$categories = mysqli_query($conn, "SELECT id, category_name FROM menu_category ORDER BY category_name");
if (!$categories) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}

// ================================================
// GET RECENT PRODUCTS
// ================================================
$recent_products = mysqli_query($conn, "
    SELECT p.*, mc.category_name
    FROM product p
    LEFT JOIN menu_category mc ON p.category_id = mc.id
    ORDER BY p.id DESC
    LIMIT 20
");

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'ap_error_name_required'  => 'Product name is required!',
        'ap_error_name_long'      => 'Product name is too long (max 255 chars)!',
        'ap_error_category'       => 'Please select a category!',
        'ap_error_price'          => 'Price cannot be negative!',
        'ap_error_quantity'       => 'Quantity cannot be negative!',
        'ap_error_invalid_type'   => 'Invalid file type!',
        'ap_error_file_large'     => 'File too large! Maximum 10MB.',
        'ap_error_invalid_image'  => 'File is not a valid image!',
        'ap_error_upload'         => 'Error uploading image!'
    ],
    'sw' => [
        'ap_error_name_required'  => 'Jina la bidhaa linahitajika!',
        'ap_error_name_long'      => 'Jina la bidhaa ni refu mno (upeo herufi 255)!',
        'ap_error_category'       => 'Tafadhali chagua kategoria!',
        'ap_error_price'          => 'Bei haiwezi kuwa hasi!',
        'ap_error_quantity'       => 'Kiasi hakiwezi kuwa hasi!',
        'ap_error_invalid_type'   => 'Aina ya faili si sahihi!',
        'ap_error_file_large'     => 'Faili ni kubwa mno! Upeo 10MB.',
        'ap_error_invalid_image'  => 'Faili si picha sahihi!',
        'ap_error_upload'         => 'Kosa la kupakia picha!'
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
    <title data-i18n="ap_title">Add Product - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
    <style>
        .compact-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 20px;
            align-items: start;
        }
        .image-section { text-align: center; }
        .image-preview-box {
            width: 180px;
            height: 180px;
            border-radius: 10px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #E07B39;
            margin: 0 auto 10px;
        }
        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .file-input {
            width: 100%;
            max-width: 180px;
            padding: 6px;
            font-size: 11px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .compact-form .form-group { margin-bottom: 12px; }
        .compact-form .form-group label {
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }
        .compact-form .form-control {
            width: 100%;
            padding: 8px 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .compact-form .form-control:focus {
            outline: none;
            border-color: #E07B39;
            box-shadow: 0 0 0 3px rgba(224, 123, 57, 0.15);
        }
        .form-row-inline {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .form-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .small-note {
            color: #888;
            font-size: 11px;
            display: block;
            margin-top: 3px;
        }
        .debug-box {
            background: #ffffcc;
            border: 2px solid #f39c12;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 0.85em;
            line-height: 1.6;
        }

        .recent-products-section {
            margin-top: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            padding: 20px;
        }
        .recent-products-section h2 {
            color: #E07B39;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .products-display-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        .product-display-card {
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .product-display-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: #E07B39;
        }
        .product-display-image {
            width: 100%;
            height: 140px;
            background: linear-gradient(135deg, #E07B39, #C86A2F);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5em;
            overflow: hidden;
        }
        .product-display-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-display-info {
            padding: 12px;
        }
        .product-display-name {
            font-weight: 700;
            color: #333;
            font-size: 0.95em;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-display-category {
            font-size: 0.75em;
            color: #888;
            margin-bottom: 6px;
        }
        .product-display-price {
            color: #2ecc71;
            font-weight: 800;
            font-size: 1.1em;
        }
        .product-display-stock {
            font-size: 0.75em;
            color: #666;
            margin-top: 4px;
        }
        .product-display-stock.low {
            color: #f39c12;
            font-weight: 700;
        }
        .product-display-stock.out {
            color: #e74c3c;
            font-weight: 700;
        }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .form-row-inline { grid-template-columns: 1fr; }
            .products-display-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
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
                    <i class="fas fa-plus-circle"></i>
                    <span data-i18n="ap_title">Add New Product</span>
                </h1>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="ap_back_products">Back to Products</span>
                </a>
            </div>

            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?>">
                <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($debug_info)): ?>
            <div class="debug-box">
                <strong>🔍 DEBUG INFO:</strong><br>
                <?php echo $debug_info; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-plus"></i>
                        <span data-i18n="ap_product_info">Product Information</span>
                    </h2>
                </div>

                <form method="POST" enctype="multipart/form-data" class="compact-form" id="productForm">
                    <div class="form-row">

                        <div class="image-section">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">
                                <i class="fas fa-image"></i>
                                <span data-i18n="ap_product_image">Product Image</span>
                            </label>

                            <div class="image-preview-box">
                                <img id="imagePreview" src="" alt="Preview" style="display:none;">
                                <i id="imageIcon" class="fas fa-utensils"></i>
                            </div>

                            <input type="file" name="product_image" id="imageInput"
                                   class="file-input" accept="image/*"
                                   onchange="previewImage(this)">
                            <span class="small-note">
                                <span data-i18n="ap_max_size">Max 10MB</span><br>
                                <span data-i18n="ap_all_types">All image types</span>
                            </span>
                        </div>

                        <div>
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tag"></i>
                                    <span data-i18n="ap_product_name">Product Name</span> *
                                </label>
                                <input type="text" name="product_name" class="form-control"
                                       data-i18n="ap_product_name_ph" data-i18n-placeholder
                                       placeholder="e.g., Chicken Biryani"
                                       maxlength="255" required
                                       value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-list"></i>
                                    <span data-i18n="ap_category">Category</span> *
                                </label>
                                <select name="category_id" class="form-control" required>
                                    <option value="" data-i18n="ap_select_category">Select Category</option>
                                    <?php
                                    $selected_cat = intval($_POST['category_id'] ?? 0);
                                    mysqli_data_seek($categories, 0);
                                    while($category = mysqli_fetch_assoc($categories)):
                                    ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo ($selected_cat == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-row-inline">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-dollar-sign"></i>
                                        <span data-i18n="ap_price">Price ($)</span> *
                                    </label>
                                    <input type="number" name="price" class="form-control"
                                           step="0.01" min="0" placeholder="0.00" required
                                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-cubes"></i>
                                        <span data-i18n="ap_quantity">Stock Quantity</span> *
                                    </label>
                                    <input type="number" name="quantity" class="form-control"
                                           min="0" required
                                           value="<?php echo htmlspecialchars($_POST['quantity'] ?? '0'); ?>">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="add_product" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    <span data-i18n="ap_save">Save Product</span>
                                </button>
                                <a href="products.php" class="btn btn-secondary" data-i18n="ap_cancel">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="recent-products-section">
                <h2>
                    <span>
                        <i class="fas fa-boxes"></i>
                        <span data-i18n="ap_recent_products">Recent Products</span>
                    </span>
                    <a href="products.php" class="btn btn-sm btn-primary">
                        <span data-i18n="ap_view_all">View All</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </h2>

                <?php if ($recent_products && mysqli_num_rows($recent_products) > 0): ?>
                    <div class="products-display-grid">
                        <?php while($product = mysqli_fetch_assoc($recent_products)): ?>
                            <?php
                            $qty = intval($product['quantity']);
                            $stock_class = '';
                            $stock_key = 'ap_stock_normal';
                            $stock_text = "$qty " . ($lang === 'sw' ? 'vipande' : 'units');

                            if ($qty == 0) {
                                $stock_class = 'out';
                                $stock_key = 'ap_stock_out';
                                $stock_text = $lang === 'sw' ? 'Hakuna stock' : 'Out of stock';
                            } elseif ($qty < 10) {
                                $stock_class = 'low';
                                $stock_key = 'ap_stock_low';
                                $stock_text = "$qty " . ($lang === 'sw' ? 'vipande (Chini)' : 'units (Low)');
                            }
                            ?>
                            <div class="product-display-card">
                                <div class="product-display-image">
                                    <?php
                                    $img_path = $product['image'] ?? '';
                                    $img_exists = !empty($img_path) && file_exists($img_path);
                                    ?>
                                    <?php if ($img_exists): ?>
                                        <img src="<?php echo htmlspecialchars($img_path); ?>?t=<?php echo time(); ?>"
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <i class="fas fa-utensils" style="display: none;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-utensils"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-display-info">
                                    <div class="product-display-name" title="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </div>
                                    <div class="product-display-category">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="product-display-price">
                                        $<?php echo number_format($product['price'], 2); ?>
                                    </div>
                                    <div class="product-display-stock <?php echo $stock_class; ?>">
                                        <i class="fas fa-cubes"></i> <?php echo $stock_text; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 3em; display: block; margin-bottom: 10px; color: #ddd;"></i>
                        <p data-i18n="ap_no_products">No products yet. Add your first product above!</p>
                    </div>
                <?php endif; ?>
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
        } else {
            preview.style.display = 'none';
            icon.style.display = 'block';
        }
    }

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) { window.location.reload(); }
    });

    window.addEventListener('languageChanged', () => {
        if (typeof applyTranslations === 'function') {
            applyTranslations();
        }
    });
    </script>
</body>
</html>