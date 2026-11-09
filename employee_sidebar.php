<?php
// employee_sidebar.php
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <li>
            <a href="employee_dashboard.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_dashboard.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span data-i18n="emp_sidebar_dashboard">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="employee_shifts.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_shifts.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-clock"></i>
                <span data-i18n="emp_sidebar_shifts">My Shifts</span>
            </a>
        </li>
        <li>
            <a href="employee_orders.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_orders.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-shopping-cart"></i>
                <span data-i18n="emp_sidebar_orders">My Orders</span>
            </a>
        </li>
        <li>
            <a href="employee_products.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_products.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-box"></i>
                <span data-i18n="emp_sidebar_products">Products</span>
            </a>
        </li>
        <li>
            <a href="employee_categories.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_categories.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-list"></i>
                <span data-i18n="emp_sidebar_categories">Categories</span>
            </a>
        </li>
        <li>
            <a href="employee_events.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_events.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-calendar-alt"></i>
                <span data-i18n="emp_sidebar_events">Events</span>
            </a>
        </li>
        <li>
            <a href="employee_profile.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'employee_profile.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-user-cog"></i>
                <span data-i18n="emp_sidebar_profile">My Profile</span>
            </a>
        </li>
        <li style="border-top: 1px solid rgba(255,255,255,0.2); margin-top: 10px; padding-top: 10px;">
            <a href="employee_logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span data-i18n="logout">Logout</span>
            </a>
        </li>
    </nav>
</aside>

<script>
    // Re-apply translations when language changes
    window.addEventListener('languageChanged', () => {
        if (typeof applyTranslations === 'function') {
            applyTranslations();
        }
    });
</script>