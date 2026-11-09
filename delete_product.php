<?php
// delete_product.php - Admin only
require_once 'config.php';
check_login();

// KINGA: ADMIN PEKEE
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    $_SESSION['flash_message'] = 'Access denied! Only admins can delete products.';
    $_SESSION['flash_type'] = 'error';
    header('Location: employee_dashboard.php');
    exit();
}

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Fetch product to delete image
$result = mysqli_query($conn, "SELECT * FROM product WHERE id = $product_id");

if ($result && $product = mysqli_fetch_assoc($result)) {
    // Futa picha kama ipo
    if (!empty($product['image']) && file_exists($product['image'])) {
        @unlink($product['image']);
    }
    
    // Futa order_details zinazohusiana (kama unataka)
    // mysqli_query($conn, "DELETE FROM order_detail WHERE product_id = $product_id");
    
    // Futa product
    $delete = "DELETE FROM product WHERE id = $product_id";
    
    if (mysqli_query($conn, $delete)) {
        $_SESSION['flash_message'] = 'Product deleted successfully!';
        $_SESSION['flash_type'] = 'success';
        if (function_exists('log_activity')) {
            log_activity($conn, 'delete', 'product', $product_id, 'Product deleted');
        }
    } else {
        $_SESSION['flash_message'] = 'Error deleting product: ' . mysqli_error($conn);
        $_SESSION['flash_type'] = 'error';
    }
} else {
    $_SESSION['flash_message'] = 'Product not found!';
    $_SESSION['flash_type'] = 'error';
}

header('Location: products.php');
exit;
?>