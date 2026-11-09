<?php
// ================================================
// user/forgot_password.php - Customer Only (OTP Displayed Below)
// ================================================
session_start();

if (isset($_SESSION['customer_id'])) {
    header('Location: user_dashboard.php');
    exit();
}

require_once '../config.php';
require_once '../send_email.php';

$message = '';
$message_type = '';
$show_otp = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // ================================================
        // CHECK EMAIL — CUSTOMER PEKEE
        // ================================================
        $stmt = $conn->prepare("SELECT id, full_name FROM customer WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $r = $stmt->get_result();

            if ($r->num_rows > 0) {
                $row = $r->fetch_assoc();
                $user_name = $row['full_name'];

                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $safe_email = $conn->real_escape_string($email);
                $conn->query("DELETE FROM password_resets WHERE email = '$safe_email' AND user_type = 'customer'");

                $stmt2 = $conn->prepare("
                    INSERT INTO password_resets (email, token, otp, user_type, expires_at) 
                    VALUES (?, ?, ?, 'customer', ?)
                ");
                $stmt2->bind_param("ssss", $email, $token, $otp, $expires_at);

                if ($stmt2->execute()) {
                    // Tuma email (kama inawezekana)
                    send_otp_email($email, $user_name, $otp, 'customer');

                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_token'] = $token;
                    $_SESSION['otp_role']  = 'customer';

                    // Onyesha OTP chini (hakuna ujumbe wa email)
                    $show_otp = $otp;
                } else {
                    $message = 'Error generating OTP. Please try again.';
                    $message_type = 'error';
                }
                $stmt2->close();
            } else {
                $message = 'This email is not registered in our system.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Forgot Password</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('../login.jpg') center/cover no-repeat;
            background-attachment: fixed;
            padding: 20px;
        }
        .login-box {
            background: white; padding: 40px; border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%; max-width: 400px;
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo h2 { color: #E07B39; margin-bottom: 10px; }
        .login-logo p { color: #666; font-size: 0.95rem; }
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: #333; font-size: 0.95rem;
        }
        .form-group label i { color: #E07B39; margin-right: 5px; }
        .form-control {
            width: 100%; padding: 12px; border: 2px solid #ecf0f1;
            border-radius: 6px; font-size: 0.95rem; box-sizing: border-box;
        }
        .form-control:focus {
            outline: none; border-color: #E07B39;
        }
        .btn {
            width: 100%; padding: 12px; border: none; border-radius: 6px;
            font-weight: 600; color: white;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            cursor: pointer; font-family: inherit;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-green {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            margin-top: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .alert {
            padding: 15px; border-radius: 6px; margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 10px; font-size: 0.9em;
        }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .otp-backup {
            background: #f8f9fa;
            border: 1px dashed #ccc;
            border-radius: 6px;
            padding: 10px 15px;
            margin-top: 20px;
            text-align: center;
        }
        .otp-backup .label {
            font-size: 0.7rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .otp-backup .code {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #666;
            letter-spacing: 4px;
            font-weight: 600;
        }

        .login-footer {
            text-align: center; margin-top: 20px;
            color: #7f8c8d; font-size: 0.9rem;
        }
        .login-footer a { color: #E07B39; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h2><i class="fas fa-user"></i> Customer Forgot Password</h2>
                <p>Enter your registered email</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <div><?php echo $message; ?></div>
            </div>
            <?php endif; ?>

            <?php if ($show_otp): ?>
            <a href="../verify_otp.php" class="btn btn-green">
                <i class="fas fa-shield-alt"></i> Verify OTP →
            </a>
            <?php endif; ?>

            <form method="POST" style="margin-top: 15px;">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Customer Email</label>
                    <input type="email" name="email" class="form-control"
                           required placeholder="Enter your registered email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           autofocus>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Get OTP
                    </button>
                </div>
            </form>

            <!-- ============ OTP DISPLAY (Chini) ============ -->
            <?php if ($show_otp): ?>
            <div class="otp-backup">
                <div class="label">Your OTP Code</div>
                <div class="code"><?php echo htmlspecialchars($show_otp); ?></div>
            </div>
            <?php endif; ?>

            <div class="login-footer">
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <a href="user_login.php"><i class="fas fa-arrow-left"></i> Back to Customer Login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>