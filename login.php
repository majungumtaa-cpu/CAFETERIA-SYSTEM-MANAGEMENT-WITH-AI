<?php
// login.php - Admin Login

// ================================================
// ANTI-CACHE HEADERS
// ================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'config.php';
check_login();

// Kama ni employee, mpeleke kwenye employee dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM `user` WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            $password_matches = false;
            if (password_verify($password, $admin['password'])) {
                $password_matches = true;
            } elseif ($admin['password'] === $password) {
                $password_matches = true;
            }

            if ($password_matches) {
                session_regenerate_id(true);

                $_SESSION['user_id']   = $admin['id'];
                $_SESSION['username']  = $admin['username'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['role']      = $admin['role'];

                header('Location: admin_dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0.4) 0%, rgba(200, 106, 47, 0.4) 100%),
                        url('login.jpg') center/cover no-repeat;
            background-attachment: fixed;
            padding: 20px;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo h2 {
            color: #E07B39;
            margin-bottom: 8px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-logo p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-group label i {
            color: #E07B39;
            margin-right: 5px;
        }

        .form-control {
            transition: all 0.3s ease;
            border: 2px solid #ecf0f1;
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #E07B39;
            box-shadow: 0 0 10px rgba(224, 123, 57, 0.2);
        }

        .btn {
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            font-family: inherit;
            font-size: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(230, 126, 34, 0.3);
        }

        .alert {
            padding: 12px 15px;
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
            margin-top: -5px;
            margin-bottom: 15px;
        }

        .forgot-link a {
            color: #E07B39;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link a:hover {
            color: #C86A2F;
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: #E07B39;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover { text-decoration: underline; }

        .footer-divider {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* BACK TO HOME LINK */
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
                <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
                <p>Please sign in to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control" required
                           placeholder="Enter username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" required
                           placeholder="Enter password">
                </div>

                <div class="forgot-link">
                    <a href="forgot_password.php">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Sign In as Admin
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Cafeteria Management System</p>

                <p class="footer-divider">
                    <strong>Employee?</strong> <a href="employee_login.php">Login here</a>
                </p>
            </div>

            <!-- ================================================
                 BACK TO HOME LINK
                 ================================================ -->
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