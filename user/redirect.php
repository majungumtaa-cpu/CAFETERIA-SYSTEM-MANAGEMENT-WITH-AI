<?php
// user/redirect.php - Redirect employee and admin pages to root URLs
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = basename($_SERVER['SCRIPT_NAME']);

// List of employee and admin pages that should be at root level
$redirect_pages = [
    'employee_dashboard.php',
    'employee_login.php',
    'employee_logout.php',
    'employee_profile.php',
    'employee_orders.php',
    'employee_products.php',
    'employee_categories.php',
    'employee_events.php',
    'employee_feedback.php',
    'employee_shifts.php',
    'admin_dashboard.php',
    'admin_profile.php',
    'login.php',
    'logout.php',
    'sidebar.php',
    'header.php',
    'employees.php',
    'customers.php',
    'products.php',
    'categories.php',
    'orders.php',
    'order_create.php',
    'order_edit.php',
    'order_view.php',
    'payments.php',
    'events.php',
    'feedback.php',
    'reports.php',
    'reservations.php',
    'suppliers.php',
    'expenses.php',
    'expense_categories.php',
    'edit_product.php',
    'customer_edit.php'
];

if (in_array($script_name, $redirect_pages)) {
    // Redirect to root URL
    $root_url = str_replace('/user/', '/', $request_uri);
    header('Location: ' . $root_url);
    exit();
}

// If not a redirect page, continue with normal processing
?>