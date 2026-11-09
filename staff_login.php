<?php
// staff_login.php - Unified login for Admin and Employees
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
        header('Location: employee_dashboard.php');
    } else {
        header('Location: admin_dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $employee_name  = $_POST['employee_name'] ?? '';
    $employee_phone = $_POST['employee_phone'] ?? '';

    // ================= ADMIN LOGIN =================
    if (!empty($admin_username) && !empty($admin_password)) {

        $query = "SELECT id, username, password FROM user WHERE username = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            die("SQL Error (Admin Query): " . $conn->error);
        }

        $stmt->bind_param("s", $admin_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            $password_matches = false;
            if (password_verify($admin_password, $admin['password'])) {
                $password_matches = true;
            } elseif ($admin['password'] === $admin_password) {
                $password_matches = true;
            }

            if ($password_matches) {
                $_SESSION['user_id']   = $admin['id'];
                $_SESSION['username']  = $admin['username'];
                $_SESSION['full_name'] = $admin['username'];
                $_SESSION['user_type'] = 'admin';
                header('Location: admin_dashboard.php');
                exit();
            } else {
                $error = 'invalid_admin';
            }
        } else {
            $error = 'invalid_admin';
        }
        $stmt->close();
    }
    // ================= EMPLOYEE LOGIN =================
    elseif (!empty($employee_name) && !empty($employee_phone)) {

        $query = "SELECT * FROM employee WHERE full_name = ? AND phone = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            die("SQL Error (Employee Query): " . $conn->error);
        }

        $stmt->bind_param("ss", $employee_name, $employee_phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            $_SESSION['user_id']   = $employee['id'];
            $_SESSION['full_name'] = $employee['full_name'];
            $_SESSION['phone']     = $employee['phone'];
            $_SESSION['user_type'] = 'employee';
            header('Location: employee_dashboard.php');
            exit();
        } else {
            $error = 'invalid_employee';
        }
        $stmt->close();
    } else {
        $error = 'missing_fields';
    }
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Error message translations
$errorMessages = [
    'en' => [
        'invalid_admin'    => 'Invalid admin username or password',
        'invalid_employee' => 'Invalid employee name or phone number',
        'missing_fields'   => 'Please fill in all required fields'
    ],
    'sw' => [
        'invalid_admin'    => 'Jina la mtumiaji au neno la siri si sahihi',
        'invalid_employee' => 'Jina la mfanyakazi au namba ya simu si sahihi',
        'missing_fields'   => 'Tafadhali jaza sehemu zote zinazohitajika'
    ]
];
$errorText = $error && isset($errorMessages[$lang][$error]) ? $errorMessages[$lang][$error] : $error;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="login_staff">Staff Login - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
    <style>
        * { scroll-behavior: smooth; }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('login.jpg') center/cover no-repeat;
            background-attachment: fixed;
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo i { font-size: 48px; color: #E07B39; margin-bottom: 10px; }
        .login-logo h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: #4A4A4A;
            margin-bottom: 5px;
        }
        .login-logo p { color: #888; font-size: 14px; }

        .login-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .login-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #888;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .login-tab.active { color: #E07B39; border-bottom-color: #E07B39; }
        .login-tab:hover { color: #E07B39; }

        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4A4A4A;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #E07B39;
            box-shadow: 0 0 0 3px rgba(224, 123, 57, 0.1);
        }

        .login-form { display: none; }
        .login-form.active { display: block; }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #E07B39;
            color: white;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: #C86A2F;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(224, 123, 57, 0.3);
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-family: 'Roboto', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i { font-size: 18px; }

        .forgot-link {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 15px;
        }

        .forgot-link a {
            color: #E07B39;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: color 0.3s ease;
            font-family: 'Roboto', sans-serif;
        }

        .forgot-link a:hover {
            color: #C86A2F;
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-family: 'Roboto', sans-serif;
        }

        .back-link a {
            color: #E07B39;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link a:hover { color: #C86A2F; text-decoration: underline; }

        @media (max-width: 480px) {
            .login-box { padding: 25px; }
            .login-logo i { font-size: 36px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <i class="fas fa-building"></i>
                <h1 data-i18n="staff_portal">Staff Portal</h1>
                <p data-i18n="staff_subtitle">Admin & Employee Login</p>
            </div>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($errorText); ?></span>
            </div>
            <?php endif; ?>

            <div class="login-tabs">
                <div class="login-tab active" onclick="switchTab('admin', event)">
                    <i class="fas fa-user-tie"></i> <span data-i18n="staff_admin_tab">Admin</span>
                </div>
                <div class="login-tab" onclick="switchTab('employee', event)">
                    <i class="fas fa-user"></i> <span data-i18n="staff_employee_tab">Employee</span>
                </div>
            </div>

            <!-- Admin Login Form -->
            <form method="POST" class="login-form active" id="admin-form">
                <div class="form-group">
                    <label for="admin_username">
                        <i class="fas fa-user"></i>
                        <span data-i18n="login_username">Username</span>
                    </label>
                    <input type="text" id="admin_username" name="admin_username" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">
                        <i class="fas fa-lock"></i>
                        <span data-i18n="login_password">Password</span>
                    </label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>

                <div class="forgot-link">
                    <a href="forgot_password.php">
                        <i class="fas fa-key"></i>
                        <span data-i18n="login_forgot">Forgot Password?</span>
                    </a>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span data-i18n="login_as_admin">Login as Admin</span>
                </button>
            </form>

            <!-- Employee Login Form -->
            <form method="POST" class="login-form" id="employee-form">
                <div class="form-group">
                    <label for="employee_name">
                        <i class="fas fa-user"></i>
                        <span data-i18n="login_full_name">Full Name</span>
                    </label>
                    <input type="text" id="employee_name" name="employee_name" required>
                </div>
                <div class="form-group">
                    <label for="employee_phone">
                        <i class="fas fa-phone"></i>
                        <span data-i18n="login_phone">Phone Number</span>
                    </label>
                    <input type="tel" id="employee_phone" name="employee_phone" required>
                </div>

                <div class="forgot-link">
                    <a href="forgot_password.php">
                        <i class="fas fa-key"></i>
                        <span data-i18n="login_forgot">Forgot Password?</span>
                    </a>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span data-i18n="login_as_employee">Login as Employee</span>
                </button>
            </form>

            <div class="back-link">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="login_back">Back to Home</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab, event) {
            document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
            event.currentTarget.classList.add('active');

            document.querySelectorAll('.login-form').forEach(f => f.classList.remove('active'));
            document.getElementById(tab + '-form').classList.add('active');

            const errorMsg = document.querySelector('.error-message');
            if (errorMsg) { errorMsg.style.display = 'none'; }
        }

        // ✅ Re-apply translations when language changes
        window.addEventListener('languageChanged', () => {
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
        });
    </script>
</body>
</html>