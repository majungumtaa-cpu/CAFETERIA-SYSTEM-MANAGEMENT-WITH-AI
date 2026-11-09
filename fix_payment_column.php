<?php
// fix_payment_column.php
require_once 'config.php';

// Hakikisha connection ipo
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<pre>";
echo "=== Checking payment table structure ===\n\n";

// Check if payment table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'payment'");
if (!$table_check || mysqli_num_rows($table_check) == 0) {
    die("✗ Error: Table 'payment' does not exist in the database!\n");
}
echo "✓ Table 'payment' found.\n\n";

// Check if created_at column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM `payment` LIKE 'created_at'");

if (!$check_column) {
    die("✗ Error checking columns: " . mysqli_error($conn) . "\n");
}

if (mysqli_num_rows($check_column) == 0) {
    echo "✗ created_at column NOT found. Adding it now...\n";
    
    $alter_query = "ALTER TABLE `payment` 
                    ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "✓ created_at column added successfully!\n";
    } else {
        echo "✗ Error adding column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✓ created_at column already exists. No changes needed.\n";
}

echo "\n";

// Verify the column exists
$verify = mysqli_query($conn, "SHOW COLUMNS FROM `payment` LIKE 'created_at'");

if (!$verify) {
    die("✗ Error verifying: " . mysqli_error($conn) . "\n");
}

if (mysqli_num_rows($verify) > 0) {
    $col = mysqli_fetch_assoc($verify);
    echo "✓ Column verified successfully!\n";
    echo "  - Name: " . $col['Field'] . "\n";
    echo "  - Type: " . $col['Type'] . "\n";
    echo "  - Null: " . $col['Null'] . "\n";
    echo "  - Default: " . ($col['Default'] ?? 'NULL') . "\n";
} else {
    echo "✗ Column still not found. Something went wrong.\n";
}

echo "\n=== Done ===\n";
echo "</pre>";

mysqli_close($conn);
?>