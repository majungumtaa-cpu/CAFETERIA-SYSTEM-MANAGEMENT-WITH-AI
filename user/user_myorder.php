<?php
// ================================================
// user/my_orders.php - Customer Order History (USD)
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

$customer_id   = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? '';

// ================================================
// PAGINATION & FILTERS
// ================================================
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$status_filter = trim($_GET['status'] ?? '');

$where = "WHERE o.customer_id = $customer_id";
$params = [];
$types = "";

if (!empty($status_filter) && in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// ================================================
// COUNT TOTAL ORDERS
// ================================================
$count_sql = "SELECT COUNT(*) as total FROM `order` o $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

$total_pages = max(1, ceil($total_orders / $per_page));

// ================================================
// GET ORDERS
// ================================================
$orders_sql = "
    SELECT 
        o.id, o.order_date, o.status, o.address, o.phone, o.notes,
        o.total_amount,
        pm.method_name,
        p.status as payment_status,
        p.reference,
        e.full_name as employee_name
    FROM `order` o
    LEFT JOIN payment p ON o.id = p.order_id
    LEFT JOIN payment_method pm ON p.payment_method_id = pm.id
    LEFT JOIN employee e ON o.employee_id = e.id
    $where
    ORDER BY o.order_date DESC
    LIMIT $per_page OFFSET $offset
";

$orders_stmt = $conn->prepare($orders_sql);
if (!empty($params)) {
    $orders_stmt->bind_param($types, ...$params);
}
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

$orders = [];
$order_ids = [];
while ($order = $orders_result->fetch_assoc()) {
    $orders[$order['id']] = $order;
    $order_ids[] = (int)$order['id'];
}
$orders_stmt->close();

// ================================================
// GET ITEMS FOR ALL ORDERS
// ================================================
if (!empty($order_ids)) {
    $ids_string = implode(',', $order_ids);

    $items_sql = "
        SELECT od.order_id, od.quantity, od.price_at_order,
               p.product_name, p.price, p.image
        FROM order_detail od
        JOIN product p ON od.product_id = p.id
        WHERE od.order_id IN ($ids_string)
        ORDER BY od.id ASC
    ";

    $items_result = mysqli_query($conn, $items_sql);
    if ($items_result) {
        while ($item = mysqli_fetch_assoc($items_result)) {
            $orders[$item['order_id']]['items'][] = $item;
        }
    }
}

foreach ($orders as $id => $order) {
    if (!isset($orders[$id]['items'])) {
        $orders[$id]['items'] = [];
    }
}

// ================================================
// FLASH MESSAGE
// ================================================
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Status translations
$statusTranslations = [
    'en' => [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'paid'       => 'Paid',
        'unpaid'     => 'Unpaid'
    ],
    'sw' => [
        'pending'    => 'Inasubiri',
        'processing' => 'Inashughulikiwa',
        'completed'  => 'Imekamilika',
        'cancelled'  => 'Imefutwa',
        'paid'       => 'Imelipwa',
        'unpaid'     => 'Haijalipwa'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="mo_title">My Orders - Cafeteria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../lang.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: linear-gradient(135deg, rgba(224,123,57,0.05) 0%, rgba(200,106,47,0.05) 100%),
                        url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 20px 15px;
        }

        .container-orders {
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-header-orders {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 18px 22px;
            border-radius: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .page-header-orders h1 {
            margin: 0;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .btn-back:hover { background: rgba(255, 255, 255, 0.35); }

        .filter-bar {
            background: white;
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 18px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 7px 14px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.8em;
            font-weight: 600;
            background: #f0f0f0;
            color: #555;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .filter-btn:hover { background: #e0e0e0; }
        .filter-btn.active { background: #E07B39; color: white; }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 15px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .order-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,0.1); }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background: #f8f9fa;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-header-left { display: flex; flex-direction: column; gap: 3px; }

        .order-id { font-weight: 700; color: #E07B39; font-size: 1em; }

        .order-date {
            font-size: 0.8em;
            color: #888;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .order-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #fff3cd; color: #856404; }

        .order-body { padding: 18px; }

        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }

        .order-info-item { font-size: 0.85em; color: #666; }

        .order-info-item strong {
            color: #333;
            display: inline-block;
            min-width: 90px;
        }

        .order-info-item i { color: #E07B39; margin-right: 5px; }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .order-items-table th,
        .order-items-table td {
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.85em;
        }

        .order-items-table th {
            background: #fafafa;
            color: #888;
            font-weight: 600;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .order-items-table .product-name { color: #333; font-weight: 600; }
        .order-items-table .product-price { color: #E07B39; font-weight: 700; }

        .order-total {
            text-align: right;
            padding-top: 12px;
            font-size: 1.2em;
            font-weight: 700;
            color: #E07B39;
            border-top: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-total .label {
            color: #666;
            font-size: 0.7em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .empty-orders {
            background: white;
            text-align: center;
            padding: 60px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .empty-orders i {
            font-size: 3.5em;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .empty-orders h3 {
            color: #666;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .empty-orders p { color: #999; margin-bottom: 20px; }

        .btn-primary-lg {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-primary-lg:hover { transform: translateY(-2px); }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: 600;
            background: white;
            color: #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.06);
            transition: all 0.2s;
            min-width: 38px;
            text-align: center;
        }

        .pagination a:hover {
            background: #E07B39;
            color: white;
            transform: translateY(-2px);
        }

        .pagination .active {
            background: #E07B39;
            color: white;
            font-weight: 700;
        }

        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
            opacity: 0.5;
        }

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

        @media (max-width: 600px) {
            .order-info-grid { grid-template-columns: 1fr; }
            .page-header-orders h1 { font-size: 1.1em; }
            .order-items-table th,
            .order-items-table td { padding: 6px 5px; font-size: 0.75em; }
            .order-total { font-size: 1em; }
            .pagination a, .pagination span { padding: 6px 10px; font-size: 0.75em; min-width: 32px; }
        }
    </style>
</head>
<body>
    <div class="container-orders">
        <div class="page-header-orders">
            <h1>
                <i class="fas fa-receipt"></i>
                <span data-i18n="mo_title">My Orders</span>
            </h1>
            <a href="user_dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span data-i18n="mo_back_dashboard">Back to Dashboard</span>
            </a>
        </div>

        <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($flash_message); ?>
        </div>
        <?php endif; ?>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <strong style="font-size: 0.85em; color: #666; margin-right: 5px;">
                <i class="fas fa-filter"></i>
                <span data-i18n="mo_filter">Filter</span>:
            </strong>
            <a href="my_orders.php" class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">
                <span data-i18n="mo_all">All</span> (<?php echo $total_orders; ?>)
            </a>
            <a href="?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                <span data-i18n="mo_pending">Pending</span>
            </a>
            <a href="?status=processing" class="filter-btn <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                <span data-i18n="mo_processing">Processing</span>
            </a>
            <a href="?status=completed" class="filter-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                <span data-i18n="mo_completed">Completed</span>
            </a>
            <a href="?status=cancelled" class="filter-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                <span data-i18n="mo_cancelled">Cancelled</span>
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-inbox"></i>
                <h3>
                    <?php if (!empty($status_filter)): ?>
                        <span data-i18n="mo_no_status_orders">No orders with this status</span>
                    <?php else: ?>
                        <span data-i18n="mo_no_orders">No orders yet</span>
                    <?php endif; ?>
                </h3>
                <p data-i18n="mo_start_shopping">Start shopping to see your orders here!</p>
                <a href="menu.php" class="btn-primary-lg">
                    <i class="fas fa-utensils"></i>
                    <span data-i18n="mo_browse_menu">Browse Menu</span>
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $status = strtolower($order['status'] ?? 'pending');
                $payment_status = strtolower($order['payment_status'] ?? 'unpaid');
                $statusText = $statusTranslations[$lang][$status] ?? ucfirst($status);
                $isPaid = in_array($payment_status, ['completed','paid']);
                $payStatusText = $isPaid
                    ? $statusTranslations[$lang]['paid']
                    : $statusTranslations[$lang][$payment_status] ?? ucfirst($payment_status);
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-header-left">
                            <span class="order-id">
                                <span data-i18n="mo_order">Order</span>
                                #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                            </span>
                            <span class="order-date">
                                <i class="fas fa-clock"></i>
                                <?php echo date('M d, Y - g:i A', strtotime($order['order_date'])); ?>
                            </span>
                        </div>
                        <div class="order-header-right">
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo $statusText; ?>
                            </span>
                            <span class="status-badge status-<?php echo $isPaid ? 'paid' : 'unpaid'; ?>">
                                <i class="fas fa-<?php echo $isPaid ? 'check-circle' : 'clock'; ?>"></i>
                                <?php echo $payStatusText; ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-body">
                        <div class="order-info-grid">
                            <div class="order-info-item">
                                <strong>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span data-i18n="mo_delivery">Delivery</span>:
                                </strong>
                                <?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?>
                            </div>
                            <div class="order-info-item">
                                <strong>
                                    <i class="fas fa-phone"></i>
                                    <span data-i18n="mo_phone">Phone</span>:
                                </strong>
                                <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                            </div>
                            <div class="order-info-item">
                                <strong>
                                    <i class="fas fa-user-tie"></i>
                                    <span data-i18n="mo_employee">Employee</span>:
                                </strong>
                                <?php echo htmlspecialchars($order['employee_name'] ?? 'Not assigned'); ?>
                            </div>
                            <div class="order-info-item">
                                <strong>
                                    <i class="fas fa-credit-card"></i>
                                    <span data-i18n="mo_payment">Payment</span>:
                                </strong>
                                <?php echo htmlspecialchars($order['method_name'] ?? 'Not paid'); ?>
                                <?php if (!empty($order['reference'])): ?>
                                    <br><small style="color: #999; margin-left: 95px;">
                                        <span data-i18n="mo_ref">Ref</span>: <?php echo htmlspecialchars($order['reference']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th data-i18n="mo_product">Product</th>
                                    <th data-i18n="mo_qty">Qty</th>
                                    <th data-i18n="mo_price">Price</th>
                                    <th data-i18n="mo_subtotal">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($order['items'])): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #999; padding: 15px;">
                                            <span data-i18n="mo_no_items">No items</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <?php
                                        $unit_price = ($item['price_at_order'] > 0) ? $item['price_at_order'] : $item['price'];
                                        $subtotal = $item['quantity'] * $unit_price;
                                        ?>
                                        <tr>
                                            <td class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo intval($item['quantity']); ?></td>
                                            <td>$<?php echo number_format($unit_price, 2); ?></td>
                                            <td class="product-price">$<?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="order-total">
                            <span class="label" data-i18n="mo_total">Total</span>:
                            <span>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1): ?>
                        <a href="?page=1<?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="disabled">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="disabled">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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