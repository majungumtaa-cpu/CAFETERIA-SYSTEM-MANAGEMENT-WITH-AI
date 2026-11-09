 <?php
// ================================================
// customers.php - Customer Management (Hard Delete + 1s Rate Limit)
// ================================================
require_once 'config.php';
check_login();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$message_key = '';

// ================================================
// RATE LIMITING (Sekunde 1)
// ================================================
$RATE_LIMIT_SECONDS = 1;

function check_rate_limit($key = 'last_delete_time') {
    global $RATE_LIMIT_SECONDS;
    if (isset($_SESSION[$key])) {
        $elapsed = time() - $_SESSION[$key];
        if ($elapsed < $RATE_LIMIT_SECONDS) {
            return $RATE_LIMIT_SECONDS - $elapsed;
        }
    }
    return 0;
}

function update_rate_limit($key = 'last_delete_time') {
    $_SESSION[$key] = time();
}

// ================================================
// HANDLE DELETE CUSTOMER (GET)
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $customer_id = intval($_GET['delete']);

    if ($customer_id <= 0) {
        $_SESSION['flash_message_key'] = 'cust_error_invalid_id';
        $_SESSION['flash_type'] = 'error';
        header('Location: customers.php');
        exit();
    }

    $wait = check_rate_limit('last_delete_time');
    if ($wait > 0) {
        $_SESSION['flash_message'] = "Please wait $wait second(s) before deleting another customer.";
        $_SESSION['flash_type'] = 'error';
        header('Location: customers.php');
        exit();
    }

    $check_stmt = $conn->prepare("SELECT id, full_name FROM customer WHERE id = ?");
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        $_SESSION['flash_message'] = "Customer #$customer_id not found.";
        $_SESSION['flash_type'] = 'error';
        header('Location: customers.php');
        exit();
    }

    $customer_data = $check_result->fetch_assoc();
    $check_stmt->close();

    mysqli_begin_transaction($conn);

    try {
        $order_ids_query = mysqli_query($conn, "SELECT id FROM `order` WHERE customer_id = $customer_id");
        $order_ids = [];
        if ($order_ids_query) {
            while ($row = mysqli_fetch_assoc($order_ids_query)) {
                $order_ids[] = (int)$row['id'];
            }
        }

        if (!empty($order_ids)) {
            $ids_string = implode(',', $order_ids);
            mysqli_query($conn, "DELETE FROM order_detail WHERE order_id IN ($ids_string)");
            mysqli_query($conn, "DELETE FROM payment WHERE order_id IN ($ids_string)");
        }

        mysqli_query($conn, "DELETE FROM `order` WHERE customer_id = $customer_id");
        mysqli_query($conn, "DELETE FROM feedback WHERE customer_id = $customer_id");
        mysqli_query($conn, "DELETE FROM event_customer WHERE customer_id = $customer_id");
        mysqli_query($conn, "DELETE FROM reservation WHERE customer_id = $customer_id");

        $email_query = mysqli_query($conn, "SELECT email FROM customer WHERE id = $customer_id");
        if ($email_row = mysqli_fetch_assoc($email_query)) {
            $email = mysqli_real_escape_string($conn, $email_row['email'] ?? '');
            if (!empty($email)) {
                mysqli_query($conn, "DELETE FROM password_resets WHERE email = '$email'");
            }
        }

        $delete_stmt = $conn->prepare("DELETE FROM customer WHERE id = ?");
        $delete_stmt->bind_param("i", $customer_id);
        $delete_stmt->execute();
        $affected = $delete_stmt->affected_rows;
        $delete_stmt->close();

        if ($affected === 0) {
            throw new Exception('Customer delete affected 0 rows.');
        }

        mysqli_commit($conn);

        $_SESSION['flash_message'] = "Customer '{$customer_data['full_name']}' and ALL related data permanently deleted!";
        $_SESSION['flash_type'] = 'success';

        if (function_exists('log_activity')) {
            log_activity($conn, 'delete', 'customer', $customer_id, 'Customer + all related data deleted');
        }

        update_rate_limit('last_delete_time');

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['flash_message'] = 'Delete failed: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }

    header('Location: customers.php');
    exit();
}

// ================================================
// HANDLE ADD CUSTOMER
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($full_name) || empty($phone)) {
        $_SESSION['flash_message_key'] = 'cust_error_fill_required';
        $_SESSION['flash_type'] = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO customer (full_name, phone, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name, $phone, $email);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $_SESSION['flash_message_key'] = 'cust_success_add';
            $_SESSION['flash_type'] = 'success';

            if (function_exists('log_activity')) {
                log_activity($conn, 'add', 'customer', $new_id, "name:$full_name;phone:$phone");
            }
        } else {
            $_SESSION['flash_message'] = 'Error adding customer: ' . $stmt->error;
            $_SESSION['flash_type'] = 'error';
        }
        $stmt->close();
    }

    header('Location: customers.php');
    exit();
}

