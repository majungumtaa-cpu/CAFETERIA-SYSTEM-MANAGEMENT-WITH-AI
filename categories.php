<?php
// categories.php - Product Categories Management
require_once 'config.php';
check_login();

$message = '';
$message_type = '';
$message_key = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

        $check_sql = "SELECT id FROM menu_category WHERE category_name = '$category_name'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['flash_message_key'] = 'cat_error_exists';
            $_SESSION['flash_type'] = 'error';
        } else {
            $sql = "INSERT INTO menu_category (category_name) VALUES ('$category_name')";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash_message_key'] = 'cat_success_add';
                $_SESSION['flash_type'] = 'success';
                if (function_exists('log_activity')) {
                    $new_id = mysqli_insert_id($conn);
                    log_activity($conn, 'add', 'category', $new_id, 'name:' . $category_name);
                }
            } else {
                $_SESSION['flash_message'] = 'Error adding category: ' . mysqli_error($conn);
                $_SESSION['flash_type'] = 'error';
            }
        }
    }

    if (isset($_POST['delete_category'])) {
        $category_id = intval($_POST['category_id']);

        $check_sql = "SELECT id FROM product WHERE category_id = $category_id";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['flash_message_key'] = 'cat_error_has_products';
            $_SESSION['flash_type'] = 'error';
        } else {
            $sql = "DELETE FROM menu_category WHERE id = $category_id";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash_message_key'] = 'cat_success_delete';
                $_SESSION['flash_type'] = 'success';
                if (function_exists('log_activity')) {
                    log_activity($conn, 'delete', 'category', $category_id, 'Category deleted');
                }
            } else {
                $_SESSION['flash_message'] = 'Error deleting category: ' . mysqli_error($conn);
                $_SESSION['flash_type'] = 'error';
            }
        }
    }

    if (isset($_POST['update_category'])) {
        $category_id = intval($_POST['category_id']);
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

        $sql = "UPDATE menu_category SET category_name = '$category_name' WHERE id = $category_id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['flash_message_key'] = 'cat_success_update';
            $_SESSION['flash_type'] = 'success';
            if (function_exists('log_activity')) {
                $res_old = mysqli_query($conn, "SELECT category_name FROM menu_category WHERE id = $category_id LIMIT 1");
                $old_name = ($res_old && mysqli_num_rows($res_old) > 0) ? mysqli_fetch_assoc($res_old)['category_name'] : '';
                $details = 'old_name:' . $old_name . '->new_name:' . $category_name;
                log_activity($conn, 'update', 'category', $category_id, $details);
            }
        } else {
            $_SESSION['flash_message'] = 'Error updating category: ' . mysqli_error($conn);
            $_SESSION['flash_type'] = 'error';
        }
    }

    header('Location: categories.php');
    exit();
}

