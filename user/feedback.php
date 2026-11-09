<?php
// user/feedback.php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: user_login.php');
    exit();
}

require_once '../config.php';

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$error = '';
$success = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = (int)$_POST['rating'];
    $comments = trim($_POST['comments'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating (1-5 stars).';
    } else {
        // Check if customer already submitted feedback
        $existing_feedback = mysqli_query($conn, "SELECT id FROM feedback WHERE customer_id = $customer_id");
        if (mysqli_num_rows($existing_feedback) > 0) {
            $error = 'You have already submitted feedback. Thank you!';
        } else {
            // Insert feedback
            $stmt = mysqli_prepare($conn, "INSERT INTO feedback (customer_id, rating) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $customer_id, $rating);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Thank you for your feedback! We appreciate your input.';
            } else {
                $error = 'Failed to submit feedback. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get customer's existing feedback
$my_feedback = mysqli_query($conn, "
    SELECT rating, id
    FROM feedback
    WHERE customer_id = $customer_id
    LIMIT 1
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Cafeteria</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
        }

        .feedback-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .feedback-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .feedback-header h1 {
            margin: 0;
        }

        .feedback-form, .my-feedback {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .feedback-form h3, .my-feedback h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #E07B39;
            padding-bottom: 10px;
        }

        .rating-section {
            margin-bottom: 20px;
        }

        .rating-section label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
        }

        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2em;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }

        .rating-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 100px;
            font-size: 16px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .feedback-display {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .rating-display {
            font-size: 2em;
            color: #ffc107;
            margin-bottom: 10px;
        }

        .rating-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .feedback-date {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .thank-you {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .thank-you i {
            font-size: 3em;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="feedback-container">
        <a href="user_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="feedback-header">
            <h1><i class="fas fa-star"></i> Share Your Feedback</h1>
            <p>Your opinion helps us improve our service!</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($my_feedback) > 0): ?>
            <div class="my-feedback">
                <h3><i class="fas fa-history"></i> Your Previous Feedback</h3>
                <?php $feedback = mysqli_fetch_assoc($my_feedback); ?>
                <div class="feedback-display">
                    <div class="rating-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $feedback['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-number"><?php echo $feedback['rating']; ?> out of 5 stars</div>
                </div>
            </div>

            <div class="thank-you">
                <i class="fas fa-heart"></i>
                <h3>Thank you for your feedback!</h3>
                <p>We appreciate you taking the time to share your experience with us.</p>
            </div>
        <?php else: ?>
            <div class="feedback-form">
                <h3><i class="fas fa-edit"></i> Submit Your Feedback</h3>

                <form method="POST">
                    <div class="rating-section">
                        <label>How would you rate your overall experience?</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5"><label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                        <div class="rating-text">
                            <span id="rating-text">No rating selected</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comments">Additional Comments (Optional):</label>
                        <textarea name="comments" id="comments" placeholder="Tell us more about your experience..."></textarea>
                    </div>

                    <div style="text-align: center;">
                        <button type="submit" name="submit_feedback" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Update rating text when stars are clicked
        const stars = document.querySelectorAll('.star-rating input');
        const ratingText = document.getElementById('rating-text');

        stars.forEach(star => {
            star.addEventListener('change', function() {
                const rating = this.value;
                const texts = {
                    1: 'Poor - 1 star',
                    2: 'Fair - 2 stars',
                    3: 'Good - 3 stars',
                    4: 'Very Good - 4 stars',
                    5: 'Excellent - 5 stars'
                };
                ratingText.textContent = texts[rating];
            });
        });
    </script>
</body>
</html>