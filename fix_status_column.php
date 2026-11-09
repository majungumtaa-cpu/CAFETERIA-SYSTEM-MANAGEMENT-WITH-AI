<?php
// Fix missing status column
require_once 'config.php';

// Check if status column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM `order` LIKE 'status'");

if (mysqli_num_rows($check_column) == 0) {
    echo "Status column not found. Adding it now...\n";
    
    // Try to add the column
    $alter_query = "ALTER TABLE `order` ADD COLUMN `status` VARCHAR(50) DEFAULT 'pending' AFTER `order_date`";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "✓ Status column added successfully!\n";
    } else {
        echo "✗ Error adding column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✓ Status column already exists.\n";
}

// Verify the column was added
$verify = mysqli_query($conn, "SHOW COLUMNS FROM `order` LIKE 'status'");
if (mysqli_num_rows($verify) > 0) {
    echo "✓ Column verified successfully!\n";
    $col = mysqli_fetch_assoc($verify);
    echo "Column details: " . json_encode($col) . "\n";
} else {
    echo "✗ Column still not found.\n";
}

mysqli_close($conn);
?>
