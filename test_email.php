<?php
require_once 'send_email.php';

$result = send_otp_email('test@example.com', 'Test User', '123456', 'customer');

if ($result['success']) {
    echo "<h2 style='color:green;'>✓ Email sent successfully!</h2>";
    echo "<p>Check your Mailtrap Inbox: <a href='https://mailtrap.io/inboxes' target='_blank'>https://mailtrap.io/inboxes</a></p>";
} else {
    echo "<h2 style='color:red;'>✗ Failed:</h2>";
    echo "<p>" . htmlspecialchars($result['message']) . "</p>";
}
?>