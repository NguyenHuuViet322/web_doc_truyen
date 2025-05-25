<?php
/**
 * User Login Handler
 * 
 * This file handles the user login process including:
 * - Credential validation
 * - Session management
 * - Login error reporting
 */

// Initialize login results array
$login = [
    'success' => false,
    'errors' => [],
    'username' => ''
];

// Process login if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Get and sanitize form data
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Store the submitted username for form repopulation
    $login['username'] = $username;
    
    // Basic validation
    if (empty($username)) {
        $login['errors'][] = 'Vui lòng nhập tên đăng nhập';
    }
    
    if (empty($password)) {
        $login['errors'][] = 'Vui lòng nhập mật khẩu';
    }
    
    // If no validation errors, check credentials
    if (empty($login['errors'])) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $login['success'] = true;
            
            // Start session if not already started
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            
            // Generate a secure access key token
            $access_key = bin2hex(random_bytes(32));
            $expires = time() + 60 * 60 * 24 * 30; // 30 days
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['access_key'] = $access_key;
            
            try {
                // Update last login time and save access key
                // Check if access_key column exists
                $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'access_key'");
                $column_exists = $stmt->rowCount() > 0;
                
                if ($column_exists) {
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), access_key = ? WHERE id = ?");
                    $stmt->execute([$access_key, $user['id']]);
                } else {
                    // Just update last login time if access_key column doesn't exist
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Display warning for site administrator
                    error_log("Warning: access_key column not found in users table. Please run update_schema.php");
                }
                
                // Store minimal user data in cookies
                $user_data = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar'] ?? ''
                ];
                
                // Set cookies with user data and access key
                setcookie('user_data', json_encode($user_data), $expires, '/', '', false, true); // httponly for security
                setcookie('access_key', $access_key, $expires, '/', '', false, true);
                
            } catch (PDOException $e) {
                // Log the error but don't prevent login
                error_log("Database error during login: " . $e->getMessage());
            }
            
            // Set remember-me cookie if requested
            if ($remember) {
                setcookie('remember_token', $access_key, $expires, '/');
                setcookie('remember_user', $user['username'], $expires, '/');
            }
            
            // Check if user is admin and redirect accordingly
            if ($user['role'] === 'admin') {
                // Set redirect URL to admin panel
                $login['admin_redirect'] = true;
                $login['redirect_url'] = 'admin/index.php';
            }
        } else {
            $login['errors'][] = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}

/**
 * Displays error messages if any exist in the login process
 */
function display_login_errors() {
    global $login;
    if (!empty($login['errors'])) {
        echo '<div class="alert alert-danger">';
        echo '<ul class="mb-0">';
        foreach ($login['errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

/**
 * Checks if a remember-me token exists and is valid
 */
function check_remember_login() {
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND remember_token = ?");
        $stmt->execute([$_COOKIE['remember_user'], $_COOKIE['remember_token']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            return true;
        }
    }
    
    return false;
}
?>
