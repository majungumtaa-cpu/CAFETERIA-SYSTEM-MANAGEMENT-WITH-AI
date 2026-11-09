<?php
// user/events.php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: user_login.php');
    exit();
}

require_once '../config.php';

$customer_id = $_SESSION['customer_id'];

$error = '';
$success = '';

// Handle event registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $event_id = (int)$_POST['event_id'];

    // Check if event exists
    $event_check = mysqli_query($conn, "SELECT id, event_name FROM event WHERE id = $event_id");
    if (mysqli_num_rows($event_check) == 0) {
        $error = 'Event not found.';
    } else {
        // Check if already registered
        $existing_registration = mysqli_query($conn, "
            SELECT id FROM event_customer
            WHERE event_id = $event_id AND customer_id = $customer_id
        ");

        if (mysqli_num_rows($existing_registration) > 0) {
            $error = 'You are already registered for this event.';
        } else {
            // Register for event
            $stmt = mysqli_prepare($conn, "INSERT INTO event_customer (event_id, customer_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $event_id, $customer_id);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Successfully registered for the event!';
            } else {
                $error = 'Failed to register for event. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get upcoming events
$upcoming_events = mysqli_query($conn, "
    SELECT e.*,
           (SELECT COUNT(*) FROM event_customer ec WHERE ec.event_id = e.id) as registered_count
    FROM event e
    WHERE e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
");

// Get customer's registered events
$my_events = mysqli_query($conn, "
    SELECT e.event_name, e.event_date, e.description,
           ec.id as registration_id
    FROM event_customer ec
    JOIN event e ON ec.event_id = e.id
    WHERE ec.customer_id = $customer_id
    ORDER BY e.event_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Cafeteria</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
        }

        .events-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .events-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .events-header h1 {
            margin: 0;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .event-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .event-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .event-header h3 {
            margin: 0 0 10px 0;
            font-size: 1.3em;
        }

        .event-date {
            font-size: 1.1em;
            font-weight: bold;
        }

        .event-body {
            padding: 20px;
        }

        .event-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .event-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .event-info span {
            color: #666;
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }

        .register-btn:hover {
            transform: translateY(-2px);
        }

        .register-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .registered-badge {
            background: #d4edda;
            color: #155724;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .my-events {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .my-events h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #E07B39;
            padding-bottom: 10px;
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
        }

        .events-table th,
        .events-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .events-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .no-events {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="events-container">
        <a href="user_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="events-header">
            <h1><i class="fas fa-calendar-star"></i> Events & Activities</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="events-grid">
            <?php if (mysqli_num_rows($upcoming_events) > 0): ?>
                <?php while ($event = mysqli_fetch_assoc($upcoming_events)):
                    // Check if user is already registered
                    $is_registered = false;
                    $registration_check = mysqli_query($conn, "
                        SELECT id FROM event_customer
                        WHERE event_id = {$event['id']} AND customer_id = $customer_id
                    ");
                    if (mysqli_num_rows($registration_check) > 0) {
                        $is_registered = true;
                    }
                ?>
                    <div class="event-card">
                        <div class="event-header">
                            <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <div class="event-date">
                                <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                            </div>
                        </div>
                        <div class="event-body">
                            <?php if ($event['description']): ?>
                                <div class="event-description">
                                    <?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>
                                    <?php if (strlen($event['description']) > 150): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="event-info">
                                <span><i class="fas fa-users"></i> <?php echo $event['registered_count']; ?> registered</span>
                                <span><?php echo date('l', strtotime($event['event_date'])); ?></span>
                            </div>

                            <?php if ($is_registered): ?>
                                <div class="registered-badge">
                                    <i class="fas fa-check"></i> You're Registered!
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="register_event" class="register-btn">
                                        <i class="fas fa-plus"></i> Register for Event
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                    <i class="fas fa-calendar-times" style="font-size: 3em; color: #ccc;"></i>
                    <h3 style="color: #666;">No upcoming events</h3>
                    <p>Check back later for new events and activities!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="my-events">
            <h3><i class="fas fa-list"></i> My Registered Events</h3>

            <?php if (mysqli_num_rows($my_events) > 0): ?>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = mysqli_fetch_assoc($my_events)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($event['event_name']); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                <td>
                                    <?php if ($event['description']): ?>
                                        <?php echo htmlspecialchars(substr($event['description'], 0, 80)); ?>
                                        <?php if (strlen($event['description']) > 80): ?>...<?php endif; ?>
                                    <?php else: ?>
                                        <em>No description</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-events">
                    <i class="fas fa-calendar-plus" style="font-size: 2em; color: #ccc;"></i>
                    <h4>You haven't registered for any events yet</h4>
                    <p>Browse the upcoming events above and register for the ones that interest you!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>