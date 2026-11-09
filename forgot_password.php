<?php
// ================================================
// forgot_password.php - Admin Only
// ================================================
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'send_email.php';

$message = '';
$message_type = '';
$message_key = '';  // ✅ Key for translation
$show_verify_link = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_key = 'fp_invalid_email';
        $message_type = 'error';
    } else {
        // CHECK EMAIL — KWA ADMIN (`user` table) PEKEE
        $stmt = $conn->prepare("SELECT id, username FROM user WHERE email = ? OR username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $email);
            $stmt->execute();
            $r = $stmt->get_result();

            if ($r->num_rows > 0) {
                $row = $r->fetch_assoc();
                $user_id = $row['id'];
                $user_name = $row['username'];

                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $safe_email = $conn->real_escape_string($email);
                $conn->query("DELETE FROM password_resets WHERE email = '$safe_email' AND user_type = 'admin'");

                $stmt2 = $conn->prepare("
                    INSERT INTO password_resets (email, token, otp, user_type, expires_at) 
                    VALUES (?, ?, ?, 'admin', ?)
                ");
                $stmt2->bind_param("ssss", $email, $token, $otp, $expires_at);

                if ($stmt2->execute()) {
                    $email_result = send_otp_email($email, $user_name, $otp, 'admin');

                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_token'] = $token;
                    $_SESSION['otp_role']  = 'admin';

                    if ($email_result['success']) {
                        $message_key = 'fp_otp_sent';
                        $message_type = 'success';
                        $show_verify_link = true;
                    } else {
                        $message_key = 'fp_otp_error';
                        $message_type = 'error';
                    }
                } else {
                    $message_key = 'fp_generate_error';
                    $message_type = 'error';
                }
                $stmt2->close();
            } else {
                $message_key = 'fp_email_not_admin';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'fp_invalid_email'    => 'Please enter a valid email address.',
        'fp_otp_sent'         => 'OTP sent successfully to ' . htmlspecialchars($_POST['email'] ?? '') . '!',
        'fp_otp_error'        => 'Error sending OTP. Please try again.',
        'fp_generate_error'   => 'Error generating OTP.',
        'fp_email_not_admin'  => 'This email is not registered as an administrator.'
    ],
    'sw' => [
        'fp_invalid_email'    => 'Tafadhali weka barua pepe sahihi.',
        'fp_otp_sent'         => 'OTP imetumwa kikamilifu kwa ' . htmlspecialchars($_POST['email'] ?? '') . '!',
        'fp_otp_error'        => 'Kuna kosa la kutuma OTP. Tafadhali jaribu tena.',
        'fp_generate_error'   => 'Kuna kosa la kutengeneza OTP.',
        'fp_email_not_admin'  => 'Barua pepe hii haijasajiliwa kama msimamizi.'
    ]
];
$message = $message_key && isset($messageTranslations[$lang][$message_key])
    ? $messageTranslations[$lang][$message_key]
    : '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="fp_title">Forgot Password - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
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
        }
        .form-control:focus {
            outline: none; border-color: #E07B39;
        }
        .btn {
            width: 100%; padding: 12px; border: none; border-radius: 6px;
            font-weight: 600; color: white;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            cursor: pointer; font-family: inherit;
            transition: all 0.3s;
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
            line-height: 1.5;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
                <h2>
                    <i class="fas fa-user-shield"></i>
                    <span data-i18n="fp_title">Admin Forgot Password</span>
                </h2>
                <p data-i18n="fp_subtitle">Enter your admin email to receive an OTP</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <div><?php echo $message; ?></div>
            </div>
            <?php endif; ?>

            <?php if ($show_verify_link): ?>
            <a href="verify_otp.php" class="btn btn-green">
                <i class="fas fa-shield-alt"></i>
                <span data-i18n="fp_verify_otp">Verify OTP</span> →
            </a>
            <br><br>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        <span data-i18n="fp_admin_email">Admin Email</span>
                    </label>
                    <input type="email" name="email" class="form-control"
                           required
                           data-i18n="fp_enter_email" data-i18n-placeholder
                           placeholder="Enter your admin email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           autofocus>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i>
                        <span data-i18n="fp_send_otp">Send OTP</span>
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        <span data-i18n="fp_back_login">Back to Admin Login</span>
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>