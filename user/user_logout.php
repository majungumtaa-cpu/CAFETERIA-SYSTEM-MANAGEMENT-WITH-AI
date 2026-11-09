<?php
// ================================================
// user/user_logout.php - Customer Logout
// ================================================

// ================================================
// ANTI-CACHE HEADERS (kabla ya session_start)
// ================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================
// Futa session variables zote za customer
// ================================================
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_phone']);

// Futa cart pia (kama unataka)
unset($_SESSION['cart']);

// Futa flash messages
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);

// Futa CSRF token
unset($_SESSION['csrf_token']);

// ================================================
// Futa session cookie kwenye browser
// ================================================
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ================================================
// Destroy session
// ================================================
session_destroy();

// ================================================
// Redirect kwa customer login
// ================================================
header('Location: user_login.php');
exit();
?>