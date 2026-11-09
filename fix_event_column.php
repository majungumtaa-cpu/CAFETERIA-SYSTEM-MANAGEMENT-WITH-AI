<?php
// Fix missing description column in event table
require_once 'config.php';

// Check if description column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM `event` LIKE 'description'");

if (mysqli_num_rows($check_column) == 0) {
    echo "description column not found. Adding it now...\n";
    
    // Try to add the column
    $alter_query = "ALTER TABLE `event` ADD COLUMN `description` TEXT AFTER `event_date`";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "✓ description column added successfully!\n";
    } else {
        echo "✗ Error adding column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✓ description column already exists.\n";
}

// Verify the column was added
$verify = mysqli_query($conn, "SHOW COLUMNS FROM `event` LIKE 'description'");
if (mysqli_num_rows($verify) > 0) {
    echo "✓ Column verified successfully!\n";
} else {
    echo "✗ Column still not found.\n";
}

mysqli_close($conn);
?>
