<?php
// user/menu.php - Menu ya Customer

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: user_login.php');
    exit();
}

require_once '../config.php';

$customer_id = (int)$_SESSION['customer_id'];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// HANDLE ADD TO CART (PRG Pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch!');
    }

    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];

    if ($product_id > 0 && $quantity > 0) {
        $stmt = $conn->prepare("SELECT quantity, product_name FROM product WHERE id = ? AND quantity > 0");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $max_stock = (int)$product['quantity'];

            $current_in_cart = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
            $new_quantity = $current_in_cart + $quantity;

            if ($new_quantity > $max_stock) {
                $new_quantity = $max_stock;
                $_SESSION['flash_message'] = $product['product_name'] . ' — ' . $max_stock . ' units available only.';
                $_SESSION['flash_type'] = 'error';
            } else {
                $_SESSION['flash_message'] = $product['product_name'] . ' added to cart!';
                $_SESSION['flash_type'] = 'success';
            }

            $_SESSION['cart'][$product_id] = $new_quantity;
        } else {
            $_SESSION['flash_message'] = 'Product not available.';
            $_SESSION['flash_type'] = 'error';
        }
        $stmt->close();
    }

    header('Location: menu.php?t=' . time());
    exit();
}

// GET ALL PRODUCTS
$products = mysqli_query($conn, "
    SELECT p.*, mc.category_name
    FROM product p
    LEFT JOIN menu_category mc ON p.category_id = mc.id
    WHERE p.quantity > 0
    ORDER BY mc.category_name, p.product_name
");

if (!$products) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}

