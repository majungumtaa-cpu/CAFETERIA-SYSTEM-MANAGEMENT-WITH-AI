<?php
// Populate payment methods
require_once 'config.php';

// Check if payment_method table is empty
$check = mysqli_query($conn, "SELECT COUNT(*) as count FROM payment_method");
$row = mysqli_fetch_assoc($check);
$count = $row['count'];

if ($count == 0) {
    echo "Payment methods table is empty. Adding default methods...\n";
    
    $default_methods = ['Cash', 'Credit Card', 'Debit Card', 'Mobile Money', 'Bank Transfer'];
    
    foreach ($default_methods as $method) {
        $sql = "INSERT INTO payment_method (method_name) VALUES ('$method')";
        if (mysqli_query($conn, $sql)) {
            echo "✓ Added: $method\n";
        } else {
            echo "✗ Error adding $method: " . mysqli_error($conn) . "\n";
        }
    }
} else {
    echo "✓ Payment methods already exist ($count methods found)\n";
}

// Display all payment methods
$methods = mysqli_query($conn, "SELECT * FROM payment_method");
echo "\nCurrent payment methods:\n";
while ($method = mysqli_fetch_assoc($methods)) {
    echo "- " . $method['method_name'] . "\n";
}

mysqli_close($conn);
?>
