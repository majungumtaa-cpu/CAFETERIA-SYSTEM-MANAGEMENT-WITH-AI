<?php
// ================================================
// user/user_profile.php - Customer Profile
// ================================================

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: user_login.php');
    exit();
}

require_once '../config.php';

$customer_id = (int)$_SESSION['customer_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$error_key = '';
$success = '';
$success_key = '';

// ================================================
// HANDLE PROFILE UPDATE
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_key = 'prof_invalid_request';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        if (empty($full_name) || empty($phone) || empty($email)) {
            $error_key = 'prof_fill_required';
        } elseif (mb_strlen($full_name) < 3) {
            $error_key = 'prof_name_short';
        } elseif (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
            $error_key = 'prof_invalid_phone';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_key = 'prof_invalid_email';
        } else {
            // Check if phone is used by another customer
            $stmt = mysqli_prepare($conn, "SELECT id FROM customer WHERE phone = ? AND id != ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "si", $phone, $customer_id);
            mysqli_stmt_execute($stmt);
            $phone_exists = mysqli_stmt_get_result($stmt)->num_rows > 0;
            mysqli_stmt_close($stmt);

            // Check if email is used by another customer
            $stmt = mysqli_prepare($conn, "SELECT id FROM customer WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) AND id != ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "si", $email, $customer_id);
            mysqli_stmt_execute($stmt);
            $email_exists = mysqli_stmt_get_result($stmt)->num_rows > 0;
            mysqli_stmt_close($stmt);

            if ($phone_exists) {
                $error_key = 'prof_phone_used';
            } elseif ($email_exists) {
                $error_key = 'prof_email_used';
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE customer 
                    SET full_name = ?, phone = ?, email = ?
                    WHERE id = ?
                ");
                mysqli_stmt_bind_param($stmt, "sssi", $full_name, $phone, $email, $customer_id);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);

                    $_SESSION['customer_name']  = $full_name;
                    $_SESSION['customer_email'] = $email;
                    $_SESSION['customer_phone'] = $phone;

                    $success_key = 'prof_updated';

                    if (function_exists('log_activity')) {
                        log_activity($conn, 'update', 'customer', $customer_id, "name:$full_name");
                    }
                } else {
                    $error_key = 'prof_failed';
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// ================================================
// HANDLE PASSWORD CHANGE
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_key = 'prof_invalid_request';
    } else {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm      = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password) || empty($confirm)) {
            $error_key = 'prof_fill_required';
        } elseif (strlen($new_password) < 6) {
            $error_key = 'prof_password_short';
        } elseif ($new_password !== $confirm) {
            $error_key = 'prof_password_mismatch';
        } else {
            // Get current password
            $stmt = mysqli_prepare($conn, "SELECT password FROM customer WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
            mysqli_stmt_close($stmt);

            if (!$row || $row['password'] !== $old_password) {
                $error_key = 'prof_old_wrong';
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE customer SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $new_password, $customer_id);

                if (mysqli_stmt_execute($stmt)) {
                    $success_key = 'prof_password_changed';

                    if (function_exists('log_activity')) {
                        log_activity($conn, 'update', 'customer', $customer_id, 'password changed');
                    }
                } else {
                    $error_key = 'prof_failed';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// ================================================
// GET CUSTOMER DATA
// ================================================
$stmt = mysqli_prepare($conn, "
    SELECT id, full_name, phone, email, created_at
    FROM customer
    WHERE id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$customer) {
    header('Location: user_logout.php');
    exit();
}

// ================================================
// STATS
// ================================================
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM `order` WHERE customer_id = ?");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$total_orders = mysqli_stmt_get_result($stmt)->fetch_assoc()['count'] ?? 0;
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT SUM(od.quantity * od.price_at_order) as total
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    WHERE o.customer_id = ? AND o.status != 'cancelled'
");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$total_spent = mysqli_stmt_get_result($stmt)->fetch_assoc()['total'] ?? 0;
mysqli_stmt_close($stmt);

$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Translations
$translations = [
    'en' => [
        'prof_invalid_request'   => 'Invalid request. Please try again.',
        'prof_fill_required'     => 'Please fill in all required fields.',
        'prof_name_short'        => 'Full name must be at least 3 characters.',
        'prof_invalid_phone'     => 'Please enter a valid phone number.',
        'prof_invalid_email'     => 'Please enter a valid email address.',
        'prof_phone_used'        => 'This phone number is used by another account.',
        'prof_email_used'        => 'This email is used by another account.',
        'prof_updated'           => 'Profile updated successfully!',
        'prof_failed'            => 'Update failed. Please try again.',
        'prof_password_short'    => 'Password must be at least 6 characters.',
        'prof_password_mismatch' => 'Passwords do not match.',
        'prof_old_wrong'         => 'Current password is incorrect.',
        'prof_password_changed'  => 'Password changed successfully!'
    ],
    'sw' => [
        'prof_invalid_request'   => 'Ombi si sahihi. Tafadhali jaribu tena.',
        'prof_fill_required'     => 'Tafadhali jaza sehemu zote zinazohitajika.',
        'prof_name_short'        => 'Jina kamili liwe na herufi 3 au zaidi.',
        'prof_invalid_phone'     => 'Tafadhali weka namba sahihi ya simu.',
        'prof_invalid_email'     => 'Tafadhali weka barua pepe sahihi.',
        'prof_phone_used'        => 'Namba hii ya simu inatumiwa na akaunti nyingine.',
        'prof_email_used'        => 'Barua pepe hii inatumiwa na akaunti nyingine.',
        'prof_updated'           => 'Wasifu umesasishwa kikamilifu!',
        'prof_failed'            => 'Usasishaji umeshindwa. Tafadhali jaribu tena.',
        'prof_password_short'    => 'Neno la siri liwe na herufi 6 au zaidi.',
        'prof_password_mismatch' => 'Maneno ya siri hayafanani.',
        'prof_old_wrong'         => 'Neno la siri la sasa si sahihi.',
        'prof_password_changed'  => 'Neno la siri limebadilishwa kikamilifu!'
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
    <title data-i18n="prof_title">My Profile - Cafeteria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../lang.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: linear-gradient(135deg, rgba(224,123,57,0.05) 0%, rgba(200,106,47,0.05) 100%),
                        url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 20px 15px;
        }

        .profile-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover { background: #5a6268; }

        /* HEADER */
        .profile-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2em;
            flex-shrink: 0;
        }

        .profile-header-info h1 {
            margin: 0 0 5px 0;
            font-size: 1.5em;
            font-weight: 700;
        }

        .profile-header-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9em;
        }

        /* STATS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 14px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-box .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1em;
            color: white;
            flex-shrink: 0;
        }

        .stat-box .stat-value {
            font-size: 1.1em;
            font-weight: 700;
            color: #333;
            line-height: 1.1;
        }

        .stat-box .stat-label {
            color: #888;
            font-size: 0.72em;
            margin-top: 2px;
        }

        /* CARDS */
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 22px;
            margin-bottom: 18px;
        }

        .profile-card h3 {
            margin: 0 0 18px 0;
            color: #333;
            font-size: 1.05em;
            padding-bottom: 10px;
            border-bottom: 2px solid #E07B39;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-card h3 i { color: #E07B39; }

        .form-group { margin-bottom: 15px; }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 0.85em;
        }

        .form-group label i {
            color: #E07B39;
            margin-right: 4px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.95em;
            font-family: inherit;
            background: #f8f9fa;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #E07B39;
            background: white;
            box-shadow: 0 0 0 3px rgba(224, 123, 57, 0.1);
        }

        .form-group input:disabled {
            background: #e9ecef;
            color: #666;
            cursor: not-allowed;
        }

        .btn-save {
            background: linear-gradient(135deg, #E07B39, #C86A2F);
            color: white;
            border: none;
            padding: 11px 24px;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .btn-save:hover { transform: translateY(-2px); }

        .btn-change-pw {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 11px 24px;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .btn-change-pw:hover { transform: translateY(-2px); }

        /* ALERTS */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #2ecc71; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #e74c3c; }

        @media (max-width: 600px) {
            .stats-row { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-header-info h1 { font-size: 1.2em; }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="user_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span data-i18n="prof_back_dashboard">Back to Dashboard</span>
        </a>

        <!-- PROFILE HEADER -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-header-info">
                <h1><?php echo htmlspecialchars($customer['full_name']); ?></h1>
                <p>
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($customer['email']); ?>
                </p>
                <p style="margin-top: 3px;">
                    <i class="fas fa-calendar-alt"></i>
                    <span data-i18n="prof_member_since">Member since</span>
                    <?php echo date('M Y', strtotime($customer['created_at'] ?? 'now')); ?>
                </p>
            </div>
        </div>

        <!-- ALERTS -->
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
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-icon" style="background: linear-gradient(135deg, #E07B39, #C86A2F);">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label" data-i18n="prof_total_orders">Total Orders</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <div class="stat-value">$<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label" data-i18n="prof_total_spent">Total Spent</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $cart_count; ?></div>
                    <div class="stat-label" data-i18n="prof_cart_items">Items in Cart</div>
                </div>
            </div>
        </div>

        <!-- UPDATE PROFILE -->
        <div class="profile-card">
            <h3>
                <i class="fas fa-user-edit"></i>
                <span data-i18n="prof_edit_title">Edit Profile</span>
            </h3>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i>
                        <span data-i18n="prof_full_name">Full Name</span> *
                    </label>
                    <input type="text" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($customer['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i>
                        <span data-i18n="prof_phone">Phone Number</span> *
                    </label>
                    <input type="tel" id="phone" name="phone" required
                           value="<?php echo htmlspecialchars($customer['phone']); ?>">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        <span data-i18n="prof_email">Email Address</span> *
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($customer['email']); ?>">
                </div>

                <button type="submit" name="update_profile" class="btn-save">
                    <i class="fas fa-save"></i>
                    <span data-i18n="prof_save">Save Changes</span>
                </button>
            </form>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="profile-card">
            <h3>
                <i class="fas fa-lock"></i>
                <span data-i18n="prof_change_password">Change Password</span>
            </h3>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="old_password">
                        <i class="fas fa-lock"></i>
                        <span data-i18n="prof_old_password">Current Password</span> *
                    </label>
                    <input type="password" id="old_password" name="old_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-key"></i>
                        <span data-i18n="prof_new_password">New Password</span> *
                    </label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-key"></i>
                        <span data-i18n="prof_confirm_password">Confirm New Password</span> *
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" name="change_password" class="btn-change-pw">
                    <i class="fas fa-shield-alt"></i>
                    <span data-i18n="prof_change_pw_btn">Change Password</span>
                </button>
            </form>
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