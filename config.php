<?php
// ================================================
// config.php - Database Configuration
// ================================================

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================
// DATABASE CREDENTIALS — BADILISHA HAPA
// ================================================
$host     = 'sql207.infinityfree.com';              // InfinityFree hostname
$username = 'if0_42908119';                         // InfinityFree username
$password = 'Rapa2003200';              // ⚠️ Weka password yako ya DB
$database = 'if0_42908119_cafeterias';              // InfinityFree database name

// ================================================
// CONNECTION
// ================================================
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ================================================
// SITE URL
// ================================================
define('SITE_URL', 'http://cafeterias.infinityfree.me/');

// ================================================
// HELPER: CHECK LOGIN
// ================================================
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// ================================================
// HELPER: LOG ACTIVITY
// ================================================
function log_activity($conn, $action, $entity, $entity_id = null, $details = null) {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : null);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $user_val = $user_id !== null ? intval($user_id) : 'NULL';
    $username_val = $username !== null ? "'" . mysqli_real_escape_string($conn, $username) . "'" : 'NULL';
    $action_val = "'" . mysqli_real_escape_string($conn, $action) . "'";
    $entity_val = "'" . mysqli_real_escape_string($conn, $entity) . "'";
    $entity_id_val = $entity_id !== null ? intval($entity_id) : 'NULL';
    $details_val = $details !== null ? "'" . mysqli_real_escape_string($conn, $details) . "'" : 'NULL';
    $ip_val = $ip !== null ? "'" . mysqli_real_escape_string($conn, $ip) . "'" : 'NULL';

    $sql = "INSERT INTO activity_log (user_id, username, action, entity, entity_id, details, ip_address) 
            VALUES ($user_val, $username_val, $action_val, $entity_val, $entity_id_val, $details_val, $ip_val)";
    @mysqli_query($conn, $sql);
}
?>