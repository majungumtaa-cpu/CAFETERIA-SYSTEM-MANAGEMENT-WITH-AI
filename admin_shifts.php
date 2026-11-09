<?php
// admin_shifts.php - Admin manages employee shifts
require_once 'config.php';
check_login();

// ================== KINGA: ADMIN PEKEE ==================
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    $_SESSION['flash_message'] = 'Access denied! Only admins can manage shifts.';
    $_SESSION['flash_type'] = 'error';
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$message_key = '';

// ================== ONGEZA SHIFT ==================
if (isset($_POST['add_shift'])) {
    $employee_id = intval($_POST['employee_id']);
    $shift_date = mysqli_real_escape_string($conn, $_POST['shift_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    if ($employee_id <= 0 || empty($shift_date) || empty($start_time) || empty($end_time)) {
        $_SESSION['flash_message_key'] = 'sh_error_fill';
        $_SESSION['flash_type'] = 'error';
    } elseif ($start_time >= $end_time) {
        $_SESSION['flash_message_key'] = 'sh_error_time';
        $_SESSION['flash_type'] = 'error';
    } else {
        $sql = "INSERT INTO shift (employee_id, shift_date, start_time, end_time, notes) 
                VALUES ($employee_id, '$shift_date', '$start_time', '$end_time', '$notes')";

        if (mysqli_query($conn, $sql)) {
            $shift_id = mysqli_insert_id($conn);
            $_SESSION['flash_message_key'] = 'sh_success_add';
            $_SESSION['flash_type'] = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'add', 'shift', $shift_id, "employee:$employee_id;date:$shift_date");
            }
        } else {
            $_SESSION['flash_message'] = 'Error adding shift: ' . mysqli_error($conn);
            $_SESSION['flash_type'] = 'error';
        }
    }
    header('Location: admin_shifts.php');
    exit();
}

// ================== FUTA SHIFT ==================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    $sql = "DELETE FROM shift WHERE id = $delete_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['flash_message_key'] = 'sh_success_delete';
        $_SESSION['flash_type'] = 'success';
        if (function_exists('log_activity')) {
            log_activity($conn, 'delete', 'shift', $delete_id, 'Shift deleted');
        }
    } else {
        $_SESSION['flash_message'] = 'Error deleting shift: ' . mysqli_error($conn);
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: admin_shifts.php');
    exit();
}

// ================== ONYESHA FLASH MESSAGE ==================
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

// ================== FILTER ==================
$employee_filter = isset($_GET['employee']) ? intval($_GET['employee']) : 0;
$filter_sql = $employee_filter > 0 ? "WHERE s.employee_id = $employee_filter" : "";

