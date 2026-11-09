<?php
// user/reservations.php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: user_login.php');
    exit();
}

require_once '../config.php';

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$error = '';
$success = '';

// Get available services
$services = mysqli_query($conn, "
    SELECT * FROM service
    WHERE is_active = 1
    ORDER BY service_name
");

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_reservation'])) {
    $service_id = (int)$_POST['service_id'];
    $reservation_date = trim($_POST['reservation_date']);

    if (empty($reservation_date)) {
        $error = 'Please select a reservation date.';
    } elseif (strtotime($reservation_date) < strtotime('today')) {
        $error = 'Reservation date cannot be in the past.';
    } else {
        // Check if service exists and is active
        $service_check = mysqli_query($conn, "SELECT id, service_name FROM service WHERE id = $service_id AND is_active = 1");
        if (mysqli_num_rows($service_check) == 0) {
            $error = 'Invalid service selected.';
        } else {
            // Check if customer already has a reservation for this date and service
            $existing_reservation = mysqli_query($conn, "
                SELECT id FROM reservation
                WHERE customer_id = $customer_id AND service_id = $service_id AND reservation_date = '$reservation_date'
            ");

            if (mysqli_num_rows($existing_reservation) > 0) {
                $error = 'You already have a reservation for this service on this date.';
            } else {
                // Insert reservation
                $stmt = mysqli_prepare($conn, "INSERT INTO reservation (customer_id, service_id, reservation_date) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iis", $customer_id, $service_id, $reservation_date);

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Reservation made successfully!';
                } else {
                    $error = 'Failed to make reservation. Please try again.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get customer's existing reservations
$my_reservations = mysqli_query($conn, "
    SELECT r.id, r.reservation_date, s.service_name
    FROM reservation r
    JOIN service s ON r.service_id = s.id
    WHERE r.customer_id = $customer_id
    ORDER BY r.reservation_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Cafeteria</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('../customer.jpg') center/cover no-repeat;
            background-attachment: fixed;
        }

        .reservations-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .reservations-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .reservations-header h1 {
            margin: 0;
        }

        .reservation-form, .my-reservations {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .reservation-form h3, .my-reservations h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #E07B39;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .service-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }

        .service-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .service-card p {
            margin: 5px 0;
            color: #666;
        }

        .service-price {
            font-weight: bold;
            color: #E07B39;
        }

        .reserve-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: transform 0.2s;
        }

        .reserve-btn:hover {
            transform: translateY(-2px);
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

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservations-table th,
        .reservations-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .reservations-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .reservation-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-upcoming { background: #d4edda; color: #155724; }
        .status-past { background: #f8d7da; color: #721c24; }

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

        .no-reservations {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="reservations-container">
        <a href="user_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="reservations-header">
            <h1><i class="fas fa-calendar-check"></i> Make a Reservation</h1>
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

        <div class="reservation-form">
            <h3><i class="fas fa-plus"></i> Book a Service</h3>

            <form method="POST">
                <div class="form-group">
                    <label for="service_id">Select Service:</label>
                    <select name="service_id" id="service_id" required>
                        <option value="">Choose a service...</option>
                        <?php
                        mysqli_data_seek($services, 0);
                        while ($service = mysqli_fetch_assoc($services)):
                        ?>
                            <option value="<?php echo $service['id']; ?>">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reservation_date">Reservation Date:</label>
                    <input type="date" name="reservation_date" id="reservation_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <button type="submit" name="make_reservation" class="reserve-btn">
                    <i class="fas fa-calendar-plus"></i> Make Reservation
                </button>
            </form>
        </div>

        <div class="my-reservations">
            <h3><i class="fas fa-list"></i> My Reservations</h3>

            <?php if (mysqli_num_rows($my_reservations) > 0): ?>
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reservation = mysqli_fetch_assoc($my_reservations)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['service_name']); ?></strong>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($reservation['reservation_date'])); ?></td>
                                <td>
                                    <span class="reservation-status <?php echo strtotime($reservation['reservation_date']) >= strtotime('today') ? 'status-upcoming' : 'status-past'; ?>">
                                        <?php echo strtotime($reservation['reservation_date']) >= strtotime('today') ? 'Upcoming' : 'Past'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-reservations">
                    <i class="fas fa-calendar-times" style="font-size: 3em; color: #ccc;"></i>
                    <h4>No reservations found</h4>
                    <p>You haven't made any reservations yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>