<?php
// employees.php
require_once 'config.php';
check_login();

// Prevent employees from accessing this page
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);

        if (empty($full_name) || empty($phone) || empty($email)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            // Check if email already exists
            $check = mysqli_query($conn, "SELECT id FROM employee WHERE email = '$email'");
            if (mysqli_num_rows($check) > 0) {
                $message = 'This email is already registered for another employee.';
                $message_type = 'error';
            } else {
                $sql = "INSERT INTO employee (full_name, phone, email) 
                        VALUES ('$full_name', '$phone', '$email')";

                if (mysqli_query($conn, $sql)) {
                    $employee_id = mysqli_insert_id($conn);
                    $message = 'Employee added successfully!';
                    $message_type = 'success';
                    if (function_exists('log_activity')) {
                        $details = 'name:' . $full_name . ';phone:' . $phone . ';email:' . $email;
                        log_activity($conn, 'add', 'employee', $employee_id, $details);
                    }
                } else {
                    $message = 'Error adding employee: ' . mysqli_error($conn);
                    $message_type = 'error';
                }
            }
        }
    }

    if (isset($_POST['delete_employee'])) {
        $employee_id = intval($_POST['employee_id']);

        // Delete related shift records first
        mysqli_query($conn, "DELETE FROM shift WHERE employee_id = $employee_id");

        // Delete orders assigned to this employee
        mysqli_query($conn, "DELETE FROM `order` WHERE employee_id = $employee_id");

        // Then delete the employee
        $sql = "DELETE FROM employee WHERE id = $employee_id";

        if (mysqli_query($conn, $sql)) {
            $message = 'Employee deleted successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'delete', 'employee', $employee_id, 'Employee and related records deleted');
            }
        } else {
            $message = 'Error deleting employee: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }

    if (isset($_POST['assign_shift'])) {
        $employee_id = intval($_POST['shift_employee_id']);
        $shift_date = mysqli_real_escape_string($conn, $_POST['shift_date']);

        // Check if shift already exists
        $check_sql = "SELECT id FROM shift WHERE employee_id = $employee_id AND shift_date = '$shift_date'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Shift already assigned for this date!';
            $message_type = 'error';
        } else {
            $sql = "INSERT INTO shift (employee_id, shift_date) 
                    VALUES ($employee_id, '$shift_date')";

            if (mysqli_query($conn, $sql)) {
                $shift_id = mysqli_insert_id($conn);
                $message = 'Shift assigned successfully!';
                $message_type = 'success';
                if (function_exists('log_activity')) {
                    $details = 'employee:' . $employee_id . ';date:' . $shift_date;
                    log_activity($conn, 'add', 'shift', $shift_id, $details);
                }
            } else {
                $message = 'Error assigning shift: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// Fetch all employees
$employees = mysqli_query($conn, "SELECT * FROM employee ORDER BY full_name");
if (!$employees) {
    $message = 'Database error: ' . mysqli_error($conn);
    $message_type = 'error';
    $employees = false;
}

// Fetch today's shifts
$today = date('Y-m-d');
$today_shifts = mysqli_query($conn, "
    SELECT s.*, e.full_name, e.phone
    FROM shift s
    JOIN employee e ON s.employee_id = e.id
    WHERE s.shift_date = '$today'
    ORDER BY s.id
");
if (!$today_shifts) {
    die("Error fetching shifts: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-user-tie"></i> Employee Management</h1>
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('assignShiftModal').style.display='block'"
                            class="btn btn-success">
                        <i class="fas fa-calendar-alt"></i> Assign Shift
                    </button>
                    <button onclick="document.getElementById('addEmployeeModal').style.display='block'"
                            class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Employee
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Today's Shifts -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Today's Shifts (<?php echo date('M d, Y'); ?>)</h2>
                    <span><?php echo mysqli_num_rows($today_shifts); ?> employees on shift</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Contact</th>
                                <th>Shift Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($shift = mysqli_fetch_assoc($today_shifts)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($shift['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($shift['phone']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($shift['shift_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-active">
                                        On Duty
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewEmployee(<?php echo $shift['employee_id']; ?>)" 
                                            class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Profile
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($today_shifts) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No shifts scheduled for today</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Employees List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Employees</h2>
                    <span><?php echo $employees ? mysqli_num_rows($employees) : 0; ?> employees</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($employee = mysqli_fetch_assoc($employees)): 
                                // Check if employee is on shift today
                                $on_shift = false;
                                mysqli_data_seek($today_shifts, 0);
                                while($shift = mysqli_fetch_assoc($today_shifts)) {
                                    if ($shift['employee_id'] == $employee['id']) {
                                        $on_shift = true;
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($employee['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($employee['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                <td><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($on_shift): ?>
                                    <span class="status-badge status-active">On Shift</span>
                                    <?php else: ?>
                                    <span class="status-badge status-inactive">Off Duty</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="viewEmployee(<?php echo $employee['id']; ?>)" 
                                            class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button onclick="editEmployee(<?php echo $employee['id']; ?>)" 
                                            class="btn btn-sm btn-success">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                        <button type="submit" name="delete_employee" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this employee?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(!$employees || mysqli_num_rows($employees) == 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No employees found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <?php
                // Total employees
                $total_employees = $employees ? mysqli_num_rows($employees) : 0;

                // Employees on shift today
                $on_shift_today = mysqli_num_rows($today_shifts);

                // Total shifts this week
                $week_start = date('Y-m-d', strtotime('monday this week'));
                $week_end = date('Y-m-d', strtotime('sunday this week'));
                $week_shifts = mysqli_query($conn, "
                    SELECT COUNT(*) as count
                    FROM shift
                    WHERE shift_date BETWEEN '$week_start' AND '$week_end'
                ");
                if (!$week_shifts) {
                    $week_shifts_count = 0;
                } else {
                    $week_shifts_count = mysqli_fetch_assoc($week_shifts)['count'] ?? 0;
                }
                ?>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_employees; ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $on_shift_today; ?></div>
                    <div class="stat-label">On Shift Today</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-value"><?php echo $week_shifts_count; ?></div>
                    <div class="stat-label">Shifts This Week</div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Employee Modal -->
    <div id="addEmployeeModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Add New Employee</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('addEmployeeModal').style.display='none'">&times;</span>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-control" required
                           placeholder="employee@cafeteria.com">
                    <small style="color: #888; font-size: 12px;">
                        <i class="fas fa-info-circle"></i> Email itatumika kwa password reset
                    </small>
                </div>

                <div class="form-group">
                    <button type="submit" name="add_employee" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Employee
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addEmployeeModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Shift Modal -->
    <div id="assignShiftModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Assign Shift</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('assignShiftModal').style.display='none'">&times;</span>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="shift_employee_id" class="form-control" required>
                        <option value="">Select Employee</option>
                        <?php 
                        mysqli_data_seek($employees, 0);
                        while($employee = mysqli_fetch_assoc($employees)): 
                        ?>
                        <option value="<?php echo $employee['id']; ?>">
                            <?php echo htmlspecialchars($employee['full_name']); ?> 
                            (<?php echo htmlspecialchars($employee['phone']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Shift Date *</label>
                    <input type="date" name="shift_date" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <button type="submit" name="assign_shift" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Assign Shift
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('assignShiftModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function viewEmployee(employeeId) {
        alert('View employee profile for ID: ' + employeeId);
    }

    function editEmployee(employeeId) {
        alert('Edit employee with ID: ' + employeeId);
    }
    </script>
</body>
</html>