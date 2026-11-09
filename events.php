<?php
// events.php
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
    if (isset($_POST['add_event'])) {
        $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
        $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        
        $sql = "INSERT INTO event (event_name, event_date, description) 
                VALUES ('$event_name', '$event_date', '$description')";
        
        if (mysqli_query($conn, $sql)) {
            $event_id = mysqli_insert_id($conn);
            $message = 'Event added successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                $details = 'name:' . $event_name . ';date:' . $event_date;
                log_activity($conn, 'add', 'event', $event_id, $details);
            }
        } else {
            $message = 'Error adding event: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['delete_event'])) {
        $event_id = intval($_POST['event_id']);
        $sql = "DELETE FROM event WHERE id = $event_id";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Event deleted successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'delete', 'event', $event_id, 'Event deleted');
            }
        } else {
            $message = 'Error deleting event: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['register_customer'])) {
        $event_id = intval($_POST['register_event_id']);
        $customer_id = intval($_POST['customer_id']);
        
        // Check if already registered
        $check_sql = "SELECT id FROM event_customer 
                     WHERE event_id = $event_id AND customer_id = $customer_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Customer already registered for this event!';
            $message_type = 'error';
        } else {
            $sql = "INSERT INTO event_customer (event_id, customer_id) 
                    VALUES ($event_id, $customer_id)";
            
            if (mysqli_query($conn, $sql)) {
                $reg_id = mysqli_insert_id($conn);
                $message = 'Customer registered successfully!';
                $message_type = 'success';
                if (function_exists('log_activity')) {
                    $details = 'event:' . $event_id . ';customer:' . $customer_id;
                    log_activity($conn, 'add', 'event_registration', $reg_id, $details);
                }
            } else {
                $message = 'Error registering customer: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// Fetch all events
$events = mysqli_query($conn, "
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_customer ec WHERE ec.event_id = e.id) as attendees_count
    FROM event e
    ORDER BY e.event_date DESC
");

// Fetch customers for registration
$customers = mysqli_query($conn, "SELECT * FROM customer ORDER BY full_name");

// Upcoming events (next 30 days)
$upcoming_events = mysqli_query($conn, "
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_customer ec WHERE ec.event_id = e.id) as attendees_count
    FROM event e
    WHERE e.event_date >= CURDATE() 
    AND e.event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY e.event_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-calendar-alt"></i> Event Management</h1>
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('registerCustomerModal').style.display='block'" 
                            class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Register Customer
                    </button>
                    <button onclick="document.getElementById('addEventModal').style.display='block'" 
                            class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Event
                    </button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Upcoming Events -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-week"></i> Upcoming Events (Next 30 Days)</h2>
                    <span><?php echo mysqli_num_rows($upcoming_events); ?> upcoming events</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Attendees</th>
                                <th>Days Until</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($event = mysqli_fetch_assoc($upcoming_events)): 
                                $days_until = floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24));
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['description']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $event['attendees_count'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $event['attendees_count']; ?> attendees
                                    </span>
                                </td>
                                <td>
                                    <?php if ($days_until == 0): ?>
                                    <span class="status-badge status-active">Today</span>
                                    <?php elseif ($days_until == 1): ?>
                                    <span class="status-badge status-pending">Tomorrow</span>
                                    <?php elseif ($days_until < 0): ?>
                                    <span class="status-badge status-inactive">Past</span>
                                    <?php else: ?>
                                    <span><?php echo $days_until; ?> days</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="viewEvent(<?php echo $event['id']; ?>)" 
                                            class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button onclick="viewAttendees(<?php echo $event['id']; ?>)" 
                                            class="btn btn-sm btn-success">
                                        <i class="fas fa-users"></i> Attendees
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($upcoming_events) == 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No upcoming events</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- All Events -->
            <div class="card">
                <div class="card-header">
                    <h2>All Events</h2>
                    <span><?php echo mysqli_num_rows($events); ?> total events</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Attendees</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($event = mysqli_fetch_assoc($events)): 
                                $today = date('Y-m-d');
                                $event_date = $event['event_date'];
                                $status = $event_date > $today ? 'Upcoming' : ($event_date == $today ? 'Today' : 'Past');
                                $status_class = $event_date > $today ? 'status-pending' : ($event_date == $today ? 'status-active' : 'status-inactive');
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($event['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['description']); ?></td>
                                <td><?php echo $event['attendees_count']; ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                <td>
                                    <button onclick="editEvent(<?php echo $event['id']; ?>)" 
                                            class="btn btn-sm btn-success">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="delete_event" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this event?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($events) == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No events found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <?php
                // Total events
                $total_events = mysqli_num_rows($events);
                
                // Upcoming events count
                $upcoming_count = mysqli_num_rows($upcoming_events);
                
                // Total attendees across all events
                $total_attendees = mysqli_query($conn, "
                    SELECT COUNT(*) as total
                    FROM event_customer
                ");
                $attendees_total = mysqli_fetch_assoc($total_attendees)['total'] ?? 0;
                
                // Most popular event
                $popular_event = mysqli_query($conn, "
                    SELECT e.event_name, COUNT(ec.id) as attendees
                    FROM event e
                    LEFT JOIN event_customer ec ON e.id = ec.event_id
                    GROUP BY e.id
                    ORDER BY attendees DESC
                    LIMIT 1
                ");
                $popular = mysqli_fetch_assoc($popular_event);
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_events; ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $upcoming_count; ?></div>
                    <div class="stat-label">Upcoming Events</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $attendees_total; ?></div>
                    <div class="stat-label">Total Attendees</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #e74c3c;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value"><?php echo $popular['attendees'] ?? 0; ?></div>
                    <div class="stat-label">Max Attendees (<?php echo htmlspecialchars($popular['event_name'] ?? 'N/A'); ?>)</div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Event Modal -->
    <div id="addEventModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 600px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Add New Event</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('addEventModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Event Name *</label>
                    <input type="text" name="event_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_event" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Create Event
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addEventModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Register Customer Modal -->
    <div id="registerCustomerModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Register Customer for Event</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('registerCustomerModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Event *</label>
                    <select name="register_event_id" class="form-control" required>
                        <option value="">Select Event</option>
                        <?php 
                        mysqli_data_seek($upcoming_events, 0);
                        while($event = mysqli_fetch_assoc($upcoming_events)): 
                        ?>
                        <option value="<?php echo $event['id']; ?>">
                            <?php echo htmlspecialchars($event['event_name']); ?> 
                            (<?php echo date('M d, Y', strtotime($event['event_date'])); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Customer *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">Select Customer</option>
                        <?php 
                        mysqli_data_seek($customers, 0);
                        while($customer = mysqli_fetch_assoc($customers)): 
                        ?>
                        <option value="<?php echo $customer['id']; ?>">
                            <?php echo htmlspecialchars($customer['full_name']); ?> 
                            (<?php echo htmlspecialchars($customer['phone']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="register_customer" class="btn btn-primary">
                        <i class="fas fa-user-check"></i> Register Customer
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('registerCustomerModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function viewEvent(eventId) {
        // This would fetch event details via AJAX in a real app
        alert('View event details for ID: ' + eventId);
    }
    
    function viewAttendees(eventId) {
        // Fetch attendees data via fetch API
        fetch('get_event_attendees.php?event_id=' + eventId)
            .then(response => response.text())
            .then(html => {
                const modal = document.getElementById('attendeesModal');
                document.getElementById('attendeesContent').innerHTML = html;
                modal.style.display = 'block';
            })
            .catch(error => {
                alert('Error loading attendees: ' + error);
            });
    }
    
    function removeAttendee(attendeeId, eventId) {
        if (confirm('Are you sure you want to remove this attendee?')) {
            const formData = new FormData();
            formData.append('remove_attendee', '1');
            formData.append('attendee_id', attendeeId);
            
            fetch('events.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Reload attendees
                viewAttendees(eventId);
            })
            .catch(error => {
                alert('Error removing attendee: ' + error);
            });
        }
    }
    
    function editEvent(eventId) {
        // This would load event data into form via AJAX in a real app
        alert('Edit event with ID: ' + eventId);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('attendeesModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
    
    <!-- Attendees Modal -->
    <div id="attendeesModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 5% auto; padding: 20px; width: 90%; max-width: 600px; border-radius: 5px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Event Attendees</h2>
                <span style="cursor: pointer; font-size: 24px;" 
                      onclick="document.getElementById('attendeesModal').style.display='none'">&times;</span>
            </div>
            <div id="attendeesContent">
                Loading...
            </div>
        </div>
    </div>
</body>
</html>