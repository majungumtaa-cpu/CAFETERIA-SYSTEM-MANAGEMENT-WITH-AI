<?php
// expense_categories.php - Expense Categories Management
require_once 'config.php';
check_login();

// Prevent employees from accessing this page
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_expense_category'])) {
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
        
        $check_sql = "SELECT id FROM expense_category WHERE category_name = '$category_name'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Expense category already exists!';
            $message_type = 'error';
        } else {
            $sql = "INSERT INTO expense_category (category_name) VALUES ('$category_name')";
            
            if (mysqli_query($conn, $sql)) {
                $category_id = mysqli_insert_id($conn);
                $message = 'Expense category added successfully!';
                $message_type = 'success';
                if (function_exists('log_activity')) {
                    $details = 'name:' . $category_name;
                    log_activity($conn, 'add', 'expense_category', $category_id, $details);
                }
            } else {
                $message = 'Error adding expense category: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
    
    if (isset($_POST['delete_expense_category'])) {
        $category_id = intval($_POST['category_id']);
        
        $check_sql = "SELECT id FROM expenses WHERE expense_category_id = $category_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Cannot delete category. It has associated expenses!';
            $message_type = 'error';
        } else {
            $sql = "DELETE FROM expense_category WHERE id = $category_id";
            
            if (mysqli_query($conn, $sql)) {
                $message = 'Expense category deleted successfully!';
                $message_type = 'success';
                if (function_exists('log_activity')) {
                    log_activity($conn, 'delete', 'expense_category', $category_id, 'Category deleted');
                }
            } else {
                $message = 'Error deleting expense category: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
    
    if (isset($_POST['update_expense_category'])) {
        $category_id = intval($_POST['category_id']);
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
        
        $sql = "UPDATE expense_category SET category_name = '$category_name' WHERE id = $category_id";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Expense category updated successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                $details = 'name:' . $category_name;
                log_activity($conn, 'update', 'expense_category', $category_id, $details);
            }
        } else {
            $message = 'Error updating expense category: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// Fetch all expense categories with expense counts
$expense_categories = mysqli_query($conn, "
    SELECT ec.*, 
           COUNT(e.id) as expense_count,
           SUM(e.amount) as total_amount
    FROM expense_category ec
    LEFT JOIN expenses e ON ec.id = e.expense_category_id
    GROUP BY ec.id
    ORDER BY ec.category_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Categories - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-money-bill-wave"></i> Expense Categories</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="expenses.php" class="btn btn-success">
                        <i class="fas fa-receipt"></i> Manage Expenses
                    </a>
                    <button onclick="document.getElementById('addExpenseCategoryModal').style.display='block'" 
                            class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Expense Categories Grid -->
            <div class="dashboard-grid">
                <?php while($category = mysqli_fetch_assoc($expense_categories)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                        <span class="status-badge 
                            <?php echo $category['expense_count'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $category['expense_count']; ?> expenses
                        </span>
                    </div>
                    
                    <div class="stats-grid" style="grid-template-columns: 1fr; gap: 10px; margin-top: 15px;">
                        <div>
                            <div class="card-subtitle">Total Expenses</div>
                            <div class="card-value" style="font-size: 1.2rem; color: #e74c3c;">
                                $<?php echo number_format($category['total_amount'] ?? 0, 2); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <button onclick="editExpenseCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')" 
                                class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display: inline; width: 100%;">
                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                            <button type="submit" name="delete_expense_category" 
                                    class="btn btn-sm btn-danger w-100"
                                    onclick="return confirm('Are you sure you want to delete this expense category?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($expense_categories) == 0): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>No Expense Categories Found</h3>
                        <p class="text-muted mb-3">Start by adding your first expense category</p>
                        <button onclick="document.getElementById('addExpenseCategoryModal').style.display='block'" 
                                class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <?php
                $total_categories = mysqli_num_rows($expense_categories);
                mysqli_data_seek($expense_categories, 0);
                
                $total_expenses = 0;
                $total_expense_amount = 0;
                $most_expenses = 0;
                $most_expenses_category = '';
                
                while($cat = mysqli_fetch_assoc($expense_categories)) {
                    $total_expenses += $cat['expense_count'];
                    $total_expense_amount += $cat['total_amount'];
                    
                    if ($cat['expense_count'] > $most_expenses) {
                        $most_expenses = $cat['expense_count'];
                        $most_expenses_category = $cat['category_name'];
                    }
                }
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #e74c3c;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_expenses; ?></div>
                    <div class="stat-label">Total Expenses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($total_expense_amount, 2); ?></div>
                    <div class="stat-label">Total Expense Amount</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-value"><?php echo $most_expenses; ?></div>
                    <div class="stat-label">Most Expenses (<?php echo htmlspecialchars($most_expenses_category); ?>)</div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Expense Category Modal -->
    <div id="addExpenseCategoryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Add New Expense Category</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('addExpenseCategoryModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="category_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_expense_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Category
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addExpenseCategoryModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Expense Category Modal -->
    <div id="editExpenseCategoryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Edit Expense Category</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('editExpenseCategoryModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="category_id" id="editExpenseCategoryId">
                
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="category_name" id="editExpenseCategoryName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_expense_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('editExpenseCategoryModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function editExpenseCategory(categoryId, categoryName) {
        document.getElementById('editExpenseCategoryId').value = categoryId;
        document.getElementById('editExpenseCategoryName').value = categoryName;
        document.getElementById('editExpenseCategoryModal').style.display = 'block';
    }
    
    window.onclick = function(event) {
        if (event.target == document.getElementById('addExpenseCategoryModal')) {
            document.getElementById('addExpenseCategoryModal').style.display = 'none';
        }
        if (event.target == document.getElementById('editExpenseCategoryModal')) {
            document.getElementById('editExpenseCategoryModal').style.display = 'none';
        }
    }
    </script>
</body>
</html>