<?php
// user/checkout.php - Checkout

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

$customer_id    = (int)$_SESSION['customer_id'];
$customer_name  = $_SESSION['customer_name'] ?? '';
$customer_phone = $_SESSION['customer_phone'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$error_key = '';
$cart_items = array();
$total = 0;
$has_insufficient_stock = false;

// GET CART ITEMS
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_map('intval', array_keys($_SESSION['cart']));
    $ids_string = implode(',', $product_ids);

    $products = mysqli_query($conn, "
        SELECT p.id, p.product_name, p.price, p.quantity as stock_quantity
        FROM product p
        WHERE p.id IN ($ids_string)
    ");

    if ($products) {
        while ($product = mysqli_fetch_assoc($products)) {
            $product_id = (int)$product['id'];
            $quantity = (int)$_SESSION['cart'][$product_id];

            if ($quantity > $product['stock_quantity']) {
                $has_insufficient_stock = true;
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
                    'subtotal' => $subtotal
                );
            }
        }
    }
}

// GET EMPLOYEES & PAYMENT METHODS
$employees = mysqli_query($conn, "SELECT id, full_name FROM employee ORDER BY full_name");
$payment_methods = mysqli_query($conn, "SELECT id, method_name FROM payment_method ORDER BY method_name");

// HANDLE ORDER PLACEMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_key = 'invalid_request';
    } else {
        $customer_name  = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $notes          = trim($_POST['notes'] ?? '');
        $employee_id    = intval($_POST['employee_id'] ?? 0);
        $payment_method_id = intval($_POST['payment_method_id'] ?? 0);

        if (empty($customer_name) || empty($customer_phone) || empty($address)) {
            $error_key = 'fill_required';
        } elseif ($payment_method_id <= 0) {
            $error_key = 'select_payment';
        } elseif ($has_insufficient_stock) {
            $error_key = 'insufficient_stock';
        } elseif (empty($cart_items)) {
            $error_key = 'empty_cart';
        } else {
            $employee_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM employee");
            $employee_count = mysqli_fetch_assoc($employee_check)['count'];

            $employee_id_sql = 'NULL';
            if ($employee_count > 0) {
                if ($employee_id == 0) {
                    $error_key = 'select_employee';
                } else {
                    $employee_id_sql = intval($employee_id);
                }
            }

            if (empty($error_key)) {
                mysqli_begin_transaction($conn);

                try {
                    $order_stmt = mysqli_prepare($conn, "
                        INSERT INTO `order` 
                        (customer_id, employee_id, order_date, status, address, phone, notes, total_amount) 
                        VALUES (?, ?, NOW(), 'pending', ?, ?, ?, ?)
                    ");
                    mysqli_stmt_bind_param(
                        $order_stmt,
                        "iisssd",
                        $customer_id,
                        $employee_id,
                        $address,
                        $customer_phone,
                        $notes,
                        $total
                    );

                    if (!mysqli_stmt_execute($order_stmt)) {
                        throw new Exception('Order insert failed: ' . mysqli_stmt_error($order_stmt));
                    }
                    $order_id = mysqli_stmt_insert_id($order_stmt);
                    mysqli_stmt_close($order_stmt);

                    $detail_stmt = mysqli_prepare($conn, "
                        INSERT INTO order_detail (order_id, product_id, quantity, price_at_order) 
                        VALUES (?, ?, ?, ?)
                    ");

                    $stock_stmt = mysqli_prepare($conn, "
                        UPDATE product SET quantity = quantity - ? WHERE id = ? AND quantity >= ?
                    ");

                    foreach ($cart_items as $item) {
                        $pid   = (int)$item['id'];
                        $qty   = (int)$item['quantity'];
                        $price = (float)$item['price'];

                        mysqli_stmt_bind_param($detail_stmt, "iiid", $order_id, $pid, $qty, $price);
                        if (!mysqli_stmt_execute($detail_stmt)) {
                            throw new Exception('Order detail failed: ' . mysqli_stmt_error($detail_stmt));
                        }

                        mysqli_stmt_bind_param($stock_stmt, "iii", $qty, $pid, $qty);
                        if (!mysqli_stmt_execute($stock_stmt)) {
                            throw new Exception('Stock update failed: ' . mysqli_stmt_error($stock_stmt));
                        }

                        if (mysqli_stmt_affected_rows($stock_stmt) === 0) {
                            throw new Exception("Stock insufficient for product ID $pid");
                        }
                    }

                    mysqli_stmt_close($detail_stmt);
                    mysqli_stmt_close($stock_stmt);

                    $reference = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
                    $payment_stmt = mysqli_prepare($conn, "
                        INSERT INTO payment 
                        (order_id, payment_method_id, reference, amount, status, created_at) 
                        VALUES (?, ?, ?, ?, 'pending', NOW())
                    ");
                    mysqli_stmt_bind_param(
                        $payment_stmt,
                        "iisd",
                        $order_id,
                        $payment_method_id,
                        $reference,
                        $total
                    );

                    if (!mysqli_stmt_execute($payment_stmt)) {
                        throw new Exception('Payment insert failed: ' . mysqli_stmt_error($payment_stmt));
                    }
                    mysqli_stmt_close($payment_stmt);

                    $_SESSION['customer_name']  = $customer_name;
                    $_SESSION['customer_phone'] = $customer_phone;
                    unset($_SESSION['cart']);

                    mysqli_commit($conn);

                    if (function_exists('log_activity')) {
                        $details = 'customer:' . $customer_name . ';total:' . number_format($total, 2) . ';ref:' . $reference;
                        log_activity($conn, 'add', 'order', $order_id, $details);
                    }

                    $_SESSION['flash_message'] = "Order #" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " placed successfully! Ref: $reference";
                    $_SESSION['flash_type'] = 'success';

                    header('Location: my_orders.php');
                    exit();

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = $e->getMessage();
                }
            }
        }
    }
}

$lang = $_SESSION['lang'] ?? 'en';

$errorTranslations = [
    'en' => [
        'invalid_request'      => 'Invalid request. Please try again.',
        'fill_required'        => 'Please fill in name, phone, and address.',
        'select_payment'       => 'Please select a payment method.',
        'insufficient_stock'   => 'Some items have insufficient stock.',
        'empty_cart'           => 'Your cart is empty.',
        'select_employee'      => 'Please select an employee.'
    ],
    'sw' => [
        'invalid_request'      => 'Ombi si sahihi. Tafadhali jaribu tena.',
        'fill_required'        => 'Tafadhali jaza jina, simu, na anwani.',
        'select_payment'       => 'Tafadhali chagua njia ya malipo.',
        'insufficient_stock'   => 'Baadhi ya bidhaa hazina kiasi cha kutosha.',
        'empty_cart'           => 'Kikapu chako ni tupu.',
        'select_employee'      => 'Tafadhali chagua mfanyakazi.'
    ]
];
$error = $error_key && isset($errorTranslations[$lang][$error_key])
    ? $errorTranslations[$lang][$error_key]
    : $error;

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="checkout_title">Checkout - Cafeteria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../lang.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: linear-gradient(135deg, rgba(224, 123, 57, 0.05) 0%, rgba(200, 106, 47, 0.05) 100%),
                        url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* TOP BAR */
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

        /* CHECKOUT */
        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 15px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 15px;
            transition: background 0.2s;
        }

        .back-btn:hover { background: #5a6268; }

        .checkout-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .checkout-header h1 {
            font-size: 1.6em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .customer-info,
        .order-summary {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 22px;
        }

        .customer-info h3,
        .order-summary h3 {
            margin: 0 0 18px 0;
            color: #333;
            font-size: 1.05em;
            padding-bottom: 10px;
            border-bottom: 2px solid #E07B39;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .customer-info h3 i,
        .order-summary h3 i { color: #E07B39; }

        .form-group { margin-bottom: 15px; }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 0.85em;
        }

        .form-group label i {
            color: #E07B39;
            margin-right: 4px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.95em;
            font-family: inherit;
            background: #f8f9fa;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #E07B39;
            background: white;
            box-shadow: 0 0 0 3px rgba(224, 123, 57, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 70px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .info-box {
            background: #fff8f0;
            border-left: 4px solid #E07B39;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.85em;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9em;
            gap: 10px;
        }

        .summary-item:last-of-type { border-bottom: none; }

        .summary-item .item-name { color: #555; flex: 1; }
        .summary-item .item-price { color: #333; font-weight: 600; white-space: nowrap; }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0 0 0;
            border-top: 2px solid #E07B39;
            margin-top: 10px;
        }

        .summary-total .label {
            font-size: 1em;
            font-weight: 700;
            color: #333;
        }

        .summary-total .amount {
            font-size: 1.5em;
            font-weight: 800;
            color: #E07B39;
        }

        .place-order-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 15px;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .place-order-btn:hover { transform: translateY(-2px); }

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

        .empty-cart h3 {
            color: #666;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .checkout-content { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .top-bar { padding: 10px 15px; }
            .logout-btn span { display: none; }
        }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="brand">
            <i class="fas fa-utensils"></i>
            <span data-i18n="brand">Cafeteria</span>
        </div>
        <div class="top-bar-actions">
            <a href="cart.php" class="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-cart"></i>
            </a>
            <a href="user_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span data-i18n="logout">Logout</span>
            </a>
        </div>
    </div>

    <div class="checkout-container">
        <a href="cart.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span data-i18n="back_to_cart">Back to Cart</span>
        </a>

        <div class="checkout-header">
            <h1>
                <i class="fas fa-credit-card"></i>
                <span data-i18n="checkout_title">Checkout</span>
            </h1>
        </div>

        <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($flash_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <div class="checkout-content">
                <div class="customer-info">
                    <h3>
                        <i class="fas fa-user"></i>
                        <span data-i18n="order_info">Order Information</span>
                    </h3>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <span data-i18n="info_note">Please provide accurate information for delivery.</span>
                    </div>

                    <form method="POST" action="checkout.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_name">
                                    <i class="fas fa-user"></i>
                                    <span data-i18n="full_name">Full Name</span> *
                                </label>
                                <input type="text" id="customer_name" name="customer_name"
                                       value="<?php echo htmlspecialchars($customer_name); ?>"
                                       required
                                       data-i18n="full_name_ph" data-i18n-placeholder
                                       placeholder="Enter your full name">
                            </div>
                            <div class="form-group">
                                <label for="customer_phone">
                                    <i class="fas fa-phone"></i>
                                    <span data-i18n="phone">Phone Number</span> *
                                </label>
                                <input type="tel" id="customer_phone" name="customer_phone"
                                       value="<?php echo htmlspecialchars($customer_phone); ?>"
                                       required
                                       data-i18n="phone_ph" data-i18n-placeholder
                                       placeholder="+255 712 345 678">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">
                                <i class="fas fa-map-marker-alt"></i>
                                <span data-i18n="address">Delivery Address</span> *
                            </label>
                            <input type="text" id="address" name="address"
                                   required
                                   data-i18n="address_ph" data-i18n-placeholder
                                   placeholder="e.g., Kariakoo, Dar es Salaam"
                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="employee_id">
                                <i class="fas fa-user-tie"></i>
                                <span data-i18n="select_employee">Select Employee</span>
                            </label>
                            <select name="employee_id" id="employee_id">
                                <option value="" data-i18n="choose_employee">Choose an employee to handle your order</option>
                                <?php
                                if ($employees && mysqli_num_rows($employees) > 0) {
                                    mysqli_data_seek($employees, 0);
                                    while ($employee = mysqli_fetch_assoc($employees)):
                                ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="payment_method_id">
                                <i class="fas fa-credit-card"></i>
                                <span data-i18n="payment_method">Payment Method</span> *
                            </label>
                            <select name="payment_method_id" id="payment_method_id" required>
                                <option value="" data-i18n="choose_payment">Choose payment method</option>
                                <?php
                                if ($payment_methods && mysqli_num_rows($payment_methods) > 0) {
                                    mysqli_data_seek($payment_methods, 0);
                                    while ($pm = mysqli_fetch_assoc($payment_methods)):
                                ?>
                                    <option value="<?php echo $pm['id']; ?>">
                                        <?php echo htmlspecialchars($pm['method_name']); ?>
                                    </option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-pen"></i>
                                <span data-i18n="order_notes">Order Notes (Optional)</span>
                            </label>
                            <textarea name="notes" id="notes"
                                      data-i18n="order_notes_ph" data-i18n-placeholder
                                      placeholder="Any special instructions..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" name="place_order" class="place-order-btn">
                            <i class="fas fa-shopping-cart"></i>
                            <span data-i18n="place_order">Place Order</span> — $<?php echo number_format($total, 2); ?>
                        </button>
                    </form>
                </div>

                <div class="order-summary">
                    <h3>
                        <i class="fas fa-list"></i>
                        <span data-i18n="order_summary">Order Summary</span>
                    </h3>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <span class="item-name">
                                <?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)
                            </span>
                            <span class="item-price">
                                $<?php echo number_format($item['subtotal'], 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-total">
                        <span class="label" data-i18n="total_amount">Total Amount</span>
                        <span class="amount">$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3 data-i18n="empty_cart_msg">Your cart is empty.</h3>
                <a href="menu.php" class="place-order-btn" style="width: auto; padding: 12px 28px; text-decoration: none; display: inline-flex;">
                    <i class="fas fa-utensils"></i>
                    <span data-i18n="browse_menu">Browse Menu</span>
                </a>
            </div>
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