// ================================================
// FLASH MESSAGE
// ================================================
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

// ================================================
// FETCH CUSTOMERS
// ================================================
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM customer WHERE id > 0";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
    $types = "sss";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$customers = $stmt->get_result();
$total_customers = $customers->num_rows;
$stmt->close();

$wait_time = check_rate_limit('last_delete_time');

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

$messageTranslations = [
    'en' => [
        'cust_error_invalid_id'      => 'Invalid customer ID.',
        'cust_error_fill_required'   => 'Please fill in all required fields.',
        'cust_success_add'           => 'Customer added successfully!'
    ],
    'sw' => [
        'cust_error_invalid_id'      => 'Kitambulisho cha mteja si sahihi.',
        'cust_error_fill_required'   => 'Tafadhali jaza sehemu zote zinazohitajika.',
        'cust_success_add'           => 'Mteja ameongezwa kikamilifu!'
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
    <title data-i18n="cust_title">Customers - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .modal-header h2 {
            color: #E07B39;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 26px;
            cursor: pointer;
            color: #999;
            line-height: 1;
        }
        .modal-close:hover { color: #e74c3c; }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
        }
        .form-group label i { color: #E07B39; margin-right: 5px; }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.95em;
            font-family: inherit;
            background: #f8f9fa;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #E07B39;
            background: white;
            box-shadow: 0 0 10px rgba(224, 123, 57, 0.2);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-actions .btn { flex: 1; justify-content: center; }

        .search-bar {
            background: white;
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-bar input {
            flex: 1;
            min-width: 200px;
            padding: 10px 14px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.9em;
            font-family: inherit;
        }
        .search-bar input:focus {
            outline: none;
            border-color: #E07B39;
        }

        .btn-delete.disabled {
            background: #ccc !important;
            color: #666 !important;
            cursor: not-allowed !important;
            transform: none !important;
            pointer-events: none;
        }

        .rate-notice {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
            border-left: 4px solid #f39c12;
        }

        .id-warning {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #e74c3c;
            font-size: 0.9em;
        }
        .id-warning strong { color: #e74c3c; }
        .id-warning pre {
            background: #f5f5f5;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-users"></i>
                    <span data-i18n="cust_title">Customer Management</span>
                </h1>
                <button onclick="openAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span data-i18n="cust_add">Add Customer</span>
                </button>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- RATE LIMIT NOTICE -->
            <div id="rateLimitNotice" class="rate-notice" style="display: none;">
                <i class="fas fa-clock"></i>
                <span>
                    <strong data-i18n="cust_please_wait">Please wait:</strong>
                    <span id="rateLimitCounter">1</span>
                    <span data-i18n="cust_seconds">second(s) before deleting another customer.</span>
                </span>
            </div>

            <!-- SEARCH -->
            <form method="GET" class="search-bar">
                <input type="text" name="search"
                       data-i18n="cust_search_ph" data-i18n-placeholder
                       placeholder="Search by name, phone, or email..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    <span data-i18n="cust_search">Search</span>
                </button>
                <?php if (!empty($search)): ?>
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        <span data-i18n="cust_clear">Clear</span>
                    </a>
                <?php endif; ?>
            </form>

            <!-- CUSTOMERS TABLE -->
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        <span data-i18n="cust_all">All Customers</span>
                    </h2>
                    <span>
                        <?php echo $total_customers; ?>
                        <span data-i18n="cust_customers_count">customer(s)</span>
                    </span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="cust_id">ID</th>
                                <th data-i18n="cust_full_name">Full Name</th>
                                <th data-i18n="cust_phone">Phone</th>
                                <th data-i18n="cust_email">Email</th>
                                <th data-i18n="cust_actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_customers > 0): ?>
                                <?php while($customer = mysqli_fetch_assoc($customers)): ?>
                                <tr>
                                    <td><strong style="color: #E07B39;">#<?php echo str_pad($customer['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="customer_edit.php?id=<?php echo $customer['id']; ?>"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                            <span data-i18n="cust_edit">Edit</span>
                                        </a>

                                        <a href="customers.php?delete=<?php echo $customer['id']; ?>"
                                           class="btn btn-sm btn-danger btn-delete"
                                           data-customer-id="<?php echo $customer['id']; ?>"
                                           data-customer-name="<?php echo htmlspecialchars($customer['full_name'], ENT_QUOTES); ?>"
                                           onclick="return handleDeleteClick(event, this);">
                                            <i class="fas fa-trash"></i>
                                            <span data-i18n="cust_delete">Delete</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                        <i class="fas fa-users-slash" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                        <span data-i18n="cust_no_customers">No customers found</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD CUSTOMER MODAL -->
    <div id="addCustomerModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-user-plus"></i>
                    <span data-i18n="cust_add_new">Add New Customer</span>
                </h2>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>

            <form method="POST" id="addCustomerForm">
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        <span data-i18n="cust_full_name_label">Full Name *</span>
                    </label>
                    <input type="text" name="full_name" class="form-control" required
                           data-i18n="cust_full_name_ph" data-i18n-placeholder
                           placeholder="e.g., John Doe" autofocus>
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-phone"></i>
                        <span data-i18n="cust_phone_label">Phone Number *</span>
                    </label>
                    <input type="tel" name="phone" class="form-control" required
                           data-i18n="cust_phone_ph" data-i18n-placeholder
                           placeholder="+255 712 345 678">
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        <span data-i18n="cust_email_label">Email Address</span>
                    </label>
                    <input type="email" name="email" class="form-control"
                           data-i18n="cust_email_ph" data-i18n-placeholder
                           placeholder="you@example.com">
                </div>

                <div class="modal-actions">
                    <button type="submit" name="add_customer" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span data-i18n="cust_save">Save Customer</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">
                        <span data-i18n="cust_cancel">Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 style="color: #e74c3c;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span data-i18n="cust_confirm_delete">Confirm Delete</span>
                </h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>

            <p style="color: #555; margin-bottom: 15px;">
                <span data-i18n="cust_confirm_msg">Are you sure you want to permanently delete customer</span>
                <strong id="deleteCustomerName"></strong>?
            </p>

            <div style="background: #f8d7da; border-left: 4px solid #e74c3c; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; color: #721c24;">
                <i class="fas fa-exclamation-circle"></i>
                <strong data-i18n="cust_warning">Warning:</strong>
                <span data-i18n="cust_warning_desc">All related data will be deleted:</span>
                <ul style="margin: 8px 0 0 20px; padding: 0;">
                    <li data-i18n="cust_del_orders">Orders & Order Details</li>
                    <li data-i18n="cust_del_payments">Payments</li>
                    <li data-i18n="cust_del_feedback">Feedback</li>
                    <li data-i18n="cust_del_events">Event Registrations</li>
                    <li data-i18n="cust_del_reservations">Reservations</li>
                </ul>
                <p style="margin-top: 8px;" data-i18n="cust_cannot_undo">This action cannot be undone!</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <span data-i18n="cust_cancel">Cancel</span>
                </button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    <span data-i18n="cust_yes_delete">Yes, Delete Permanently</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        var RATE_LIMIT_SECONDS = 1;
        var deleteUrl = '';
        var isDeleting = false;

        function handleDeleteClick(event, element) {
            event.preventDefault();
            if (isDeleting) return false;

            var lastDelete = parseInt(localStorage.getItem('lastDeleteTime') || '0');
            var elapsed = (Date.now() - lastDelete) / 1000;

            if (elapsed < RATE_LIMIT_SECONDS) {
                var wait = Math.ceil(RATE_LIMIT_SECONDS - elapsed);
                showRateLimitNotice(wait);
                return false;
            }

            deleteUrl = element.href;
            var customerName = element.getAttribute('data-customer-name');
            document.getElementById('deleteCustomerName').textContent = customerName;
            document.getElementById('deleteModal').classList.add('active');
            return false;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!deleteUrl || isDeleting) return;
            isDeleting = true;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
            localStorage.setItem('lastDeleteTime', Date.now().toString());
            window.location.href = deleteUrl;
        });

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteUrl = '';
        }
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        function openAddModal() {
            document.getElementById('addCustomerModal').classList.add('active');
        }
        function closeAddModal() {
            document.getElementById('addCustomerModal').classList.remove('active');
        }
        document.getElementById('addCustomerModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddModal();
        });

        function showRateLimitNotice(seconds) {
            var notice = document.getElementById('rateLimitNotice');
            var counter = document.getElementById('rateLimitCounter');
            counter.textContent = seconds;
            notice.style.display = 'flex';
            document.querySelectorAll('.btn-delete').forEach(function(btn) {
                btn.classList.add('disabled');
            });
            var interval = setInterval(function() {
                seconds--;
                counter.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(interval);
                    notice.style.display = 'none';
                    document.querySelectorAll('.btn-delete').forEach(function(btn) {
                        btn.classList.remove('disabled');
                    });
                }
            }, 1000);
        }

        window.addEventListener('load', function() {
            var lastDelete = parseInt(localStorage.getItem('lastDeleteTime') || '0');
            var elapsed = (Date.now() - lastDelete) / 1000;
            if (elapsed < RATE_LIMIT_SECONDS) {
                var wait = Math.ceil(RATE_LIMIT_SECONDS - elapsed);
                showRateLimitNotice(wait);
            } else {
                isDeleting = false;
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddModal();
                closeDeleteModal();
            }
        });

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