<?php
// employee_orders.php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

// Get orders created ONLY by this employee - strict security check
$orders = mysqli_query($conn, "
    SELECT o.*, c.full_name as customer_name,
           (SELECT SUM(od.quantity * p.price) 
            FROM order_detail od 
            JOIN product p ON od.product_id = p.id 
            WHERE od.order_id = o.id) as total_amount
    FROM `order` o
    LEFT JOIN customer c ON o.customer_id = c.id
    WHERE o.employee_id = $employee_id
    ORDER BY o.order_date DESC
    LIMIT 100
");

if (!$orders) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Employee Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-shopping-cart"></i> My Orders</h1>
                <a href="order_create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Order
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Orders Created by You</h2>
                    <span><?php echo mysqli_num_rows($orders); ?> orders</span>
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
                            <?php while($order = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <strong style="color: #E07B39;">
                                        $<?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($orders) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;">No orders created yet</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>