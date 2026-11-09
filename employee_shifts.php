<?php
// employee_shifts.php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

// Get shifts for this employee (if shift table exists)
// First check if shift table exists
$shift_check = mysqli_query($conn, "SHOW TABLES LIKE 'shift'");
$shift_exists = mysqli_num_rows($shift_check) > 0;

$shifts = [];
if ($shift_exists) {
    // Check what columns exist in shift table
    $columns_check = mysqli_query($conn, "SHOW COLUMNS FROM shift");
    $columns = [];
    while ($col = mysqli_fetch_assoc($columns_check)) {
        $columns[] = $col['Field'];
    }
    
    // Build query to get ONLY this employee's shifts
    $shift_query = "SELECT * FROM shift WHERE employee_id = $employee_id ORDER BY id DESC LIMIT 100";
    $shift_result = mysqli_query($conn, $shift_query);
    
    if ($shift_result) {
        $shifts = [];
        while ($shift = mysqli_fetch_assoc($shift_result)) {
            $shifts[] = $shift;
        }
    }
}

// Get upcoming shifts for this employee ONLY
$upcoming_shifts = [];
if ($shift_exists) {
    $upcoming_query = "SELECT * FROM shift WHERE employee_id = $employee_id ORDER BY id ASC LIMIT 20";
    $upcoming_result = mysqli_query($conn, $upcoming_query);
    
    if ($upcoming_result) {
        $upcoming_shifts = [];
        while ($shift = mysqli_fetch_assoc($upcoming_result)) {
            $upcoming_shifts[] = $shift;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shifts - Employee Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-clock"></i> My Shifts</h1>
                <span>Employee: <?php echo htmlspecialchars($employee_name); ?></span>
            </div>

            <?php if (!$shift_exists): ?>
            <div class="alert" style="background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> 
                <strong>Note:</strong> Shift management system is not yet configured in the database. Contact administrator to enable this feature.
            </div>
            <?php else: ?>

            <!-- Upcoming Shifts -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-check"></i> Upcoming Shifts</h2>
                    <span><?php echo count($upcoming_shifts); ?> shifts scheduled</span>
                </div>
                <?php if (count($upcoming_shifts) > 0): ?>
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                    <?php foreach ($upcoming_shifts as $shift): ?>
                    <div class="stat-card" style="text-align: left; border-left: 4px solid #2ecc71;">
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #333;">
                                <i class="fas fa-calendar"></i> Shift #<?php echo $shift['id']; ?>
                            </strong>
                        </div>
                        <div style="color: #E07B39; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-briefcase"></i> 
                            Shift Information
                        </div>
                        <div style="font-size: 0.85rem; color: #999;">
                            <p style="margin: 5px 0;"><i class="fas fa-user"></i> Employee ID: <?php echo $shift['employee_id']; ?></p>
                            <?php if (isset($shift['shift_date'])): ?>
                            <p style="margin: 5px 0;"><i class="fas fa-calendar"></i> Date: <?php echo date('M d, Y', strtotime($shift['shift_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <i class="fas fa-calendar" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No upcoming shifts scheduled</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Shift History -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Shift History</h2>
                    <span><?php echo count($shifts); ?> shifts</span>
                </div>
                <?php if (count($shifts) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Shift ID</th>
                                <th>Employee ID</th>
                                <th>Details</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shifts as $shift): 
                                $status = '<span style="color: #E07B39;"><i class="fas fa-check-circle"></i> Active</span>';
                            ?>
                            <tr>
                                <td>#<?php echo $shift['id']; ?></td>
                                <td><?php echo $shift['employee_id']; ?></td>
                                <td>
                                    <?php 
                                        $shift_details = [];
                                        if (isset($shift['shift_date'])) $shift_details[] = 'Date: ' . date('M d, Y', strtotime($shift['shift_date']));
                                        if (isset($shift['location'])) $shift_details[] = 'Location: ' . htmlspecialchars($shift['location']);
                                        echo implode(' | ', $shift_details) ?: 'No details available';
                                    ?>
                                </td>
                                <td><?php echo $status; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No shifts history available</p>
                </div>
                <?php endif; ?>
            </div>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>
