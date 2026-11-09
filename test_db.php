<?php
// Test database connection and employee table
require_once 'config.php';

echo "<h1>Database Connection Test</h1>\n";

if ($conn) {
    echo "<p style='color: green;'>✅ Database connection successful!</p>\n";

    // Check if cafeterias database exists
    $db_check = mysqli_query($conn, "SELECT DATABASE() as current_db");
    $db_row = mysqli_fetch_assoc($db_check);
    echo "<p>Current database: <strong>{$db_row['current_db']}</strong></p>\n";

    // List all tables
    echo "<h2>Available Tables:</h2>\n";
    $tables = mysqli_query($conn, "SHOW TABLES");
    echo "<ul>\n";
    while ($table = mysqli_fetch_array($tables)) {
        echo "<li>{$table[0]}</li>\n";
    }
    echo "</ul>\n";

    // Check employee table specifically
    echo "<h2>Employee Table Check:</h2>\n";
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'employee'");
    if (mysqli_num_rows($table_check) > 0) {
        echo "<p style='color: green;'>✅ Employee table exists!</p>\n";

        // Get table structure
        echo "<h3>Table Structure:</h3>\n";
        $structure = mysqli_query($conn, "DESCRIBE employee");
        echo "<table border='1'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
        while ($row = mysqli_fetch_assoc($structure)) {
            echo "<tr>\n";
            echo "<td>{$row['Field']}</td>\n";
            echo "<td>{$row['Type']}</td>\n";
            echo "<td>{$row['Null']}</td>\n";
            echo "<td>{$row['Key']}</td>\n";
            echo "<td>{$row['Default']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";

        // Count employees
        $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM employee");
        $count_row = mysqli_fetch_assoc($count_query);
        echo "<p><strong>Total employees: {$count_row['count']}</strong></p>\n";

        if ($count_row['count'] > 0) {
            // Show first few employees
            echo "<h3>Sample Employees:</h3>\n";
            $sample = mysqli_query($conn, "SELECT * FROM employee LIMIT 5");
            echo "<table border='1'>\n";
            echo "<tr><th>ID</th><th>Name</th><th>Phone</th></tr>\n";
            while ($emp = mysqli_fetch_assoc($sample)) {
                echo "<tr>\n";
                echo "<td>{$emp['id']}</td>\n";
                echo "<td>{$emp['full_name']}</td>\n";
                echo "<td>{$emp['phone']}</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }

    } else {
        echo "<p style='color: red;'>❌ Employee table does NOT exist!</p>\n";
    }

} else {
    echo "<p style='color: red;'>❌ Database connection failed: " . mysqli_connect_error() . "</p>\n";
}

mysqli_close($conn);
?>