<?php
/**
 * User Registration Handler
 * 
 * This file handles the user registration process including:
 * - Data validation
 * - Checking for existing users
 * - Password hashing
 * - Database insertion
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure we have all required files
require_once 'database.php';
require_once 'functions.php';

// Initialize results array
$registration = [
    'success' => false,
    'errors' => [],
    'username' => '',
    'email' => ''
];

// Process registration if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Get and sanitize form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Store the submitted values for form repopulation
    $registration['username'] = $username;
    $registration['email'] = $email;
    
    // Validate inputs
    if (empty($username)) {
        $registration['errors'][] = 'Tên đăng nhập không được để trống';
    } elseif (strlen($username) < 4 || strlen($username) > 20) {
        $registration['errors'][] = 'Tên đăng nhập phải từ 4-20 ký tự';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $registration['errors'][] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
    }
    
    if (empty($email)) {
        $registration['errors'][] = 'Email không được để trống';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration['errors'][] = 'Email không hợp lệ';
    }
    
    if (empty($password)) {
        $registration['errors'][] = 'Mật khẩu không được để trống';
    } elseif (strlen($password) < 6) {
        $registration['errors'][] = 'Mật khẩu phải có ít nhất 6 ký tự';
    }
    
    if ($password !== $confirm_password) {
        $registration['errors'][] = 'Xác nhận mật khẩu không khớp';
    }
    
    // If no validation errors, check database for existing users
    if (empty($registration['errors'])) {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $registration['errors'][] = 'Tên đăng nhập đã tồn tại';
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $registration['errors'][] = 'Email đã được sử dụng';
        }
        
        // If all checks pass, register the user
        if (empty($registration['errors'])) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role, created_at) 
                VALUES (?, ?, ?, 'user', NOW())
            ");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $registration['success'] = true;
                $registration['user_id'] = $conn->lastInsertId();
                  // Optional: Auto-login the user
                if (!isset($_SESSION)) {
                    session_start();
                }                // Clear all authentication cookies
                clearAllAuthCookies();
                
                // Set fresh session data for the new user
                $_SESSION['user_id'] = $registration['user_id'];
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'user';
                $_SESSION['logged_in'] = true;
                
                // Create user data array for token generation
                $user = [
                    'id' => $registration['user_id'],
                    'username' => $username,
                    'email' => $email,
                    'role' => 'user'
                ];
                
                // Set authentication token cookies
                setAuthTokenCookies($user);
            } else {
                $registration['errors'][] = 'Đã xảy ra lỗi, vui lòng thử lại sau';
            }
        }
    }
}

/**
 * Displays error messages if any exist in the registration process
 */
function display_registration_errors() {
    global $registration;
    if (!empty($registration['errors'])) {
        echo '<div class="alert alert-danger">';
        echo '<ul class="mb-0">';
        foreach ($registration['errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

/**
 * Displays success message if registration was successful
 */
function display_registration_success() {
    global $registration;
    if ($registration['success']) {
        echo '<div class="alert alert-success">';
        echo 'Đăng ký tài khoản thành công!';
        echo '</div>';
    }
}
?>
