<?php
// ================================================
// verify_otp.php - Verify OTP Code
// ================================================
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        $message = 'Please enter both email and OTP code.';
        $message_type = 'error';
    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
        $message = 'OTP must be a 6-digit number.';
        $message_type = 'error';
    } else {
        // ================================================
        // VERIFY OTP
        // ================================================
        $stmt = $conn->prepare("
            SELECT * FROM password_resets 
            WHERE email = ? 
              AND otp = ? 
              AND used = 0 
              AND verified = 0
              AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            $token = $reset['token'];

            // Mark as verified
            $update = $conn->prepare("UPDATE password_resets SET verified = 1 WHERE token = ?");
            $update->bind_param("s", $token);
            $update->execute();
            $update->close();

            // Save session
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role']  = $reset['user_type'];

            // Log
            if (function_exists('log_activity')) {
                log_activity($conn, 'otp_verified', $reset['user_type'], 0, "OTP verified for $email");
            }

            // Redirect to reset_password
            header("Location: reset_password.php?token=" . urlencode($token));
            exit();
        } else {
            $message = 'Invalid OTP code, or it has expired. Please request a new OTP.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Prefill email from session
$prefill_email = $_SESSION['otp_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('login.jpg') center/cover no-repeat;
            background-attachment: fixed;
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
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none; border-color: #E07B39;
            box-shadow: 0 0 10px rgba(224, 123, 57, 0.2);
        }

        /* OTP Input - Kubwa, inaonekana kama code */
        .otp-input {
            font-size: 2em;
            text-align: center;
            letter-spacing: 15px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            padding: 15px 10px;
        }

        .btn {
            width: 100%; padding: 12px; border: none; border-radius: 6px;
            font-weight: 600; color: white;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            cursor: pointer; transition: all 0.3s; font-family: inherit;
            font-size: 1rem;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(230, 126, 34, 0.3);
        }

        .alert {
            padding: 15px; border-radius: 6px; margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 10px; font-size: 0.9em;
            line-height: 1.5;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .login-footer {
            text-align: center; margin-top: 20px;
            color: #7f8c8d; font-size: 0.9rem;
        }
        .login-footer a {
            color: #E07B39; text-decoration: none; font-weight: 600;
        }
        .login-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h2><i class="fas fa-shield-alt"></i> Verify OTP</h2>
                <p>Enter the 6-digit code sent to your email</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control"
                           required placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($prefill_email); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-key"></i> OTP Code</label>
                    <input type="text" name="otp" class="form-control otp-input"
                           required maxlength="6" pattern="[0-9]{6}"
                           placeholder="------"
                           inputmode="numeric"
                           autocomplete="off"
                           autofocus>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-check"></i> Verify OTP
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Cafeteria Management System</p>
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <a href="forgot_password.php"><i class="fas fa-redo"></i> Resend OTP</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>