<?php
// employee_feedback.php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_id = $_SESSION['user_id'];

// Get feedback ONLY from customers on orders created by THIS employee
// Strict security: only show feedback for orders where employee_id matches
$feedback = mysqli_query($conn, "
    SELECT f.*, c.full_name as customer_name, o.id as order_id
    FROM feedback f
    JOIN customer c ON f.customer_id = c.id
    JOIN `order` o ON f.customer_id = o.customer_id AND o.employee_id = $employee_id
    ORDER BY f.created_at DESC
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Employee Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-comments"></i> Customer Feedback</h1>
                <span>Feedback on your orders</span>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Feedback Received</h2>
                    <span><?php echo mysqli_num_rows($feedback); ?> feedback items</span>
                </div>
                <?php if (mysqli_num_rows($feedback) > 0): ?>
                <div style="display: grid; gap: 20px;">
                    <?php while($fb = mysqli_fetch_assoc($feedback)): ?>
                    <div style="background: #f9f9f9; border-left: 4px solid #E07B39; padding: 20px; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <h4 style="margin: 0; color: #333;">
                                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($fb['customer_name']); ?>
                                </h4>
                                <small style="color: #999;">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($fb['created_at'])); ?>
                                </small>
                            </div>
                            <div style="color: #f39c12;">
                                <?php for ($i = 0; $i < $fb['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                                <?php for ($i = $fb['rating']; $i < 5; $i++): ?>
                                <i class="far fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p style="margin: 10px 0; color: #666;">
                            <?php echo htmlspecialchars($fb['comment']); ?>
                        </p>
                        <div style="margin-top: 10px; font-size: 0.9rem;">
                            <span style="background: <?php echo $fb['rating'] >= 4 ? '#2ecc71' : ($fb['rating'] >= 3 ? '#f39c12' : '#e74c3c'); ?>; color: white; padding: 3px 10px; border-radius: 20px;">
                                <?php 
                                    if ($fb['rating'] >= 4) echo '<i class="fas fa-thumbs-up"></i> Positive';
                                    elseif ($fb['rating'] >= 3) echo '<i class="fas fa-minus-circle"></i> Neutral';
                                    else echo '<i class="fas fa-thumbs-down"></i> Negative';
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No feedback received yet</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Feedback Summary -->
            <?php 
            $summary = mysqli_query($conn, "
                SELECT 
                    COUNT(*) as total,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as neutral,
                    SUM(CASE WHEN rating < 3 THEN 1 ELSE 0 END) as negative
                FROM feedback f
                WHERE f.order_id IN (
                    SELECT id FROM `order` WHERE employee_id = $employee_id
                )
            ");
            $summary_data = mysqli_fetch_assoc($summary);
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-value"><?php echo $summary_data['total']; ?></div>
                    <div class="stat-label">Total Feedback</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($summary_data['avg_rating'], 1); ?>/5</div>
                    <div class="stat-label">Average Rating</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="stat-value"><?php echo $summary_data['positive']; ?></div>
                    <div class="stat-label">Positive</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #e74c3c;">
                        <i class="fas fa-thumbs-down"></i>
                    </div>
                    <div class="stat-value"><?php echo $summary_data['negative']; ?></div>
                    <div class="stat-label">Negative</div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
