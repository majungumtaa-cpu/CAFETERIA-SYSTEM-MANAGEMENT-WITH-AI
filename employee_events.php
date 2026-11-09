<?php
// employee_events.php - READ-ONLY access
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];

// Get all events - READ-ONLY for employees
$events = mysqli_query($conn, "
    SELECT * FROM event
    ORDER BY event_date DESC
    LIMIT 500
");

// Get events with attendee count (READ-ONLY)
$employee_events = mysqli_query($conn, "
    SELECT e.*, COUNT(ec.customer_id) as attendee_count FROM event e
    LEFT JOIN event_customer ec ON e.id = ec.event_id
    GROUP BY e.id
    ORDER BY e.event_date DESC
    LIMIT 500
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Employee Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-calendar-alt"></i> Events</h1>
                <span>Browse upcoming events</span>
            </div>

            <!-- Your Events -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-star"></i> Your Events</h2>
                    <span><?php echo mysqli_num_rows($employee_events); ?> events</span>
                </div>
                <?php if (mysqli_num_rows($employee_events) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php while($event = mysqli_fetch_assoc($employee_events)): 
                        $event_date = strtotime($event['event_date']);
                        $is_upcoming = $event_date >= strtotime('today');
                    ?>
                    <div style="background: white; border: 2px solid #E07B39; border-radius: 8px; padding: 20px;">
                        <h3 style="color: #E07B39; margin-top: 0;">
                            <?php echo htmlspecialchars($event['event_name']); ?>
                        </h3>
                        <div style="margin: 15px 0; color: #666;">
                            <p style="margin: 5px 0;">
                                <i class="fas fa-calendar"></i> 
                                <strong><?php echo date('M d, Y', $event_date); ?></strong>
                            </p>
                            <p style="margin: 5px 0;">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?>
                            </p>
                            <p style="margin: 5px 0;">
                                <i class="fas fa-users"></i> 
                                Attendees: <?php echo $event['expected_attendees'] ?? 0; ?>
                            </p>
                        </div>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <span style="background: <?php echo $is_upcoming ? '#2ecc71' : '#999'; ?>; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem;">
                                <?php echo $is_upcoming ? '<i class="fas fa-check-circle"></i> Upcoming' : '<i class="fas fa-check"></i> Past'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <i class="fas fa-calendar" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No events assigned to you</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- All Events -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> All Events</h2>
                    <span><?php echo mysqli_num_rows($events); ?> total events</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Attendees</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($event = mysqli_fetch_assoc($events)): 
                                $event_date = strtotime($event['event_date']);
                                $is_upcoming = $event_date >= strtotime('today');
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($event['event_name']); ?></strong></td>
                                <td><?php echo date('M d, Y', $event_date); ?></td>
                                <td><?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?></td>
                                <td><?php echo $event['expected_attendees'] ?? 0; ?></td>
                                <td>
                                    <span style="color: <?php echo $is_upcoming ? '#2ecc71' : '#999'; ?>; font-weight: 600;">
                                        <?php echo $is_upcoming ? '<i class="fas fa-check-circle"></i> Upcoming' : '<i class="fas fa-check"></i> Past'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
