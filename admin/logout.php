<?php
session_start();

// Keep track of whether the user is still logged in to the main site
$keep_main_login = isset($_SESSION['user_id']) && (!isset($_SESSION['admin_id']) || $_SESSION['user_id'] !== $_SESSION['admin_id']);
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$logged_in = $_SESSION['logged_in'] ?? false;

// Clear admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

// If the admin session and user session are the same, offer option to logout completely
$complete_logout = isset($_GET['full']) && $_GET['full'] == 1;

if ($complete_logout) {
    // Clear all session variables and destroy session
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to main site login
    header('Location: ../login.php');
} else {
    // Only log out of admin, restore main site session if needed
    if ($keep_main_login) {
        // Restore main site session data
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['logged_in'] = $logged_in;
    }
    
    // Redirect to admin login
    header('Location: login.php');
}
exit();
