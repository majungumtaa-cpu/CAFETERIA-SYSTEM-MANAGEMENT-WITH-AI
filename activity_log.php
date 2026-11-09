<?php
// ================================================
// activity_log.php - View & Delete Logs
// ================================================
require_once 'config.php';
check_login();

// Admin only
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$message_key = '';

// ================================================
// DELETE SINGLE LOG
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_single_log'])) {
    $delete_id = intval($_POST['log_id'] ?? 0);

    if ($delete_id > 0) {
        $sql = "DELETE FROM activity_log WHERE id = $delete_id";

        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                $_SESSION['flash_message'] = "Log #$delete_id deleted successfully!";
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = "Log #$delete_id not found.";
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'Error: ' . mysqli_error($conn);
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = 'Invalid log ID.';
        $_SESSION['flash_type'] = 'error';
    }

    header('Location: activity_log.php');
    exit();
}

// ================================================
// CLEAR ALL LOGS
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_logs'])) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM activity_log");
    $count = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;

    if (mysqli_query($conn, "DELETE FROM activity_log")) {
        $_SESSION['flash_message'] = "All logs cleared! ($count entries deleted)";
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Error clearing logs: ' . mysqli_error($conn);
        $_SESSION['flash_type'] = 'error';
    }

    header('Location: activity_log.php');
    exit();
}

// ================================================
// SHOW FLASH MESSAGE
// ================================================
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// ================================================
// FILTERS
// ================================================
$entity_filter = isset($_GET['entity']) ? mysqli_real_escape_string($conn, $_GET['entity']) : '';
$action_filter = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$user_filter   = isset($_GET['user'])   ? mysqli_real_escape_string($conn, $_GET['user'])   : '';
$date_from     = isset($_GET['from'])   ? mysqli_real_escape_string($conn, $_GET['from'])   : '';
$date_to       = isset($_GET['to'])     ? mysqli_real_escape_string($conn, $_GET['to'])     : '';

$where = [];
if ($entity_filter !== '') $where[] = "entity = '$entity_filter'";
if ($action_filter !== '') $where[] = "action = '$action_filter'";
if ($user_filter !== '')   $where[] = "username LIKE '%$user_filter%'";
if ($date_from !== '')     $where[] = "DATE(created_at) >= '$date_from'";
if ($date_to !== '')       $where[] = "DATE(created_at) <= '$date_to'";

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT * FROM activity_log $where_sql ORDER BY created_at DESC LIMIT 500";
$logs = mysqli_query($conn, $query);

if (!$logs) {
    die("<b>SQL Error:</b> " . mysqli_error($conn));
}

$total_logs = mysqli_num_rows($logs);

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Action translations
$actionTranslations = [
    'en' => [
        'add'      => 'Add',
        'update'   => 'Update',
        'delete'   => 'Delete',
        'register' => 'Register',
        'login'    => 'Login',
        'logout'   => 'Logout'
    ],
    'sw' => [
        'add'      => 'Ongeza',
        'update'   => 'Sasisha',
        'delete'   => 'Futa',
        'register' => 'Sajili',
        'login'    => 'Ingia',
        'logout'   => 'Toka'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-i18n="log_title">Activity Log</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="lang.js"></script>
    <style>
        .log-table td, .log-table th { padding: 8px; font-size: 0.9em; }
        .log-details {
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            max-width: 280px;
            word-break: break-word;
        }
        .page-actions { display: flex; gap: 10px; align-items: center; }
        .btn-danger-clear {
            background: #dc3545; color: white; padding: 10px 18px;
            border: none; border-radius: 6px; cursor: pointer;
            font-family: inherit; font-weight: 600;
            font-size: 14px; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s;
        }
        .btn-danger-clear:hover { background: #c82333; transform: translateY(-2px); }
        .btn-delete-sm {
            background: #dc3545; color: white; padding: 6px 12px;
            border: none; border-radius: 4px; cursor: pointer;
            text-decoration: none; font-size: 12px;
            display: inline-flex; align-items: center; gap: 4px;
            transition: all 0.25s;
            font-family: inherit;
            font-weight: 600;
        }
        .btn-delete-sm:hover { background: #c82333; transform: scale(1.05); }
        .log-id-cell { font-weight: 700; color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h1>
                    <i class="fas fa-clipboard-list"></i>
                    <span data-i18n="log_title">Activity Log</span>
                </h1>
                <div class="page-actions">
                    <form method="POST" style="display: inline;"
                          onsubmit="return confirm('<?= $lang === 'sw' ? 'ONYO: Futa kumbukumbu ZOTE?\n\nHii haiwezi kutenduliwa!\n\nUna uhakika?' : 'WARNING: Delete ALL activity logs?\n\nThis cannot be undone!\n\nAre you sure?' ?>');">
                        <button type="submit" name="clear_all_logs" class="btn-danger-clear">
                            <i class="fas fa-trash-alt"></i>
                            <span data-i18n="log_clear_all">Clear All Logs</span>
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 data-i18n="log_recent_activity">Recent Activity</h2>
                    <span>
                        <?php echo $total_logs; ?>
                        <span data-i18n="log_entries">entries</span>
                    </span>
                </div>
                <div class="table-container">
                    <table class="data-table log-table">
                        <thead>
                            <tr>
                                <th data-i18n="log_id">ID</th>
                                <th data-i18n="log_user">User</th>
                                <th data-i18n="log_action">Action</th>
                                <th data-i18n="log_entity">Entity</th>
                                <th data-i18n="log_entity_id">Entity ID</th>
                                <th data-i18n="log_details">Details</th>
                                <th data-i18n="log_ip">IP</th>
                                <th data-i18n="log_when">When</th>
                                <th data-i18n="log_delete">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_logs > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($logs)):
                                    $actionKey = strtolower($row['action'] ?? '');
                                    $actionText = $actionTranslations[$lang][$actionKey] ?? ucfirst($actionKey);
                                ?>
                                <tr>
                                    <td class="log-id-cell">#<?php echo intval($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username'] ?? 'System'); ?></td>
                                    <td>
                                        <span class="status-badge
                                            <?php
                                            switch ($row['action']) {
                                                case 'add': echo 'status-active'; break;
                                                case 'update': echo 'status-pending'; break;
                                                case 'delete': echo 'status-danger'; break;
                                                default: echo 'status-inactive';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars($actionText); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['entity'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['entity_id'] ?? '')); ?></td>
                                    <td class="log-details"><?php echo htmlspecialchars($row['details'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['ip_address'] ?? ''); ?></td>
                                    <td style="white-space: nowrap;"><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline; margin: 0;"
                                              onsubmit="return confirm('<?= $lang === 'sw' ? 'Futa kumbukumbu' : 'Delete log' ?> #<?php echo intval($row['id']); ?>?');">
                                            <input type="hidden" name="log_id" value="<?php echo intval($row['id']); ?>">
                                            <button type="submit" name="delete_single_log" class="btn-delete-sm">
                                                <i class="fas fa-trash"></i>
                                                <span data-i18n="log_delete">Delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                        <i class="fas fa-inbox" style="font-size: 2.5rem; display: block; margin-bottom: 12px; color: #ddd;"></i>
                                        <p data-i18n="log_no_logs">No activity logs found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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