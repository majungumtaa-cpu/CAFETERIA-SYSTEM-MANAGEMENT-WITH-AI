<?php
// user/user_login.php - Customer Login

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_key = 'invalid_request';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error_key = 'missing_fields';
        } else {
            $stmt = mysqli_prepare($conn, "
                SELECT id, full_name, email, password 
                FROM customer 
                WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) 
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $customer = mysqli_fetch_assoc($result);

                if ($customer['password'] === $password) {
                    session_regenerate_id(true);

                    $_SESSION['customer_id']    = $customer['id'];
                    $_SESSION['customer_name']  = $customer['full_name'];
                    $_SESSION['customer_email'] = trim($customer['email']);

                    $_SESSION['flash_message'] = 'Welcome back, ' . $customer['full_name'] . '!';
                    $_SESSION['flash_type'] = 'success';

                    unset($_SESSION['csrf_token']);

                    header('Location: user_dashboard.php');
                    exit();
                } else {
                    $error_key = 'invalid_credentials';
                }
            } else {
                $error_key = 'invalid_credentials';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

$errorMessages = [
    'en' => [
        'invalid_request'     => 'Invalid request. Please try again.',
        'missing_fields'      => 'Please enter both email and password.',
        'invalid_credentials' => 'Invalid email or password.'
    ],
    'sw' => [
        'invalid_request'     => 'Ombi si sahihi. Tafadhali jaribu tena.',
        'missing_fields'      => 'Tafadhali weka barua pepe na neno la siri.',
        'invalid_credentials' => 'Barua pepe au neno la siri si sahihi.'
    ]
];
$errorText = $error_key && isset($errorMessages[$lang][$error_key]) ? $errorMessages[$lang][$error_key] : '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Cafeteria</title>
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

        .login-box h2 {
            text-align: center; margin-bottom: 30px; color: #E07B39;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block; margin-bottom: 5px; color: #555;
            font-weight: 500;
        }

        .form-group label i {
            color: #E07B39; margin-right: 5px;
        }

        .form-group input {
            width: 100%; padding: 12px; border: 2px solid #ecf0f1;
            border-radius: 6px; font-size: 15px; box-sizing: border-box;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none; border-color: #E07B39;
            box-shadow: 0 0 5px rgba(224, 123, 57, 0.3);
        }

        .login-btn {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white; border: none; border-radius: 6px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: transform 0.2s;
            font-family: inherit;
        }

        .login-btn:hover { transform: translateY(-2px); }

        .error {
            background: #fee; color: #c33; padding: 10px;
            border-radius: 5px; margin-bottom: 20px; text-align: center;
            display: flex; align-items: center; gap: 8px; justify-content: center;
            border: 1px solid #fcc;
        }

        .forgot-link { text-align: right; margin-top: -5px; margin-bottom: 15px; }

        .forgot-link a {
            color: #E07B39; text-decoration: none;
            font-weight: 600; font-size: 0.85em;
        }

        .forgot-link a:hover { text-decoration: underline; }

        .register-link { text-align: center; margin-top: 20px; }

        .register-link a { color: #E07B39; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }

        .back-home {
            text-align: center; margin-top: 15px;
            padding-top: 15px; border-top: 1px solid #eee;
        }

        .back-home a {
            color: #666; font-size: 0.85rem; text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .back-home a:hover { color: #E07B39; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>
                <i class="fas fa-utensils"></i>
                Customer Login
            </h2>

            <?php if ($errorText): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($errorText); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="user_login.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" required
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           autofocus>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                </div>

                <div class="forgot-link">
                    <a href="forgot_password.php">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="register-link">
                <p>New customer? <a href="user_register.php">Register here</a></p>
            </div>

            <div class="back-home">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>