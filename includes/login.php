<?php
// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix path to config.php - it should be in the same directory or parent directory
require_once('config.php'); 

// Fix database.php path - it should be in the same directory
require_once('database.php'); 
require_once 'functions.php'; 

// Use the existing $login and $error variables from the including file
// If they're not defined yet, initialize them (defensive programming)
if (!isset($login) || !is_array($login)) {
    $login = [
        'success' => false,
        'admin_redirect' => false,
        'redirect_url' => '',
        'username' => isset($_POST['username']) ? $_POST['username'] : '',
        'errors' => []
    ];
}

if (!isset($error)) {
    $error = '';
}

// Nếu đã đăng nhập thì chuyển đến trang dashboard
if (isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Update username in login array for form repopulation
    $login['username'] = $username;
    
    // Basic validation
    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập hoặc email';
        $login['errors'][] = $error;
    } 
    
    if (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu';
        $login['errors'][] = $error;
    }
      // Only proceed if we have both username and password
    if (!empty($username) && !empty($password)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Debug database connection
        if (!$conn) {
            error_log("Database connection failed in login.php");
        } else {
            error_log("Database connection successful in login.php");
        }
        
        // First check if the user exists (regardless of role)
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug user data
        error_log("Login attempt: Username: " . $username);
        error_log("User data found: " . ($user ? "Yes" : "No"));
        if ($user) {
            error_log("User columns: " . implode(", ", array_keys($user)));
        }

        if ($user) {        
            if (password_verify($password, $user['password'])) {            // Dump the entire user object for debugging
            error_log("User data retrieved in login: " . print_r($user, true));
              // Set session variables with safe access methods
            // Essential fields with safe fallbacks
            $_SESSION['user_id'] = $user['id'] ?? 0; 
            $_SESSION['logged_in'] = true;
            
            // Check each field individually
            if (isset($user['username'])) {
                $_SESSION['username'] = $user['username'];
            } else {
                $_SESSION['username'] = 'User';
                error_log("username field missing in user record");
            }
            
            if (isset($user['role'])) {
                $_SESSION['role'] = $user['role'];
            } else {
                $_SESSION['role'] = 'user';
                error_log("role field missing in user record");
            }
            
            if (isset($user['email'])) {
                $_SESSION['email'] = $user['email'];
            } else {
                $_SESSION['email'] = '';
                error_log("email field missing in user record");
            }
              // Optional fields with explicit checks and debug logging
            if (isset($user['created_at'])) {
                $_SESSION['created_at'] = $user['created_at'];
            } else {
                $_SESSION['created_at'] = date('Y-m-d H:i:s');
                error_log("LOGIN: created_at not found in user data, using current date");
            }
            
            if (isset($user['status'])) {
                $_SESSION['status'] = $user['status'];
                error_log("LOGIN: Setting status from database: " . $user['status']);
            } else {
                $_SESSION['status'] = 'active';
                error_log("LOGIN: status not found in user data, using 'active'");
            }
            
            if (isset($user['last_login'])) {
                $_SESSION['last_login'] = $user['last_login'];
            } else {
                $_SESSION['last_login'] = date('Y-m-d H:i:s');
                error_log("LOGIN: last_login not found in user data, using current date");
            }
              // Set avatar from database or default - with explicit debug
            if (isset($user['avatar']) && !empty($user['avatar'])) {
                $_SESSION['avatar'] = $user['avatar'];
                error_log("LOGIN: Setting avatar from database: " . $user['avatar']);
            } else {
                $_SESSION['avatar'] = 'default.jpg';
                error_log("LOGIN: Avatar not found in user data, using default.jpg");
            }
            
            // Store existing access_key if present in database
            if (isset($user['access_key']) && !empty($user['access_key'])) {
                $_SESSION['access_key'] = $user['access_key'];
            }

            // Update last login time
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Update $login variable
            $login['success'] = true;

            // Check if admin for redirect
            if ($user['role'] === 'admin') {
                // Admin-specific session variables
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];
                
                $login['admin_redirect'] = true;
                $login['redirect_url'] = 'admin/dashboard.php';
            } else {
                // Regular user redirect
                $login['admin_redirect'] = false;
                $login['redirect_url'] = 'index.php';
            }
              // Remember me functionality
            if (isset($_POST['remember']) && $_POST['remember'] == 1) {
                // Use new token-based authentication (no database storage needed)
                setAuthTokenCookies($user);
                
                // Store token info in session for reference
                $_SESSION['remember_me'] = true;
            }
        } else {
            $error = 'Mật khẩu không đúng!';
            $login['errors'][] = $error;
        }    } else {
        $error = 'Tài khoản không tồn tại!';
        $login['errors'][] = $error;
    }
    } // Close the if block for username/password validation
}

// Define display_login_errors function - always define it
function display_login_errors() {
    global $login, $error;
    
    if (!empty($error)) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    } else if (!empty($login['errors'])) {
        echo '<div class="alert alert-danger">';
        foreach ($login['errors'] as $err) {
            echo $err . '<br>';
        }
        echo '</div>';
    }
}
?>
