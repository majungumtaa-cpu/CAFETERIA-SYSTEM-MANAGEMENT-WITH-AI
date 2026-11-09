<?php
// user/cart.php

// ================================================
// ANTI-CACHE HEADERS
// ================================================
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

// ================================================
// INIT CART
// ================================================
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ================================================
// CSRF TOKEN
// ================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ================================================
// HANDLE REMOVE VIA GET
// ================================================
if (isset($_GET['remove_id'])) {
    $product_id = (int)$_GET['remove_id'];
    unset($_SESSION['cart'][$product_id]);
    $_SESSION['flash_message'] = 'Item removed from cart.';
    $_SESSION['flash_type'] = 'success';
    header('Location: cart.php');
    exit();
}

// ================================================
// HANDLE CART UPDATES (POST)
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch!');
    }

    if (isset($_POST['update_cart'])) {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $product_id => $quantity) {
                $product_id = (int)$product_id;
                $quantity = (int)$quantity;
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
            }
        }
        $_SESSION['flash_message'] = 'Cart updated successfully.';
        $_SESSION['flash_type'] = 'success';
        header('Location: cart.php');
        exit();
    } elseif (isset($_POST['clear_cart'])) {
        unset($_SESSION['cart']);
        $_SESSION['cart'] = [];
        $_SESSION['flash_message'] = 'Cart cleared.';
        $_SESSION['flash_type'] = 'success';
        header('Location: cart.php');
        exit();
    } elseif (isset($_POST['place_order'])) {
        header('Location: checkout.php');
        exit();
    }
}

// ================================================
// GET CART ITEMS
// ================================================
$cart_items = array();
$total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_map('intval', array_keys($_SESSION['cart']));
    $ids_string = implode(',', $product_ids);

    $products = mysqli_query($conn, "
        SELECT p.id, p.product_name, p.price, p.quantity as stock_quantity, p.image,
               mc.category_name
        FROM product p
        JOIN menu_category mc ON p.category_id = mc.id
        WHERE p.id IN ($ids_string)
    ");

    if (!$products) {
        die("<b>SQL Error (Cart):</b> " . mysqli_error($conn));
    }

    while ($product = mysqli_fetch_assoc($products)) {
        $product_id = (int)$product['id'];
        $quantity = (int)$_SESSION['cart'][$product_id];

        if ($quantity > $product['stock_quantity']) {
            $quantity = (int)$product['stock_quantity'];
            $_SESSION['cart'][$product_id] = $quantity;
        }

        if ($quantity > 0) {
            $subtotal = $quantity * $product['price'];
            $total += $subtotal;

            $cart_items[] = array(
                'id'       => $product_id,
                'name'     => $product['product_name'],
                'price'    => $product['price'],
                'quantity' => $quantity,
                'stock'    => $product['stock_quantity'],
                'subtotal' => $subtotal,
                'category' => $product['category_name'],
                'image'    => $product['image']
            );
        }
    }
}

$cart_count = count($cart_items);

// Flash message
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Cafeteria</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, rgba(224, 123, 57, 0.05) 0%, rgba(200, 106, 47, 0.05) 100%),
                        url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .cart-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 15px;
        }

        /* ============================================
           HEADER
           ============================================ */
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cart-header h1 {
            margin: 0;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85em;
            font-weight: 600;
            font-family: inherit;
            transition: transform 0.2s;
        }

        .btn:hover { transform: translateY(-2px); }

        .btn-primary {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-secondary:hover { background: rgba(255,255,255,0.35); }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover { background: #c82333; }

        /* ============================================
           ALERT
           ============================================ */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #2ecc71; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #e74c3c; }

        /* ============================================
           EMPTY CART
           ============================================ */
        .empty-cart {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .empty-cart i {
            font-size: 3.5em;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .empty-cart h2 {
            color: #666;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .empty-cart p {
            color: #999;
            margin-bottom: 20px;
        }

        /* ============================================
           CART TABLE
           ============================================ */
        .cart-table-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 15px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th,
        .cart-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-table th {
            background: #f8f9fa;
            font-weight: 700;
            color: #555;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details h4 {
            margin: 0 0 3px 0;
            color: #333;
            font-size: 0.95em;
        }

        .product-details small {
            color: #999;
            font-size: 0.75em;
        }

        .quantity-input {
            width: 60px;
            padding: 6px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            text-align: center;
            font-size: 0.9em;
            font-family: inherit;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #E07B39;
        }

        .price-cell {
            color: #333;
            font-weight: 600;
            font-size: 0.9em;
        }

        .subtotal-cell {
            color: #2ecc71;
            font-weight: 700;
            font-size: 0.95em;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: transform 0.2s;
        }

        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .update-cart-btn-wrap {
            text-align: center;
            margin-bottom: 20px;
        }

        /* ============================================
           CART TOTAL
           ============================================ */
        .cart-total {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 20px 25px;
            text-align: right;
        }

        .cart-total h3 {
            margin: 0 0 8px 0;
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .total-amount {
            font-size: 2em;
            font-weight: 800;
            color: #E07B39;
            margin-bottom: 15px;
            line-height: 1;
        }

        .checkout-section { margin-top: 10px; }

        .checkout-btn {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
            text-decoration: none;
        }

        .checkout-btn:hover { transform: translateY(-2px); }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 600px) {
            .cart-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .cart-header h1 { font-size: 1.1em; }
            .cart-actions { width: 100%; }

            .cart-table th,
            .cart-table td {
                padding: 8px 8px;
                font-size: 0.8em;
            }

            .product-image {
                width: 40px;
                height: 40px;
                font-size: 1em;
            }

            .product-details h4 { font-size: 0.85em; }

            .total-amount { font-size: 1.5em; }

            .checkout-btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
            <div class="cart-actions">
                <a href="menu.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
                <?php if ($cart_count > 0): ?>
                    <form method="POST" style="display: inline;"
                          onsubmit="return confirm('Are you sure you want to clear your cart?')">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="clear_cart" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- FLASH MESSAGE -->
        <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($flash_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($cart_count == 0): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Add some delicious items from our menu!</p>
                <a href="menu.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.95em;">
                    <i class="fas fa-utensils"></i> Browse Menu
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="cart-table-wrapper">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image">
                                                <?php if (!empty($item['image']) && file_exists('../' . $item['image'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>"
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-utensils"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <small><?php echo htmlspecialchars($item['category']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price-cell">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <input type="number"
                                               name="quantities[<?php echo $item['id']; ?>]"
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1"
                                               max="<?php echo $item['stock']; ?>"
                                               class="quantity-input">
                                    </td>
                                    <td class="subtotal-cell">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    <td>
                                        <a href="cart.php?remove_id=<?php echo $item['id']; ?>"
                                           class="remove-btn"
                                           onclick="return confirm('Remove this item from cart?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="update-cart-btn-wrap">
                    <button type="submit" name="update_cart" class="btn btn-primary" style="padding: 10px 24px;">
                        <i class="fas fa-sync"></i> Update Cart
                    </button>
                </div>
            </form>

            <div class="cart-total">
                <h3>Cart Total</h3>
                <div class="total-amount">$<?php echo number_format($total, 2); ?></div>

                <div class="checkout-section">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="place_order" class="checkout-btn">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) { window.location.reload(); }
        });
    </script>
</body>
</html>