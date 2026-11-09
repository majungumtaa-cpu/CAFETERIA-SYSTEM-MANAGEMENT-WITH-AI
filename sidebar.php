<?php
// sidebar.php - Updated with Manage Shifts
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="customers.php" class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Customers
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Product Categories
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Products
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
            </li>
            <li>
                <a href="payments.php" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </li>
            <li>
                <a href="employees.php" class="<?php echo $current_page == 'employees.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i> Employees
                </a>
            </li>
            <!-- ========== LINK MPYA: MANAGE SHIFTS ========== -->
            <li>
                <a href="admin_shifts.php" class="<?php echo $current_page == 'admin_shifts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Manage Shifts
                </a>
            </li>
            <!-- ========== MWISHO WA LINK MPYA ========== -->
            <li>
                <a href="suppliers.php" class="<?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Suppliers
                </a>
            </li>
            <li>
                <a href="expense_categories.php" class="<?php echo $current_page == 'expense_categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave"></i> Expense Categories
                </a>
            </li>
            <li>
                <a href="expenses.php" class="<?php echo $current_page == 'expenses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i> Expenses
                </a>
            </li>
            <li>
                <a href="events.php" class="<?php echo $current_page == 'events.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Events
                </a>
            </li>
            <li>
                <a href="feedback.php" class="<?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i> Feedback
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li>
                <a href="activity_log.php" class="<?php echo $current_page == 'activity_log.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Activity Log
                </a>
            </li>
        </ul>
    </nav>
</aside>