// ================== FETCH SHIFTS ==================
$shifts = mysqli_query($conn, "
    SELECT s.*, e.full_name, e.phone
    FROM shift s
    LEFT JOIN employee e ON s.employee_id = e.id
    $filter_sql
    ORDER BY s.shift_date DESC, s.start_time ASC
    LIMIT 100
");

if (!$shifts) die("<b>SQL Error (Shifts):</b> " . mysqli_error($conn));

// ================== FETCH EMPLOYEES ==================
$employees = mysqli_query($conn, "SELECT id, full_name FROM employee ORDER BY full_name");
if (!$employees) die("<b>SQL Error (Employees):</b> " . mysqli_error($conn));

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'sh_error_fill'     => 'Please fill in all required fields!',
        'sh_error_time'     => 'End time must be after start time!',
        'sh_success_add'    => 'Shift added successfully!',
        'sh_success_delete' => 'Shift deleted successfully!'
    ],
    'sw' => [
        'sh_error_fill'     => 'Tafadhali jaza sehemu zote zinazohitajika!',
        'sh_error_time'     => 'Muda wa kumaliza uwe baada ya muda wa kuanza!',
        'sh_success_add'    => 'Zamu imeongezwa kikamilifu!',
        'sh_success_delete' => 'Zamu imefutwa kikamilifu!'
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
    <title data-i18n="sh_title">Manage Shifts - Cafeteria Management</title>
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
                    <i class="fas fa-clock"></i>
                    <span data-i18n="sh_title">Manage Shifts</span>
                </h1>
                <button onclick="document.getElementById('addShiftModal').style.display='flex'" 
                        class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span data-i18n="sh_add">Add Shift</span>
                </button>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="card">
                <form method="GET" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                        <label data-i18n="sh_filter_employee">Filter by Employee:</label>
                        <select name="employee" class="form-control">
                            <option value="0" data-i18n="sh_all_employees">All Employees</option>
                            <?php
                            mysqli_data_seek($employees, 0);
                            while($emp = mysqli_fetch_assoc($employees)):
                            ?>
                            <option value="<?php echo $emp['id']; ?>"
                                    <?php echo $employee_filter == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                        <span data-i18n="sh_filter">Filter</span>
                    </button>
                    <a href="admin_shifts.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                        <span data-i18n="sh_clear">Clear</span>
                    </a>
                </form>
            </div>

            <!-- Shifts Table -->
            <div class="card">
                <div class="card-header">
                    <h2 data-i18n="sh_all_shifts">All Shifts</h2>
                    <span>
                        <?php echo mysqli_num_rows($shifts); ?>
                        <span data-i18n="sh_shifts_count">shift(s)</span>
                    </span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="sh_id">ID</th>
                                <th data-i18n="sh_employee">Employee</th>
                                <th data-i18n="sh_date">Date</th>
                                <th data-i18n="sh_start_time">Start Time</th>
                                <th data-i18n="sh_end_time">End Time</th>
                                <th data-i18n="sh_notes">Notes</th>
                                <th data-i18n="sh_actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($shift = mysqli_fetch_assoc($shifts)):
                                $is_past = strtotime($shift['shift_date']) < strtotime(date('Y-m-d'));
                            ?>
                            <tr style="<?php echo $is_past ? 'opacity: 0.6;' : ''; ?>">
                                <td>#<?php echo str_pad($shift['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($shift['full_name'] ?? 'N/A'); ?></strong>
                                    <br><small style="color: #888;"><?php echo htmlspecialchars($shift['phone'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($shift['shift_date'])); ?>
                                    <?php if ($is_past): ?>
                                        <span class="status-badge status-inactive" style="font-size: 10px;" data-i18n="sh_past">Past</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="fas fa-sign-in-alt" style="color: #2ecc71;"></i>
                                    <?php echo date('H:i', strtotime($shift['start_time'])); ?>
                                </td>
                                <td><i class="fas fa-sign-out-alt" style="color: #e74c3c;"></i>
                                    <?php echo date('H:i', strtotime($shift['end_time'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($shift['notes'] ?? '-'); ?></td>
                                <td>
                                    <a href="?delete=<?php echo $shift['id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('<?= $lang === 'sw' ? 'Futa zamu hii?' : 'Delete this shift?' ?>');">
                                        <i class="fas fa-trash"></i>
                                        <span data-i18n="sh_delete">Delete</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($shifts) == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-calendar-times" style="font-size: 2rem; color: #ddd;"></i>
                                    <p style="color: #999; margin-top: 10px;" data-i18n="sh_no_shifts">No shifts found. Click "Add Shift" to schedule.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Shift Modal -->
    <div id="addShiftModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; padding: 15px; box-sizing: border-box;">
        <div style="background-color: white; padding: 20px; width: 100%; max-width: 440px; border-radius: 8px; max-height: 95vh; overflow-y: auto; box-sizing: border-box;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 1.3em;" data-i18n="sh_add_new">Add New Shift</h2>
                <span style="cursor: pointer; font-size: 22px;"
                      onclick="document.getElementById('addShiftModal').style.display='none'">&times;</span>
            </div>

            <form method="POST">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px;" data-i18n="sh_employee_label">Employee *</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="" data-i18n="sh_select_employee">Select Employee</option>
                        <?php
                        mysqli_data_seek($employees, 0);
                        while($emp = mysqli_fetch_assoc($employees)):
                        ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['full_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px;" data-i18n="sh_shift_date">Shift Date *</label>
                    <input type="date" name="shift_date" class="form-control"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 13px;" data-i18n="sh_start_time_label">Start Time *</label>
                        <input type="time" name="start_time" class="form-control"
                               value="08:00" required>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 13px;" data-i18n="sh_end_time_label">End Time *</label>
                        <input type="time" name="end_time" class="form-control"
                               value="16:00" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px;" data-i18n="sh_notes_optional">Notes (Optional)</label>
                    <input type="text" name="notes" class="form-control"
                           data-i18n="sh_notes_ph" data-i18n-placeholder
                           placeholder="e.g., Morning shift">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" name="add_shift" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i>
                        <span data-i18n="sh_save">Save Shift</span>
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="document.getElementById('addShiftModal').style.display='none'">
                        <span data-i18n="sh_cancel">Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    window.onclick = function(event) {
        var modal = document.getElementById('addShiftModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    window.addEventListener('languageChanged', () => {
        if (typeof applyTranslations === 'function') {
            applyTranslations();
        }
    });
    </script>
</body>
</html>