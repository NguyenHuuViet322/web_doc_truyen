<?php
session_start();

// Check if user is admin (for logging purposes)
$was_admin = isset($_SESSION['admin_role']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Invalidate the access key in the database
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    try {
        // Include config.php BEFORE database.php to define the DB constants
        require_once '../includes/config.php';
        require_once '../includes/database.php';
        
        $db = new Database();
        $conn = $db->getConnection();
        
        $user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : $_SESSION['user_id'];
        
        // Check if access_key column exists
        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'access_key'");
        $column_exists = $stmt->rowCount() > 0;
        
        if ($column_exists) {
            $stmt = $conn->prepare("UPDATE users SET access_key = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    } catch (PDOException $e) {
        // Log error but continue with logout process
        error_log("Error during admin logout: " . $e->getMessage());
    }
}

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

// Clear all auth cookies
setcookie('access_key', '', time() - 3600, '/');
setcookie('user_data', '', time() - 3600, '/');
setcookie('remember_token', '', time() - 3600, '/');
setcookie('remember_user', '', time() - 3600, '/');
setcookie('admin_token', '', time() - 3600, '/');
setcookie('admin_session', '', time() - 3600, '/');

// Destroy the session
session_destroy();

// Log the admin logout (optional)
if ($was_admin) {
    error_log("Admin user logged out successfully");
}

// Redirect to the main site home page
header("Location: ../index.php");
exit();
?>
