<?php
session_start();

// Debug information before logout (remove in production)
error_log("Logout attempt: " . json_encode([
    'session' => isset($_SESSION) ? 'exists' : 'not exists',
    'cookies' => $_COOKIE
]));

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Use the centralized function to clear ALL authentication cookies
require_once 'includes/functions.php';
clearAllAuthCookies();

// Destroy the session
session_destroy();

// Clear any potential output buffer before redirect
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to home page
header("Location: index.php");
exit();
?>
