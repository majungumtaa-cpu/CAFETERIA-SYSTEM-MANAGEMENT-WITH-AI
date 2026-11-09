<?php
// Fix missing price column in service table
require_once 'config.php';

echo "Checking service table for price column...\n\n";

// Check if service table exists first
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'service'");
if (mysqli_num_rows($table_check) == 0) {
    echo "❌ Service table does not exist! Creating it...\n";

    // Create the service table with all required columns
    $create_table = "CREATE TABLE IF NOT EXISTS `service` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `service_name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `duration_minutes` INT DEFAULT 60,
        `max_capacity` INT DEFAULT 10,
        `is_active` BOOLEAN DEFAULT TRUE,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci";

    if (mysqli_query($conn, $create_table)) {
        echo "✅ Service table created successfully!\n";
    } else {
        echo "❌ Error creating service table: " . mysqli_error($conn) . "\n";
        mysqli_close($conn);
        exit;
    }
} else {
    echo "✅ Service table exists\n";
}

// Now check if price column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM `service` LIKE 'price'");

if (mysqli_num_rows($check_column) == 0) {
    echo "❌ Price column not found. Adding it now...\n";

    // Try to add the column
    $alter_query = "ALTER TABLE `service` ADD COLUMN `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `description`";

    if (mysqli_query($conn, $alter_query)) {
        echo "✅ Price column added successfully!\n";
    } else {
        echo "❌ Error adding price column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✅ Price column already exists\n";
}

// Check if other required columns exist
$required_columns = ['service_name', 'description', 'price', 'duration_minutes', 'max_capacity', 'is_active'];
$existing_columns = [];

$result = mysqli_query($conn, "DESCRIBE service");
while ($row = mysqli_fetch_assoc($result)) {
    $existing_columns[] = $row['Field'];
}

echo "\nChecking for other required columns:\n";
foreach ($required_columns as $col) {
    if (in_array($col, $existing_columns)) {
        echo "✅ $col - exists\n";
    } else {
        echo "❌ $col - missing\n";
    }
}

// Insert some sample data if table is empty
$check_data = mysqli_query($conn, "SELECT COUNT(*) as count FROM service");
$row = mysqli_fetch_assoc($check_data);

if ($row['count'] == 0) {
    echo "\n📝 Table is empty. Adding sample service data...\n";

    $sample_services = [
        ['service_name' => 'Basic Catering', 'description' => 'Basic catering service for small events', 'price' => 25.00, 'duration_minutes' => 120, 'max_capacity' => 50],
        ['service_name' => 'Premium Catering', 'description' => 'Premium catering with gourmet options', 'price' => 45.00, 'duration_minutes' => 180, 'max_capacity' => 100],
        ['service_name' => 'Event Hosting', 'description' => 'Full event hosting and coordination', 'price' => 75.00, 'duration_minutes' => 240, 'max_capacity' => 200],
        ['service_name' => 'Private Dining', 'description' => 'Exclusive private dining experience', 'price' => 35.00, 'duration_minutes' => 90, 'max_capacity' => 20]
    ];

    foreach ($sample_services as $service) {
        $insert = "INSERT INTO service (service_name, description, price, duration_minutes, max_capacity, is_active) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, 'ssiii', $service['service_name'], $service['description'], $service['price'], $service['duration_minutes'], $service['max_capacity']);

        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Added: {$service['service_name']}\n";
        } else {
            echo "❌ Error adding {$service['service_name']}: " . mysqli_error($conn) . "\n";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo "\n✅ Service table already has data ({$row['count']} records)\n";
}

echo "\n🎉 Service table setup complete!\n";
mysqli_close($conn);
?>