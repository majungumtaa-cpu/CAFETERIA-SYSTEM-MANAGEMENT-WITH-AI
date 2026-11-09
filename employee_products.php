<?php
// employee_products.php - READ-ONLY access
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_name = $_SESSION['full_name'];

// Get all products - READ-ONLY
$products = mysqli_query($conn, "
    SELECT p.*, mc.category_name 
    FROM product p 
    JOIN menu_category mc ON p.category_id = mc.id 
    ORDER BY p.product_name ASC
    LIMIT 500
");

if (!$products) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Stock status translations
$stockTranslations = [
    'en' => [
        'in_stock'     => 'In Stock',
        'low_stock'    => 'Low Stock',
        'out_of_stock' => 'Out of Stock'
    ],
    'sw' => [
        'in_stock'     => 'Ipo Stock',
        'low_stock'    => 'Stock Chini',
        'out_of_stock' => 'Hakuna Stock'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="ep2_title">Products - Employee Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-box"></i>
                    <span data-i18n="ep2_title">Products</span>
                </h1>
                <span data-i18n="ep2_subtitle">View all available products</span>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 data-i18n="ep2_available">Available Products</h2>
                    <span>
                        <?php echo mysqli_num_rows($products); ?>
                        <span data-i18n="ep2_products">products</span>
                    </span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="ep2_product_name">Product Name</th>
                                <th data-i18n="ep2_category">Category</th>
                                <th data-i18n="ep2_price">Price</th>
                                <th data-i18n="ep2_stock">Stock</th>
                                <th data-i18n="ep2_status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = mysqli_fetch_assoc($products)): 
                                $stock_color = $product['quantity'] > 10 ? '#2ecc71' : ($product['quantity'] > 0 ? '#f39c12' : '#e74c3c');
                                $stock_key = $product['quantity'] > 10 ? 'in_stock' : ($product['quantity'] > 0 ? 'low_stock' : 'out_of_stock');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <strong style="color: #E07B39;">
                                        $<?php echo number_format($product['price'], 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo $product['quantity']; ?>
                                    <span data-i18n="ep2_units">units</span>
                                </td>
                                <td>
                                    <span style="color: <?php echo $stock_color; ?>; font-weight: 600;">
                                        <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                                        <?php echo $stockTranslations[$lang][$stock_key]; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($products) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;" data-i18n="ep2_no_products">No products available</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        window.addEventListener('languageChanged', () => {
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
        });
    </script>
</body>
</html>