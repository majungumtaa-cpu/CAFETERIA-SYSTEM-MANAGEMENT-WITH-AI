<?php
include 'config.php';

// Check if admin table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ Admin table exists!<br>";
    
    // Get admin records
    $admin_result = mysqli_query($conn, "SELECT id, username, full_name FROM admin");
    if ($admin_result) {
        echo "✓ Admin records found: " . mysqli_num_rows($admin_result) . "<br>";
        while ($row = mysqli_fetch_assoc($admin_result)) {
            echo "  - ID: " . $row['id'] . ", Username: " . $row['username'] . ", Name: " . $row['full_name'] . "<br>";
        }
    } else {
        echo "✗ Error querying admin table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✗ Admin table does not exist!<br>";
}

mysqli_close($conn);
?>