$products_by_category = array();
while ($product = mysqli_fetch_assoc($products)) {
    $cat_name = $product['category_name'] ?? 'Other';
    if (!isset($products_by_category[$cat_name])) {
        $products_by_category[$cat_name] = array();
    }
    $products_by_category[$cat_name][] = $product;
}

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="menu_title">Menu - Cafeteria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../lang.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: linear-gradient(135deg, rgba(224, 123, 57, 0.05) 0%, rgba(200, 106, 47, 0.05) 100%),
                        url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ============================================
           TOP BAR with LOGOUT (no language switcher)
           ============================================ */
        .top-bar {
            background: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
            margin-bottom: 15px;
        }

        .top-bar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #E07B39;
            font-size: 1.1em;
        }

        .top-bar .brand i { font-size: 1.2em; }

        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-icon {
            position: relative;
            color: #333;
            font-size: 1.2em;
            text-decoration: none;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .cart-icon:hover { background: #f5f5f5; }

        .cart-icon .badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            font-size: 0.7em;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-btn {
            padding: 7px 14px;
            background: #dc3545;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.2s;
        }

        .logout-btn:hover { background: #c82333; }

        /* ============================================
           MENU CONTENT
           ============================================ */
        .menu-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 15px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover { background: #5a6268; }

        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .menu-header h1 {
            margin: 0;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-info { display: flex; align-items: center; gap: 10px; }

        .refresh-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }
        .refresh-btn:hover { background: rgba(255,255,255,0.35); }

        .cart-btn {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.3s, transform 0.2s;
        }
        .cart-btn:hover { background: #218838; transform: translateY(-1px); }

        .cart-count {
            background: #dc3545;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75em;
            font-weight: 700;
        }

        .category-section { margin-bottom: 25px; }

        .category-title {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 5px solid #E07B39;
        }
        .category-title h2 {
            margin: 0;
            color: #333;
            font-size: 1.1em;
            font-weight: 700;
        }

        .products-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: row;
            min-height: 180px;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .product-image {
            width: 220px;
            min-width: 220px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3.5em;
            overflow: hidden;
            position: relative;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .product-info {
            padding: 18px 22px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .product-name {
            font-size: 1.4em;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-price {
            font-size: 1.6em;
            color: #2ecc71;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .product-stock {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .add-to-cart-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .quantity-input {
            width: 70px;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 1em;
            text-align: center;
            font-family: inherit;
            font-weight: 600;
        }
        .quantity-input:focus { outline: none; border-color: #E07B39; }

        .add-btn {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            flex: 1;
            transition: transform 0.2s;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .add-btn:hover { transform: translateY(-1px); }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #2ecc71; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #e74c3c; }

        .empty-menu {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        .empty-menu i {
            font-size: 3.5em;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
        .empty-menu h3 { color: #666; margin-bottom: 10px; }

        @media (max-width: 600px) {
            .product-card {
                flex-direction: column;
                min-height: auto;
            }
            .product-image {
                width: 100%;
                height: 200px;
                min-width: unset;
            }
            .product-name { font-size: 1.2em; }
            .product-price { font-size: 1.4em; }
            .menu-container { padding: 12px; }
            .top-bar { padding: 10px 15px; }
            .logout-btn span { display: none; }
        }
    </style>
</head>
<body>

    <!-- ============================================
         TOP BAR — With Cart + Logout (NO language switcher)
         ============================================ -->
    <div class="top-bar">
        <div class="brand">
            <i class="fas fa-utensils"></i>
            <span data-i18n="brand">Cafeteria</span>
        </div>
        <div class="top-bar-actions">

            <!-- Cart Icon -->
            <a href="cart.php" class="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- Logout Button -->
            <a href="user_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span data-i18n="logout">Logout</span>
            </a>
        </div>
    </div>

    <div class="menu-container">
        <a href="user_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span data-i18n="back_dashboard">Back to Dashboard</span>
        </a>

        <div class="menu-header">
            <h1>
                <i class="fas fa-utensils"></i>
                <span data-i18n="menu_title">Our Menu</span>
            </h1>
            <div class="cart-info">
                <a href="menu.php?t=<?php echo time(); ?>" class="refresh-btn" title="Refresh Menu">
                    <i class="fas fa-sync"></i>
                    <span data-i18n="refresh">Refresh</span>
                </a>
                <a href="cart.php?t=<?php echo time(); ?>" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <span data-i18n="view_cart">View Cart</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($flash_message); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($products_by_category)): ?>
            <div class="empty-menu">
                <i class="fas fa-utensils"></i>
                <h3 data-i18n="no_products">No products available</h3>
                <p style="color: #999; margin-bottom: 15px;" data-i18n="no_products_desc">
                    Products may be removed or out of stock.
                </p>
                <a href="menu.php?t=<?php echo time(); ?>" style="display: inline-block; padding: 10px 20px; background: #E07B39; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-sync"></i>
                    <span data-i18n="refresh_menu">Refresh Menu</span>
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($products_by_category as $category_name => $category_products): ?>
                <div class="category-section">
                    <div class="category-title">
                        <h2><?php echo htmlspecialchars($category_name); ?></h2>
                    </div>

                    <div class="products-grid">
                        <?php foreach ($category_products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php
                                    $image_path = '../' . ($product['image'] ?? '');
                                    $image_exists = !empty($product['image']) && file_exists($image_path);
                                    ?>
                                    <?php if ($image_exists): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>?t=<?php echo time(); ?>"
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <i class="fas fa-utensils" style="display: none;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-utensils"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div>
                                        <div class="product-name" title="<?php echo htmlspecialchars($product['product_name']); ?>">
                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                        </div>
                                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                        <div class="product-stock">
                                            <span data-i18n="available">Available</span>:
                                            <?php echo $product['quantity']; ?>
                                            <span data-i18n="units">units</span>
                                        </div>
                                    </div>

                                    <form method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1"
                                               max="<?php echo $product['quantity']; ?>"
                                               class="quantity-input" required>
                                        <button type="submit" name="add_to_cart" class="add-btn">
                                            <i class="fas fa-cart-plus"></i>
                                            <span data-i18n="add_to_cart">Add to Cart</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
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