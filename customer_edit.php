<?php
// customer_edit.php
require_once 'config.php';
check_login();

$message = '';
$message_type = '';
$message_key = '';
$customer = null;

// Get customer ID from URL
$customer_id = intval($_GET['id'] ?? 0);

if ($customer_id <= 0) {
    header('Location: customers.php');
    exit;
}

// Fetch customer details
$result = mysqli_query($conn, "SELECT * FROM customer WHERE id = $customer_id");
if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: customers.php');
    exit;
}
$customer = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_customer'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $old_name = $customer['full_name'] ?? '';
    $old_phone = $customer['phone'] ?? '';

    $sql = "UPDATE customer SET full_name = '$full_name', phone = '$phone' WHERE id = $customer_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['flash_message_key'] = 'ce_success_update';
        $_SESSION['flash_type'] = 'success';

        if (function_exists('log_activity')) {
            $details = 'name:' . $old_name . '->' . $full_name . ';phone:' . $old_phone . '->' . $phone;
            log_activity($conn, 'update', 'customer', $customer_id, $details);
        }

        header('Location: customer_edit.php?id=' . $customer_id);
        exit();
    } else {
        $_SESSION['flash_message'] = 'Error updating customer: ' . mysqli_error($conn);
        $_SESSION['flash_type'] = 'error';
    }
}

// Flash message
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}
if (isset($_SESSION['flash_message_key'])) {
    $message_key = $_SESSION['flash_message_key'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message_key'], $_SESSION['flash_type']);
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

$messageTranslations = [
    'en' => [
        'ce_success_update' => 'Customer updated successfully!'
    ],
    'sw' => [
        'ce_success_update' => 'Mteja amesasishwa kikamilifu!'
    ]
];
$message = $message_key && isset($messageTranslations[$lang][$message_key])
    ? $messageTranslations[$lang][$message_key]
    : $message;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="ce_title">Edit Customer - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-edit"></i>
                    <span data-i18n="ce_title">Edit Customer</span>
                </h1>
                <a href="customers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="ce_back">Back to Customers</span>
                </a>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($customer): ?>
            <div class="card">
                <div class="card-header">
                    <h2>
                        <span data-i18n="ce_edit_customer">Edit Customer</span>
                        #<?php echo str_pad($customer['id'], 3, '0', STR_PAD_LEFT); ?>
                    </h2>
                </div>

                <form method="POST" class="form-container">
                    <div class="form-group">
                        <label data-i18n="ce_full_name">Full Name *</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?php echo htmlspecialchars($customer['full_name']); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label data-i18n="ce_phone">Phone Number</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_customer" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span data-i18n="ce_update">Update Customer</span>
                        </button>
                        <a href="customers.php" class="btn btn-secondary" data-i18n="ce_cancel">Cancel</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span data-i18n="ce_not_found">Customer not found.</span>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        window.addEventListener('languageChanged', () => {
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
        });
    </script>
</body>
</html>