// Flash message
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}
if (isset($_SESSION['flash_message_key'])) {
    $message_key = $_SESSION['flash_message_key'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message_key'], $_SESSION['flash_type']);
}

// Fetch all categories with product counts
$categories = mysqli_query($conn, "
    SELECT mc.*, 
           COUNT(p.id) as product_count,
           SUM(p.quantity) as total_stock,
           SUM(p.quantity * p.price) as stock_value
    FROM menu_category mc
    LEFT JOIN product p ON mc.id = p.category_id
    GROUP BY mc.id
    ORDER BY mc.category_name
");

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'cat_error_exists'        => 'Category already exists!',
        'cat_error_has_products'  => 'Cannot delete category. It has associated products!',
        'cat_success_add'         => 'Category added successfully!',
        'cat_success_delete'      => 'Category deleted successfully!',
        'cat_success_update'      => 'Category updated successfully!'
    ],
    'sw' => [
        'cat_error_exists'        => 'Kategoria ipo tayari!',
        'cat_error_has_products'  => 'Haiwezi kufuta kategoria. Ina bidhaa zinazohusiana!',
        'cat_success_add'         => 'Kategoria imeongezwa kikamilifu!',
        'cat_success_delete'      => 'Kategoria imefutwa kikamilifu!',
        'cat_success_update'      => 'Kategoria imesasishwa kikamilifu!'
    ]
];
$message = $message_key && isset($messageTranslations[$lang][$message_key])
    ? $messageTranslations[$lang][$message_key]
    : $message;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="cat_title">Product Categories - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-tags"></i>
                    <span data-i18n="cat_title">Product Categories</span>
                </h1>
                <button onclick="document.getElementById('addCategoryModal').style.display='block'" 
                        class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span data-i18n="cat_add">Add Category</span>
                </button>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Categories Grid -->
            <div class="dashboard-grid">
                <?php while($category = mysqli_fetch_assoc($categories)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                        <span class="status-badge status-active">
                            <?php echo $category['product_count']; ?>
                            <span data-i18n="cat_products">products</span>
                        </span>
                    </div>

                    <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                        <div>
                            <div class="card-subtitle" data-i18n="cat_total_stock">Total Stock</div>
                            <div class="card-value" style="font-size: 1.2rem;"><?php echo $category['total_stock'] ?? 0; ?></div>
                        </div>
                        <div>
                            <div class="card-subtitle" data-i18n="cat_stock_value">Stock Value</div>
                            <div class="card-value" style="font-size: 1.2rem;">
                                $<?php echo number_format($category['stock_value'] ?? 0, 2); ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')" 
                                class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-edit"></i>
                            <span data-i18n="cat_edit">Edit</span>
                        </button>
                        <form method="POST" style="display: inline; width: 100%;">
                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                            <button type="submit" name="delete_category" 
                                    class="btn btn-sm btn-danger w-100"
                                    onclick="return confirm('<?= $lang === 'sw' ? 'Una uhakika unataka kufuta kategoria hii?' : 'Are you sure you want to delete this category?' ?>')">
                                <i class="fas fa-trash"></i>
                                <span data-i18n="cat_delete">Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if(mysqli_num_rows($categories) == 0): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3 data-i18n="cat_empty_title">No Categories Found</h3>
                        <p class="text-muted mb-3" data-i18n="cat_empty_desc">Start by adding your first product category</p>
                        <button onclick="document.getElementById('addCategoryModal').style.display='block'" 
                                class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span data-i18n="cat_add">Add Category</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <?php
                $total_categories = mysqli_num_rows($categories);
                mysqli_data_seek($categories, 0);

                $total_products = 0;
                $total_stock_value = 0;
                $most_products = 0;
                $most_products_category = '';

                while($cat = mysqli_fetch_assoc($categories)) {
                    $total_products += $cat['product_count'];
                    $total_stock_value += $cat['stock_value'];

                    if ($cat['product_count'] > $most_products) {
                        $most_products = $cat['product_count'];
                        $most_products_category = $cat['category_name'];
                    }
                }
                ?>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_categories; ?></div>
                    <div class="stat-label" data-i18n="cat_total_categories">Total Categories</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label" data-i18n="cat_total_products">Total Products</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($total_stock_value, 2); ?></div>
                    <div class="stat-label" data-i18n="cat_total_stock_value">Total Stock Value</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #e74c3c;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value"><?php echo $most_products; ?></div>
                    <div class="stat-label">
                        <span data-i18n="cat_most_products">Most Products</span>
                        (<?php echo htmlspecialchars($most_products_category); ?>)
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 data-i18n="cat_modal_add">Add New Category</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('addCategoryModal').style.display='none'">&times;</span>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label data-i18n="cat_name">Category Name *</label>
                    <input type="text" name="category_name" id="categoryName" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span data-i18n="cat_save">Save Category</span>
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addCategoryModal').style.display='none'">
                        <span data-i18n="cat_cancel">Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 data-i18n="cat_modal_edit">Edit Category</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('editCategoryModal').style.display='none'">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="category_id" id="editCategoryId">

                <div class="form-group">
                    <label data-i18n="cat_name">Category Name *</label>
                    <input type="text" name="category_name" id="editCategoryName" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span data-i18n="cat_update">Update Category</span>
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('editCategoryModal').style.display='none'">
                        <span data-i18n="cat_cancel">Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editCategory(categoryId, categoryName) {
        document.getElementById('editCategoryId').value = categoryId;
        document.getElementById('editCategoryName').value = categoryName;
        document.getElementById('editCategoryModal').style.display = 'block';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addCategoryModal')) {
            document.getElementById('addCategoryModal').style.display = 'none';
        }
        if (event.target == document.getElementById('editCategoryModal')) {
            document.getElementById('editCategoryModal').style.display = 'none';
        }
    }

    window.addEventListener('languageChanged', () => {
        if (typeof applyTranslations === 'function') {
            applyTranslations();
        }
    });
    </script>
</body>
</html>