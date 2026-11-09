<?php
// get_event_attendees.php - Fetch attendees for an event
require_once 'config.php';
check_login();

$event_id = intval($_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    echo '<p style="color: red;">Invalid event ID</p>';
    exit;
}

// Get event details
$event = mysqli_query($conn, "SELECT * FROM event WHERE id = $event_id");
$event_data = mysqli_fetch_assoc($event);

if (!$event_data) {
    echo '<p style="color: red;">Event not found</p>';
    exit;
}

// Get attendees
$attendees = mysqli_query($conn, "
    SELECT ec.id, c.id as customer_id, c.full_name, c.phone
    FROM event_customer ec
    JOIN customer c ON ec.customer_id = c.id
    WHERE ec.event_id = $event_id
    ORDER BY c.full_name
");

// Get customers not yet registered for this event
$non_attendees = mysqli_query($conn, "
    SELECT c.id, c.full_name
    FROM customer c
    WHERE c.id NOT IN (
        SELECT customer_id FROM event_customer WHERE event_id = $event_id
    )
    ORDER BY c.full_name
");

?>

<h3><?php echo htmlspecialchars($event_data['event_name']); ?> - <?php echo date('M d, Y', strtotime($event_data['event_date'])); ?></h3>

<?php if (mysqli_num_rows($attendees) > 0): ?>

<div style="overflow-x: auto;">
    <table class="data-table" style="width: 100%; margin-top: 10px;">
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($attendee = mysqli_fetch_assoc($attendees)): ?>
            <tr>
                <td><?php echo htmlspecialchars($attendee['full_name']); ?></td>
                <td><?php echo htmlspecialchars($attendee['phone'] ?? 'N/A'); ?></td>
                <td>
                    <button onclick="removeAttendee(<?php echo $attendee['id']; ?>, <?php echo $event_id; ?>)" 
                            class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<p style="padding: 20px; text-align: center; color: #666;">No attendees registered for this event yet.</p>

<?php endif; ?>

<!-- Add Attendee Section -->
<?php if (mysqli_num_rows($non_attendees) > 0): ?>
<div style="margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px;">
    <h4>Add Attendee</h4>
    <form method="POST" action="events.php" style="display: flex; gap: 10px; align-items: flex-end;">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <div style="flex: 1;">
            <label>Customer:</label>
            <select name="customer_id" class="form-control" required>
                <option value="">Select Customer</option>
                <?php 
                mysqli_data_seek($non_attendees, 0);
                while ($customer = mysqli_fetch_assoc($non_attendees)): 
                ?>
                <option value="<?php echo $customer['id']; ?>">
                    <?php echo htmlspecialchars($customer['full_name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" name="add_customer_to_event" class="btn btn-primary" style="margin-bottom: 0;">
            <i class="fas fa-plus"></i> Add
        </button>
    </form>
</div>
<?php endif; ?>

<div style="margin-top: 20px; display: flex; gap: 10px;">
    <button onclick="document.getElementById('attendeesModal').style.display='none'" class="btn btn-secondary">
        Close
    </button>
</div>
