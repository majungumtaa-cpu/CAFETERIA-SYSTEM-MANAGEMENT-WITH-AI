<?php
// user/user_dashboard.php

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

if (!$conn) {
    die("DB connection failed: " . mysqli_connect_error());
}

$customer_id   = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type    = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Recent orders
$recent_orders = mysqli_query($conn, "
    SELECT o.id, o.order_date, o.status,
           SUM(od.quantity * od.price_at_order) as total,
           GROUP_CONCAT(p.product_name SEPARATOR ', ') as items
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    JOIN product p ON od.product_id = p.id
    WHERE o.customer_id = $customer_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 5
");

// Upcoming reservations
$upcoming_reservations = false;
$check_service = mysqli_query($conn, "SHOW TABLES LIKE 'service'");
if ($check_service && mysqli_num_rows($check_service) > 0) {
    $upcoming_reservations = mysqli_query($conn, "
        SELECT r.id, r.reservation_date, s.service_name
        FROM reservation r
        JOIN service s ON r.service_id = s.id
        WHERE r.customer_id = $customer_id AND r.reservation_date >= CURDATE()
        ORDER BY r.reservation_date ASC
        LIMIT 5
    ");
}

// Event registrations
$event_registrations = mysqli_query($conn, "
    SELECT e.event_name, e.event_date, e.description
    FROM event_customer ec
    JOIN event e ON ec.event_id = e.id
    WHERE ec.customer_id = $customer_id AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
    LIMIT 5
");

// Total spent
$total_spent_query = mysqli_query($conn, "
    SELECT SUM(od.quantity * od.price_at_order) as total
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    WHERE o.customer_id = $customer_id AND o.status != 'cancelled'
");
$total_spent = mysqli_fetch_assoc($total_spent_query)['total'] ?? 0;

// Total orders
$total_orders_query = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM `order` WHERE customer_id = $customer_id
");
$total_orders = mysqli_fetch_assoc($total_orders_query)['count'] ?? 0;

$lang = $_SESSION['lang'] ?? 'en';

$statusTranslations = [
    'en' => [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'confirmed'  => 'Confirmed',
        'ready'      => 'Ready'
    ],
    'sw' => [
        'pending'    => 'Inasubiri',
        'processing' => 'Inashughulikiwa',
        'completed'  => 'Imekamilika',
        'cancelled'  => 'Imefutwa',
        'confirmed'  => 'Imethibitishwa',
        'ready'      => 'Iko Tayari'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Cafeteria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: linear-gradient(135deg, rgba(224,123,57,0.05) 0%, rgba(200,106,47,0.05) 100%),
                        url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* ============================================
           TOP BAR — Brand + Cart + LOGOUT
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
           DASHBOARD
           ============================================ */
        .dashboard-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 18px;
        }

        .welcome-section {
            background: linear-gradient(135deg, #E07B39, #C86A2F);
            color: #fff;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .welcome-section h1 {
            margin: 0;
            font-size: 1.5em;
            font-weight: 700;
        }

        .welcome-section p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.9em;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .stat-box {
            background: white;
            padding: 14px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.2s;
        }

        .stat-box:hover { transform: translateY(-2px); }

        .stat-box .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1em;
            color: white;
            flex-shrink: 0;
        }

        .stat-box .stat-value {
            font-size: 1.2em;
            font-weight: 700;
            color: #333;
            line-height: 1.1;
        }

        .stat-box .stat-label {
            color: #888;
            font-size: 0.75em;
            margin-top: 2px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .menu-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .menu-card-inner {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1em;
            flex-shrink: 0;
        }

        .menu-card-text h3 {
            margin: 0 0 3px 0;
            font-size: 0.95em;
            color: #333;
            font-weight: 700;
        }

        .menu-card-text p {
            margin: 0;
            color: #888;
            font-size: 0.75em;
            line-height: 1.3;
        }

        .recent-activity {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 18px;
            margin-bottom: 15px;
        }

        .recent-activity h3 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 1em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .recent-activity h3 i { color: #E07B39; }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-item .item-info {
            flex: 1;
            min-width: 200px;
        }

        .activity-item h4 {
            margin: 0 0 3px 0;
            color: #333;
            font-size: 0.9em;
            font-weight: 600;
        }

        .activity-item .items-list {
            margin: 2px 0;
            color: #666;
            font-size: 0.8em;
        }

        .activity-item .date {
            color: #999;
            font-size: 0.75em;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-processing { background: #cce5ff; color: #004085; }

        .btn-view-all {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 18px;
            background: #E07B39;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-view-all:hover { transform: translateY(-2px); }

        .empty-state {
            text-align: center;
            padding: 25px 15px;
            color: #999;
        }

        .empty-state i {
            font-size: 2em;
            color: #ddd;
            display: block;
            margin-bottom: 8px;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.85em;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @media (max-width: 600px) {
            .welcome-section h1 { font-size: 1.2em; }
            .stats-row { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: 1fr; }
            .top-bar { padding: 10px 15px; }
            .dashboard-container { padding: 12px; }
            .activity-item { flex-direction: column; }
            .logout-btn span { display: none; }
        }
    </style>
</head>
<body>

    <!-- ============================================
         TOP BAR — Brand + Cart + Logout
         ============================================ -->
    <div class="top-bar">
        <div class="brand">
            <i class="fas fa-utensils"></i>
            <span>Cafeteria</span>
        </div>
        <div class="top-bar-actions">

            <!-- Cart Icon -->
            <a href="cart.php" class="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- ✅ LOGOUT BUTTON -->
            <a href="user_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="dashboard-container">

        <!-- FLASH MESSAGE -->
        <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($flash_message); ?>
        </div>
        <?php endif; ?>

        <!-- WELCOME -->
        <div class="welcome-section">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($customer_name); ?>! 👋</h1>
                <p>What would you like to do today?</p>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-icon" style="background: linear-gradient(135deg, #E07B39, #C86A2F);">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <div class="stat-value">$<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $cart_count; ?></div>
                    <div class="stat-label">Items in Cart</div>
                </div>
            </div>
        </div>

        <!-- MENU CARDS -->
        <div class="menu-grid">
            <a href="menu.php" class="menu-card">
                <div class="menu-card-inner">
                    <div class="menu-card-icon" style="background: linear-gradient(135deg, #E07B39, #C86A2F);">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="menu-card-text">
                        <h3>Order Food</h3>
                        <p>Browse our menu</p>
                    </div>
                </div>
            </a>

            <a href="cart.php" class="menu-card">
                <div class="menu-card-inner">
                    <div class="menu-card-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="menu-card-text">
                        <h3>My Cart</h3>
                        <p><?php echo $cart_count; ?> item(s)</p>
                    </div>
                </div>
            </a>

            <a href="events.php" class="menu-card">
                <div class="menu-card-inner">
                    <div class="menu-card-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="menu-card-text">
                        <h3>Events</h3>
                        <p>Upcoming events</p>
                    </div>
                </div>
            </a>

            <a href="feedback.php" class="menu-card">
                <div class="menu-card-inner">
                    <div class="menu-card-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="menu-card-text">
                        <h3>Feedback</h3>
                        <p>Share experience</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- RECENT ORDERS -->
        <div class="recent-activity">
            <h3>
                <i class="fas fa-history"></i>
                Recent Orders
            </h3>

            <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($recent_orders)):
                    $status = strtolower($order['status']);
                    $statusText = $statusTranslations[$lang][$status] ?? ucfirst($status);
                ?>
                    <div class="activity-item">
                        <div class="item-info">
                            <h4>
                                Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                                — <span style="color: #2ecc71;">$<?php echo number_format($order['total'], 2); ?></span>
                            </h4>
                            <div class="items-list">
                                <i class="fas fa-utensils" style="color: #E07B39; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($order['items']); ?>
                            </div>
                            <div class="date">
                                <i class="fas fa-clock"></i>
                                <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No recent orders found.</p>
                    <a href="menu.php" class="btn-view-all" style="margin-top: 10px;">
                        <i class="fas fa-utensils"></i> Browse Menu
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- UPCOMING RESERVATIONS -->
        <?php if ($upcoming_reservations && mysqli_num_rows($upcoming_reservations) > 0): ?>
        <div class="recent-activity">
            <h3>
                <i class="fas fa-calendar"></i>
                Upcoming Reservations
            </h3>
            <?php while ($reservation = mysqli_fetch_assoc($upcoming_reservations)): ?>
                <div class="activity-item">
                    <div class="item-info">
                        <h4><?php echo htmlspecialchars($reservation['service_name']); ?></h4>
                        <div class="date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M j, Y', strtotime($reservation['reservation_date'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- REGISTERED EVENTS -->
        <?php if ($event_registrations && mysqli_num_rows($event_registrations) > 0): ?>
        <div class="recent-activity">
            <h3>
                <i class="fas fa-calendar-alt"></i>
                Registered Events
            </h3>
            <?php while ($event = mysqli_fetch_assoc($event_registrations)): ?>
                <div class="activity-item">
                    <div class="item-info">
                        <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                        <div class="items-list">
                            <?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 80)); ?>...
                        </div>
                        <div class="date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

    </div>

    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>