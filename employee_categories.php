 <?php
// employee_categories.php - READ-ONLY access
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_name = $_SESSION['full_name'];

// Get all categories - READ-ONLY
$categories = mysqli_query($conn, "
    SELECT mc.*, COUNT(p.id) as product_count
    FROM menu_category mc
    LEFT JOIN product p ON mc.id = p.category_id
    GROUP BY mc.id
    ORDER BY mc.category_name ASC
    LIMIT 500
");

if (!$categories) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="ec_title">Categories - Employee Portal</title>
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
                    <i class="fas fa-list"></i>
                    <span data-i18n="ec_title">Product Categories</span>
                </h1>
                <span data-i18n="ec_subtitle">Browse product categories</span>
            </div>

            <div class="stats-grid">
                <?php while($category = mysqli_fetch_assoc($categories)): ?>
                <div class="stat-card" style="cursor: pointer; text-align: center;">
                    <div class="stat-icon" style="color: #E07B39; font-size: 2.5rem;">
                        <i class="fas fa-tag"></i>
                    </div>
                    <h3 style="color: #333; margin: 15px 0 5px 0;">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </h3>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?php echo $category['product_count']; ?>
                    </div>
                    <div class="stat-label" data-i18n="ec_products">Products</div>
                </div>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($categories) == 0): ?>
                <div style="text-align: center; padding: 40px; color: #999; grid-column: 1 / -1;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p data-i18n="ec_no_categories">No categories available</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-table"></i>
                        <span data-i18n="ec_category_details">Category Details</span>
                    </h2>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="ec_category_name">Category Name</th>
                                <th data-i18n="ec_products">Products</th>
                                <th data-i18n="ec_description">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            mysqli_data_seek($categories, 0);
                            while($category = mysqli_fetch_assoc($categories)):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                <td>
                                    <span style="background: #E07B39; color: white; padding: 5px 10px; border-radius: 20px;">
                                        <?php echo $category['product_count']; ?>
                                        <span data-i18n="ec_products_lower">products</span>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($category['description'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endwhile; ?>
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