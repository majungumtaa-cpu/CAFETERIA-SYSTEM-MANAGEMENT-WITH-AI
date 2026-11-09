<?php
// employee_login.php

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'employee') {
        header('Location: employee_dashboard.php');
    } else {
        header('Location: admin_dashboard.php');
    }
    exit();
}

require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($full_name) && !empty($phone)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM employee WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) AND TRIM(phone) = TRIM(?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $full_name, $phone);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $employee = mysqli_fetch_assoc($result);

            session_regenerate_id(true);

            $_SESSION['user_id']   = $employee['id'];
            $_SESSION['username']  = $employee['full_name'];
            $_SESSION['full_name'] = $employee['full_name'];
            $_SESSION['phone']     = $employee['phone'];
            $_SESSION['user_type'] = 'employee';

            $_SESSION['flash_message'] = 'Welcome back, ' . $employee['full_name'] . '!';
            $_SESSION['flash_type'] = 'success';

            header('Location: employee_dashboard.php');
            exit();
        } else {
            $error = 'Invalid Full Name or Phone number. Please check and try again.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { scroll-behavior: smooth; box-sizing: border-box; }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('login.jpg') center/cover no-repeat;
            background-attachment: fixed;
            animation: fadeIn 0.6s ease-in;
            padding: 20px;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.8s ease-out;
            transition: all 0.3s ease;
        }

        .login-box:hover {
            box-shadow: 0 20px 45px rgba(0,0,0,0.3);
            transform: translateY(-5px);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
            animation: slideInLeft 0.8s ease-out 0.2s both;
        }

        .login-logo h2 {
            color: #E07B39;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            font-size: 1.8rem;
        }

        .login-logo p {
            color: #999;
            font-size: 0.9rem;
        }

        .login-logo h2:hover { color: #C86A2F; }

        .form-group {
            animation: slideInLeft 0.8s ease-out;
            animation-fill-mode: both;
            margin-bottom: 20px;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            transition: all 0.3s ease;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            padding: 12px 15px;
            width: 100%;
            font-size: 14px;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-control:focus {
            border-color: #E07B39;
            box-shadow: 0 0 10px rgba(224, 123, 57, 0.2);
            outline: none;
            transform: translateX(5px);
        }

        .form-control::placeholder {
            transition: all 0.3s ease;
            color: #bbb;
        }

        .btn {
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            animation: slideInLeft 0.8s ease-out 0.6s both;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(224, 123, 57, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(224, 123, 57, 0.3);
        }

        .btn:active { transform: translateY(0); }

        .alert {
            animation: slideUp 0.5s ease-out;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .forgot-link {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 15px;
            animation: slideInLeft 0.8s ease-out 0.5s both;
        }

        .forgot-link a {
            color: #E07B39;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85em;
            transition: color 0.3s;
        }

        .forgot-link a:hover {
            color: #C86A2F;
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #999;
            font-size: 0.85rem;
            animation: fadeIn 1.2s ease-out 0.8s both;
        }

        .login-footer p { margin-bottom: 10px; }

        .login-footer a {
            color: #E07B39;
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 600;
        }

        .login-footer a:hover {
            color: #C86A2F;
            text-decoration: underline;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 5px;
        }

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
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .back-home a:hover {
            color: #E07B39;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h2><i class="fas fa-id-card"></i> Employee Login</h2>
                <p>Access your employee account</p>
                <span class="badge"><i class="fas fa-briefcase"></i> Staff Portal</span>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="employee_login.php" autocomplete="off">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user" style="color: #E07B39;"></i> Full Name
                    </label>
                    <input type="text" name="full_name" id="full_name" class="form-control"
                           required placeholder="Enter your full name"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone" style="color: #E07B39;"></i> Phone Number
                    </label>
                    <input type="tel" name="phone" id="phone" class="form-control"
                           required placeholder="Enter your phone number"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="forgot-link">
                    <a href="employee_forgot_password.php">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Sign In as Employee
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <!-- ✅ Imeelekezwa kwenye staff_login.php -->
                <p><a href="staff_login.php"><i class="fas fa-arrow-left"></i> Back to Admin Login</a></p>
                <p>&copy; <?php echo date('Y'); ?> Cafeteria Management System</p>
            </div>

            <div class="back-home">
                <a href="index.php">
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