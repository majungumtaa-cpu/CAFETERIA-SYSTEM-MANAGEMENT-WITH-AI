<?php
// feedback.php
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
    if (isset($_POST['add_feedback'])) {
        $customer_id = intval($_POST['customer_id']);
        $rating = intval($_POST['rating']);
        
        // Validate inputs
        if ($customer_id <= 0) {
            $message = 'Please select a customer!';
            $message_type = 'error';
        } elseif ($rating < 1 || $rating > 5) {
            $message = 'Rating must be between 1 and 5!';
            $message_type = 'error';
        } else {
            $sql = "INSERT INTO feedback (customer_id, rating) VALUES ($customer_id, $rating)";
            if (mysqli_query($conn, $sql)) {
                $feedback_id = mysqli_insert_id($conn);
                $message = 'Feedback added successfully!';
                $message_type = 'success';
                if (function_exists('log_activity')) {
                    $details = 'customer_id:' . $customer_id . ';rating:' . $rating;
                    log_activity($conn, 'add', 'feedback', $feedback_id, $details);
                }
            } else {
                $message = 'Error adding feedback: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
    
    if (isset($_POST['delete_feedback'])) {
        $feedback_id = intval($_POST['feedback_id']);
        $sql = "DELETE FROM feedback WHERE id = $feedback_id";
        if (mysqli_query($conn, $sql)) {
            $message = 'Feedback deleted successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'delete', 'feedback', $feedback_id, 'Feedback deleted');
            }
        } else {
            $message = 'Error deleting feedback: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// Fetch all feedback with customer information
$feedback_list = mysqli_query($conn, "
    SELECT f.id, f.rating, c.full_name, c.id as customer_id
    FROM feedback f
    LEFT JOIN customer c ON f.customer_id = c.id
    ORDER BY f.id DESC
");

if (!$feedback_list) {
    $message = 'Error fetching feedback: ' . mysqli_error($conn);
    $message_type = 'error';
}

// Fetch customers for dropdown
$customers = mysqli_query($conn, "SELECT id, full_name FROM customer ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-comments"></i> Feedback Management</h1>
                <button onclick="document.getElementById('addFeedbackModal').style.display='block'" 
                        class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Feedback
                </button>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>All Feedback</h2>
                    <span><?php echo $feedback_list ? mysqli_num_rows($feedback_list) : 0; ?> feedback entries</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($feedback_list && mysqli_num_rows($feedback_list) > 0): ?>
                            <?php while($feedback = mysqli_fetch_assoc($feedback_list)): ?>
                            <tr>
                                <td>#<?php echo str_pad($feedback['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($feedback['full_name']); ?></td>
                                <td>
                                    <div style="font-size: 18px; color: #f39c12;">
                                        <?php for($i = 0; $i < $feedback['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                        <?php for($i = $feedback['rating']; $i < 5; $i++): ?>
                                            <i class="fas fa-star" style="color: #ddd;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                        <button type="submit" name="delete_feedback" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No feedback yet</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Feedback Modal -->
    <div id="addFeedbackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Feedback</h2>
                <span class="close" onclick="document.getElementById('addFeedbackModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Customer *</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">Select a customer</option>
                            <?php 
                            mysqli_data_seek($customers, 0);
                            while($customer = mysqli_fetch_assoc($customers)): 
                            ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['full_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating (1-5 stars) *</label>
                        <select name="rating" class="form-control" required>
                            <option value="">Select rating</option>
                            <option value="1">1 Star - Poor</option>
                            <option value="2">2 Stars - Fair</option>
                            <option value="3">3 Stars - Good</option>
                            <option value="4">4 Stars - Very Good</option>
                            <option value="5">5 Stars - Excellent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_feedback" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Feedback
                        </button>
                        <button type="button" onclick="document.getElementById('addFeedbackModal').style.display='none'" 
                                class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        var modal = document.getElementById('addFeedbackModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
</body>
</html>
