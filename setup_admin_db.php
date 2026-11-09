<?php
include 'config.php';

// Create admin table with direct SQL
$create_table_sql = "CREATE TABLE IF NOT EXISTS `cafeterias`.`admin` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci";

echo "Creating admin table...<br>";
if (mysqli_query($conn, $create_table_sql)) {
    echo "✓ Table created successfully!<br><br>";
} else {
    echo "✗ Error creating table: " . mysqli_error($conn) . "<br>";
    die();
}

// Insert default admin account
$insert_sql = "INSERT INTO `cafeterias`.`admin` (`username`, `password`, `full_name`) 
VALUES ('admin', 'mwasa', 'Administrator')
ON DUPLICATE KEY UPDATE `full_name` = 'Administrator'";

echo "Inserting default admin account...<br>";
if (mysqli_query($conn, $insert_sql)) {
    echo "✓ Default admin account created!<br><br>";
} else {
    echo "✗ Error inserting data: " . mysqli_error($conn) . "<br>";
    die();
}

// Verify the table and data
$verify_sql = "SELECT id, username, full_name FROM admin";
$result = mysqli_query($conn, $verify_sql);

echo "Admin Table Contents:<br>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<br>✓ Admin table setup completed successfully!";

mysqli_close($conn);
?>
