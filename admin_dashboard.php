<?php
// admin_dashboard.php (Admin Dashboard)

// ================================================
// ANTI-CACHE HEADERS
// ================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'config.php';
check_login();

// Prevent employees from accessing admin dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

// ================================================
// FLASH MESSAGE
// ================================================
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ================================================
// STATISTICS
// ================================================
$today = date('Y-m-d');
$stats = array();

// Total orders today
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE DATE(order_date) = '$today'");
$row = mysqli_fetch_assoc($result);
$stats['orders_today'] = $row['count'] ?? 0;

// Today's revenue
$result = mysqli_query($conn, "
    SELECT SUM(od.quantity * od.price_at_order) as revenue
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    WHERE DATE(o.order_date) = '$today'
");
$row = mysqli_fetch_assoc($result);
$stats['revenue_today'] = $row['revenue'] ?? 0;

// Total revenue (all time)
$result = mysqli_query($conn, "
    SELECT SUM(od.quantity * od.price_at_order) as revenue
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    WHERE o.status != 'cancelled'
");
$row = mysqli_fetch_assoc($result);
$stats['revenue_total'] = $row['revenue'] ?? 0;

// Total customers
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM customer");
$row = mysqli_fetch_assoc($result);
$stats['total_customers'] = $row['count'] ?? 0;

// Low stock products
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM product WHERE quantity < 10");
$row = mysqli_fetch_assoc($result);
$stats['low_stock'] = $row['count'] ?? 0;

// Pending orders
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE status = 'pending'");
$row = mysqli_fetch_assoc($result);
$stats['pending_orders'] = $row['count'] ?? 0;

// ================================================
// RECENT ORDERS
// ================================================
$recent_orders = mysqli_query($conn, "
    SELECT o.id, c.full_name, o.order_date, o.status,
           SUM(od.quantity * od.price_at_order) as total
    FROM `order` o
    LEFT JOIN customer c ON o.customer_id = c.id
    JOIN order_detail od ON o.id = od.order_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 5
");

if (!$recent_orders) {
    die("<b>SQL Error (Recent Orders):</b> " . mysqli_error($conn));
}

// ================================================
// LOW STOCK ITEMS
// ================================================
$low_stock_items = mysqli_query($conn, "
    SELECT id, product_name, quantity
    FROM product
    WHERE quantity < 10
    ORDER BY quantity ASC
    LIMIT 5
");

if (!$low_stock_items) {
    die("<b>SQL Error (Low Stock):</b> " . mysqli_error($conn));
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Status translations
$statusTranslations = [
    'en' => [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'confirmed'  => 'Confirmed',
        'ready'      => 'Ready',
        'critical'   => 'Critical',
        'low'        => 'Low'
    ],
    'sw' => [
        'pending'    => 'Inasubiri',
        'processing' => 'Inashughulikiwa',
        'completed'  => 'Imekamilika',
        'cancelled'  => 'Imefutwa',
        'confirmed'  => 'Imethibitishwa',
        'ready'      => 'Iko Tayari',
        'critical'   => 'Hatari',
        'low'        => 'Chini'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="admin_dashboard_title">Admin Dashboard - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
    <style>
        body {
            background-color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    <span data-i18n="admin_dashboard_title">Admin Dashboard</span>
                </h1>
                <span>
                    <span data-i18n="admin_welcome">Welcome</span>,
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
                </span>
            </div>

            <!-- FLASH MESSAGE -->
            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?>">
                <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['orders_today']; ?></div>
                    <div class="stat-label" data-i18n="admin_today_orders">Today's Orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($stats['revenue_today'], 2); ?></div>
                    <div class="stat-label" data-i18n="admin_today_revenue">Today's Revenue</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_customers']; ?></div>
                    <div class="stat-label" data-i18n="admin_total_customers">Total Customers</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-label" data-i18n="admin_low_stock">Low Stock Items</div>
                </div>
            </div>

            <!-- STATS ROW 2 -->
            <div class="stats-grid" style="margin-top: -10px;">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($stats['revenue_total'], 2); ?></div>
                    <div class="stat-label" data-i18n="admin_total_revenue">Total Revenue (All Time)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #3498db;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label" data-i18n="admin_pending_orders">Pending Orders</div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-history"></i>
                        <span data-i18n="admin_recent_orders">Recent Orders</span>
                    </h2>
                    <a href="orders.php" class="btn btn-primary" data-i18n="admin_view_all">View All</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="admin_order_no">Order #</th>
                                <th data-i18n="admin_customer">Customer</th>
                                <th data-i18n="admin_date">Date</th>
                                <th data-i18n="admin_total">Total</th>
                                <th data-i18n="admin_status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($recent_orders)):
                                    $status = strtolower($order['status'] ?? 'pending');
                                    $statusText = $statusTranslations[$lang][$status] ?? ucfirst($status);
                                ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $status; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-inbox" style="font-size: 2em; color: #ddd; display: block; margin-bottom: 10px;"></i>
                                        <span data-i18n="admin_no_orders">No orders today</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-exclamation-circle"></i>
                        <span data-i18n="admin_low_stock_alert">Low Stock Alert</span>
                    </h2>
                    <a href="products.php" class="btn btn-primary" data-i18n="admin_manage_inventory">Manage Inventory</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="admin_product">Product</th>
                                <th data-i18n="admin_stock_quantity">Stock Quantity</th>
                                <th data-i18n="admin_status">Status</th>
                                <th data-i18n="admin_action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($low_stock_items) > 0): ?>
                                <?php while($item = mysqli_fetch_assoc($low_stock_items)):
                                    $status_class = $item['quantity'] < 5 ? 'status-inactive' : 'status-pending';
                                    $status_key   = $item['quantity'] < 5 ? 'critical' : 'low';
                                    $status_text  = $statusTranslations[$lang][$status_key] ?? ucfirst($status_key);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                            <span data-i18n="admin_update">Update</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-check-circle" style="font-size: 2em; color: #2ecc71; display: block; margin-bottom: 10px;"></i>
                                        <span data-i18n="admin_all_stocked">All products are well stocked</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ================================================
         SCRIPT YA KUZUIA "Confirm Form Resubmission"
         ================================================ -->
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted ||
                (window.performance &&
                 window.performance.getEntriesByType &&
                 window.performance.getEntriesByType("navigation")[0] &&
                 window.performance.getEntriesByType("navigation")[0].type === "back_forward")) {
                window.location.reload();
            }
        });

        // ✅ Re-apply translations when language changes
        window.addEventListener('languageChanged', () => {
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
        });
    </script>
</body>
</html>