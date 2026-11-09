 <?php
// user/user_register.php - Customer Registration

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

session_start();

if (isset($_SESSION['customer_id'])) {
    header('Location: user_dashboard.php');
    exit();
}

require_once '../config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$error_key = '';
$success = '';
$success_key = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_key = 'reg_invalid_request';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        if (empty($full_name) || empty($phone) || empty($email) || empty($password) || empty($confirm)) {
            $error_key = 'reg_fill_required';
        } elseif (mb_strlen($full_name) < 3) {
            $error_key = 'reg_name_short';
        } elseif (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
            $error_key = 'reg_invalid_phone';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_key = 'reg_invalid_email';
        } elseif (strlen($password) < 6) {
            $error_key = 'reg_password_short';
        } elseif ($password !== $confirm) {
            $error_key = 'reg_password_mismatch';
        } else {
            $email = preg_replace('/[^a-zA-Z0-9@._\-+]/', '', $email);

            $stmt = mysqli_prepare($conn, "SELECT id FROM customer WHERE phone = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $phone);
            mysqli_stmt_execute($stmt);
            $phone_exists = mysqli_stmt_get_result($stmt)->num_rows > 0;
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "SELECT id FROM customer WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $email_exists = mysqli_stmt_get_result($stmt)->num_rows > 0;
            mysqli_stmt_close($stmt);

            if ($phone_exists) {
                $error_key = 'reg_phone_exists';
            } elseif ($email_exists) {
                $error_key = 'reg_email_exists';
            } else {
                $plain_password = $password;

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO customer (full_name, phone, email, password) 
                    VALUES (?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "ssss", $full_name, $phone, $email, $plain_password);

                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_stmt_insert_id($stmt);
                    mysqli_stmt_close($stmt);

                    if (function_exists('log_activity')) {
                        log_activity($conn, 'register', 'customer', $new_id, "name:$full_name;email:$email");
                    }

                    unset($_SESSION['csrf_token']);
                    $success_key = 'reg_success';
                } else {
                    $error_key = 'reg_failed';
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Translations
$translations = [
    'en' => [
        'reg_invalid_request'   => 'Invalid request. Please try again.',
        'reg_fill_required'     => 'Please fill in all required fields.',
        'reg_name_short'        => 'Full name must be at least 3 characters.',
        'reg_invalid_phone'     => 'Please enter a valid phone number (9-20 digits).',
        'reg_invalid_email'     => 'Please enter a valid email address.',
        'reg_password_short'    => 'Password must be at least 6 characters.',
        'reg_password_mismatch' => 'Passwords do not match.',
        'reg_phone_exists'      => 'This phone number is already registered.',
        'reg_email_exists'      => 'This email is already registered. Please login instead.',
        'reg_success'           => 'Account created successfully! You can now login.',
        'reg_failed'            => 'Registration failed. Please try again.'
    ],
    'sw' => [
        'reg_invalid_request'   => 'Ombi si sahihi. Tafadhali jaribu tena.',
        'reg_fill_required'     => 'Tafadhali jaza sehemu zote zinazohitajika.',
        'reg_name_short'        => 'Jina kamili liwe na herufi 3 au zaidi.',
        'reg_invalid_phone'     => 'Tafadhali weka namba sahihi ya simu (tarakimu 9-20).',
        'reg_invalid_email'     => 'Tafadhali weka barua pepe sahihi.',
        'reg_password_short'    => 'Neno la siri liwe na herufi 6 au zaidi.',
        'reg_password_mismatch' => 'Maneno ya siri hayafanani.',
        'reg_phone_exists'      => 'Namba hii ya simu imesajiliwa tayari.',
        'reg_email_exists'      => 'Barua pepe hii imesajiliwa tayari. Tafadhali ingia badala yake.',
        'reg_success'           => 'Akaunti imeundwa kikamilifu! Unaweza kuingia sasa.',
        'reg_failed'            => 'Usajili umeshindwa. Tafadhali jaribu tena.'
    ]
];

$error   = $error_key   && isset($translations[$lang][$error_key])   ? $translations[$lang][$error_key]   : '';
$success = $success_key && isset($translations[$lang][$success_key]) ? $translations[$lang][$success_key] : '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="reg_page_title">Register - Cafeteria</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../lang.js"></script>
    <style>
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('../login.jpg') center/cover no-repeat;
            background-attachment: fixed;
            padding: 20px;
        }

        .register-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }

        .register-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #E07B39;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .form-group label i {
            color: #E07B39;
            margin-right: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #E07B39;
            box-shadow: 0 0 5px rgba(224, 123, 57, 0.3);
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .register-btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #E07B39;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover { text-decoration: underline; }

        .back-home {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .back-home a {
            color: #666;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .back-home a:hover { color: #E07B39; }

        .password-hint {
            display: block;
            color: #888;
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2>
                <i class="fas fa-user-plus"></i>
                <span data-i18n="reg_title">Customer Registration</span>
            </h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <br>
                    <a href="user_login.php" style="color: #155724; font-weight: 600; margin-top: 10px; display: inline-block;">
                        <i class="fas fa-sign-in-alt"></i>
                        <span data-i18n="reg_go_login">Go to Login</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="user_register.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i>
                        <span data-i18n="reg_full_name">Full Name</span> *
                    </label>
                    <input type="text" id="full_name" name="full_name" required
                           data-i18n="reg_full_name_ph" data-i18n-placeholder
                           placeholder="e.g., John Doe"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                           autofocus>
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i>
                        <span data-i18n="reg_phone">Phone Number</span> *
                    </label>
                    <input type="tel" id="phone" name="phone" required
                           data-i18n="reg_phone_ph" data-i18n-placeholder
                           placeholder="+255 712 345 678"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        <span data-i18n="reg_email">Email Address</span> *
                    </label>
                    <input type="email" id="email" name="email" required
                           data-i18n="reg_email_ph" data-i18n-placeholder
                           placeholder="you@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        <span data-i18n="reg_password">Password</span> *
                    </label>
                    <input type="password" id="password" name="password" required
                           data-i18n="reg_password_ph" data-i18n-placeholder
                           placeholder="At least 6 characters"
                           minlength="6">
                    <small class="password-hint">
                        <i class="fas fa-info-circle"></i>
                        <span data-i18n="reg_password_hint">Minimum 6 characters</span>
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        <span data-i18n="reg_confirm_password">Confirm Password</span> *
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           data-i18n="reg_confirm_password_ph" data-i18n-placeholder
                           placeholder="Re-enter your password"
                           minlength="6">
                </div>

                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span data-i18n="reg_button">Create Account</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="login-link">
                <p>
                    <span data-i18n="reg_have_account">Already have an account?</span>
                    <a href="user_login.php" data-i18n="reg_login_here">Login here</a>
                </p>
            </div>

            <div class="back-home">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="login_back">Back to Home</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) { window.location.reload(); }
        });

        window.addEventListener('languageChanged', () => {
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
        });
    </script>
</body>
</html>