<?php
// Check employee table
require_once 'config.php';

echo "Checking employee table...\n\n";

// Check if employee table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'employee'");
if (mysqli_num_rows($table_check) == 0) {
    echo "❌ Employee table does not exist! Creating it...\n";

    // Create the employee table
    $create_table = "CREATE TABLE IF NOT EXISTS `employee` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `full_name` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50) NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci";

    if (mysqli_query($conn, $create_table)) {
        echo "✅ Employee table created successfully!\n";
    } else {
        echo "❌ Error creating employee table: " . mysqli_error($conn) . "\n";
        mysqli_close($conn);
        exit;
    }
} else {
    echo "✅ Employee table exists\n";
}

// Check table structure
echo "\nEmployee table structure:\n";
$result = mysqli_query($conn, "DESCRIBE employee");
while ($row = mysqli_fetch_assoc($result)) {
    echo "- {$row['Field']}: {$row['Type']}";
    if ($row['Null'] == 'NO') echo " (NOT NULL)";
    if ($row['Key'] == 'PRI') echo " (PRIMARY KEY)";
    if ($row['Default'] !== null) echo " DEFAULT '{$row['Default']}'";
    echo "\n";
}

// Check if there are any employees
$count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM employee");
$count_row = mysqli_fetch_assoc($count_result);
echo "\nTotal employees in database: {$count_row['count']}\n";

if ($count_row['count'] == 0) {
    echo "\n📝 Table is empty. Adding sample employees...\n";

    $sample_employees = [
        ['full_name' => 'John Smith', 'phone' => '+255 712 345 678'],
        ['full_name' => 'Sarah Johnson', 'phone' => '+255 713 456 789'],
        ['full_name' => 'Michael Brown', 'phone' => '+255 714 567 890'],
        ['full_name' => 'Emily Davis', 'phone' => '+255 715 678 901'],
        ['full_name' => 'David Wilson', 'phone' => '+255 716 789 012']
    ];

    foreach ($sample_employees as $employee) {
        $insert = "INSERT INTO employee (full_name, phone) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, 'ss', $employee['full_name'], $employee['phone']);

        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Added: {$employee['full_name']}\n";
        } else {
            echo "❌ Error adding {$employee['full_name']}: " . mysqli_error($conn) . "\n";
        }
        mysqli_stmt_close($stmt);
    }

    // Check count again
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM employee");
    $count_row = mysqli_fetch_assoc($count_result);
    echo "\n✅ Total employees after insertion: {$count_row['count']}\n";
}

echo "\n🎉 Employee table setup complete!\n";
mysqli_close($conn);
?>