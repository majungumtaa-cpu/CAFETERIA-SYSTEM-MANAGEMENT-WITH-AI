<?php
// employee_dashboard.php

// ================================================
// ANTI-CACHE HEADERS (LAZIMA ziwe mwanzoni kabisa)
// ================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'config.php';

// Check if user is logged in as employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

// Get employee statistics
$today = date('Y-m-d');

// Orders created by this employee
$today_orders = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM `order` 
    WHERE employee_id = $employee_id AND DATE(order_date) = '$today'
");
$today_count = mysqli_fetch_assoc($today_orders)['count'];

// Revenue from orders by this employee
$today_revenue = mysqli_query($conn, "
    SELECT SUM(od.quantity * p.price) as revenue
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    JOIN product p ON od.product_id = p.id
    WHERE o.employee_id = $employee_id AND DATE(o.order_date) = '$today'
");
$revenue = mysqli_fetch_assoc($today_revenue)['revenue'] ?? 0;

// Total orders by this employee
$total_orders = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM `order` WHERE employee_id = $employee_id
");
$total_count = mysqli_fetch_assoc($total_orders)['count'];

// Total revenue by this employee (all time)
$total_revenue_query = mysqli_query($conn, "
    SELECT SUM(od.quantity * p.price) as total 
    FROM `order` o 
    JOIN order_detail od ON o.id = od.order_id 
    JOIN product p ON od.product_id = p.id 
    WHERE o.employee_id = $employee_id
");
$total_revenue = mysqli_fetch_assoc($total_revenue_query)['total'] ?? 0;

// Average order value
$avg_order_value = $total_count > 0 ? $total_revenue / $total_count : 0;

// Recent orders by this employee
$recent_orders = mysqli_query($conn, "
    SELECT o.id, c.full_name, o.order_date, 
           SUM(od.quantity * p.price) as total
    FROM `order` o
    JOIN customer c ON o.customer_id = c.id
    JOIN order_detail od ON o.id = od.order_id
    JOIN product p ON od.product_id = p.id
    WHERE o.employee_id = $employee_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 10
");

// Store row count BEFORE looping (kwa sababu mysqli_fetch_assoc inaimaliza result set)
$recent_orders_count = mysqli_num_rows($recent_orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: white;
        }

        .employee-header {
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 2rem;
            margin: -20px -20px 20px -20px;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid var(--border);
        }

        .employee-header h1 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .employee-info {
            background: rgba(56, 189, 248, 0.1);
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border);
        }

        .employee-info p {
            margin: 0;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="employee-header">
                <h1><i class="fas fa-user-tie"></i> Employee Dashboard</h1>
                <div class="employee-info">
                    <div>
                        <p><strong>Welcome,</strong> <?php echo htmlspecialchars($employee_name); ?>!</p>
                        <p><small>Employee ID: <?php echo $employee_id; ?></small></p>
                    </div>
                    <a href="employee_logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo $today_count; ?></div>
                    <div class="stat-label">Today's Orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($revenue, 2); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_count; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($avg_order_value, 2); ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Your Recent Orders</h2>
                    <span><?php echo $recent_orders_count; ?> orders</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><strong style="color: #2ecc71;">$<?php echo number_format($order['total'], 2); ?></strong></td>
                                <td>
                                    <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary" style="padding: 5px 10px;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($recent_orders_count == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">No orders yet</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-tasks"></i> Quick Actions</h2>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; padding: 15px;">
                    <a href="order_create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Order
                    </a>
                    <a href="employee_orders.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> View My Orders
                    </a>
                    <a href="employee_shifts.php" class="btn btn-secondary">
                        <i class="fas fa-clock"></i> My Shifts
                    </a>
                    <a href="employee_products.php" class="btn btn-secondary">
                        <i class="fas fa-box"></i> View Products
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- ================================================
         SCRIPT YA KUZUIA "Confirm Form Resubmission"
         ================================================ -->
    <script>
        // Kama mtumiaji anarudi kwa back button (bfcache au history navigation)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted ||
                (window.performance &&
                 window.performance.getEntriesByType &&
                 window.performance.getEntriesByType("navigation")[0] &&
                 window.performance.getEntriesByType("navigation")[0].type === "back_forward")) {
                // Reload page ili kupata data safi (GET request mpya)
                window.location.reload();
            }
        });
    </script>
</body>
</html>