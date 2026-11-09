<?php
// expenses.php - Fixed version
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
    if (isset($_POST['add_expense'])) {
        $expense_category_id = intval($_POST['expense_category_id']);
        $amount = floatval($_POST['amount']);
        
        $sql = "INSERT INTO expenses (expense_category_id, amount) 
                VALUES ($expense_category_id, $amount)";
        
        if (mysqli_query($conn, $sql)) {
            $expense_id = mysqli_insert_id($conn);
            $message = 'Expense added successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                $details = 'category_id:' . $expense_category_id . ';amount:' . number_format($amount,2);
                log_activity($conn, 'add', 'expense', $expense_id, $details);
            }
        } else {
            $message = 'Error adding expense: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['delete_expense'])) {
        $expense_id = intval($_POST['expense_id']);
        $sql = "DELETE FROM expenses WHERE id = $expense_id";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Expense deleted successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'delete', 'expense', $expense_id, 'Expense deleted');
            }
        } else {
            $message = 'Error deleting expense: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// Fetch all expenses with category names
$expenses = mysqli_query($conn, "
    SELECT e.*, ec.category_name
    FROM expenses e
    JOIN expense_category ec ON e.expense_category_id = ec.id
    ORDER BY e.id DESC
");

// Fetch expense categories for dropdown
$expense_categories = mysqli_query($conn, "SELECT * FROM expense_category ORDER BY category_name");

// Calculate total expenses
$total_expenses_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses");
$total_expenses = mysqli_fetch_assoc($total_expenses_result)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <?php 
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
                        <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-receipt"></i> Expense Management</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="expense_categories.php" class="btn btn-secondary">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                    <button onclick="document.getElementById('addExpenseModal').style.display='block'" 
                            class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Expense
                    </button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Expenses Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Expenses List</h2>
                    <span><?php echo mysqli_num_rows($expenses); ?> expenses found</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($expense = mysqli_fetch_assoc($expenses)): ?>
                            <tr>
                                <td>#<?php echo str_pad($expense['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <span class="status-badge status-active">
                                        <?php echo htmlspecialchars($expense['category_name']); ?>
                                    </span>
                                </td>
                                <td style="color: #e74c3c; font-weight: bold;">
                                    $<?php echo number_format($expense['amount'], 2); ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y'); ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                        <button type="submit" name="delete_expense" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this expense?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($expenses) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No expenses found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(mysqli_num_rows($expenses) > 0): ?>
                        <tfoot>
                            <tr style="background-color: #f8f9fa;">
                                <td colspan="2" style="text-align: right; font-weight: bold;">Total:</td>
                                <td style="font-weight: bold; color: #e74c3c;">
                                    $<?php echo number_format($total_expenses, 2); ?>
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <!-- Expense Statistics -->
            <div class="stats-grid">
                <?php
                $expense_count = mysqli_num_rows($expenses);
                $avg_expense = $expense_count > 0 ? $total_expenses / $expense_count : 0;
                $category_count = mysqli_num_rows($expense_categories);
                
                mysqli_data_seek($expenses, 0);
                $category_totals = [];
                while($exp = mysqli_fetch_assoc($expenses)) {
                    $category = $exp['category_name'];
                    $amount = $exp['amount'];
                    if (!isset($category_totals[$category])) {
                        $category_totals[$category] = 0;
                    }
                    $category_totals[$category] += $amount;
                }
                
                $most_expensive_category = '';
                $most_expensive_amount = 0;
                foreach ($category_totals as $category => $total) {
                    if ($total > $most_expensive_amount) {
                        $most_expensive_amount = $total;
                        $most_expensive_category = $category;
                    }
                }
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #e74c3c;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-value"><?php echo $expense_count; ?></div>
                    <div class="stat-label">Total Expenses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($total_expenses, 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($avg_expense, 2); ?></div>
                    <div class="stat-label">Average Expense</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="stat-value"><?php echo $category_count; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            
            <!-- Expense Breakdown by Category -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Expense Breakdown by Category</h2>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            mysqli_data_seek($expense_categories, 0);
                            
                            while($category = mysqli_fetch_assoc($expense_categories)):
                                $category_expenses_sql = "SELECT COUNT(*) as count, SUM(amount) as total 
                                                         FROM expenses 
                                                         WHERE expense_category_id = " . $category['id'];
                                $category_expenses_result = mysqli_query($conn, $category_expenses_sql);
                                $category_data = mysqli_fetch_assoc($category_expenses_result);
                                
                                $category_count = $category_data['count'] ?? 0;
                                $category_total = $category_data['total'] ?? 0;
                                $percentage = $total_expenses > 0 ? ($category_total / $total_expenses * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo $category_count; ?></td>
                                <td style="color: #e74c3c; font-weight: bold;">
                                    $<?php echo number_format($category_total, 2); ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="flex: 1; background: #eee; height: 8px; border-radius: 4px;">
                                            <div style="width: <?php echo $percentage; ?>%; background: #e74c3c; height: 100%; border-radius: 4px;"></div>
                                        </div>
                                        <span><?php echo number_format($percentage, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Expense Modal -->
    <div id="addExpenseModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Add New Expense</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('addExpenseModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="expense_category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php 
                        mysqli_data_seek($expense_categories, 0);
                        while($category = mysqli_fetch_assoc($expense_categories)): 
                        ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_expense" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Expense
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addExpenseModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    window.onclick = function(event) {
        if (event.target == document.getElementById('addExpenseModal')) {
            document.getElementById('addExpenseModal').style.display = 'none';
        }
    }
    </script>
</body>
</html>