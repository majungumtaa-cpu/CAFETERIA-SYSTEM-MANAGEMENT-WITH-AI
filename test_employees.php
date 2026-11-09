<?php
// Test employee availability
require_once 'config.php';

echo "<h1>Employee Availability Test</h1>\n";

$employees = mysqli_query($conn, "SELECT * FROM employee ORDER BY full_name");

if (!$employees) {
    echo "<p style='color: red;'>❌ Employee query failed: " . mysqli_error($conn) . "</p>\n";
} else {
    $count = mysqli_num_rows($employees);
    echo "<p style='color: green;'>✅ Employee query successful. Found $count employees.</p>\n";

    if ($count > 0) {
        echo "<h2>Available Employees:</h2>\n";
        echo "<ul>\n";
        while ($emp = mysqli_fetch_assoc($employees)) {
            echo "<li>{$emp['full_name']} (ID: {$emp['id']})</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No employees found in database.</p>\n";
    }
}

mysqli_close($conn);
?>