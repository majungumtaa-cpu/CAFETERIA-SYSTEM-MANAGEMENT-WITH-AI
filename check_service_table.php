<?php
// Check service table structure
require_once '../config.php';

echo "Checking service table structure...\n\n";

// Check if service table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'service'");
if (mysqli_num_rows($result) == 0) {
    echo "❌ Service table does not exist!\n";
    mysqli_close($conn);
    exit;
}

echo "✅ Service table exists\n\n";

// Get table structure
$result = mysqli_query($conn, "DESCRIBE service");
if (!$result) {
    echo "❌ Error getting table structure: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit;
}

echo "Service table columns:\n";
echo "------------------------\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- {$row['Field']}: {$row['Type']}";
    if ($row['Null'] == 'NO') echo " (NOT NULL)";
    if ($row['Key'] == 'PRI') echo " (PRIMARY KEY)";
    if ($row['Default'] !== null) echo " DEFAULT '{$row['Default']}'";
    echo "\n";
}

echo "\nChecking for price column specifically...\n";
$price_check = mysqli_query($conn, "SHOW COLUMNS FROM service LIKE 'price'");
if (mysqli_num_rows($price_check) > 0) {
    $col = mysqli_fetch_assoc($price_check);
    echo "✅ Price column exists: {$col['Type']}\n";
} else {
    echo "❌ Price column does NOT exist!\n";
}

mysqli_close($conn);
?>