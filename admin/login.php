<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$error = '';

// First check for access token in cookies
if (isset($_COOKIE['access_key']) && isset($_COOKIE['user_data'])) {
    try {
        $user_data = json_decode($_COOKIE['user_data'], true);
        $access_key = $_COOKIE['access_key'];
        
        if ($user_data && isset($user_data['id']) && isset($user_data['role']) && $user_data['role'] === 'admin') {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Verify if this is a valid admin user
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$user_data['id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Set admin session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Also set regular user session
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['logged_in'] = true;
                
                header('Location: index.php');
                exit();
            }
        }
    } catch (Exception $e) {
        // Silent fail - will continue to regular login
        error_log("Cookie authentication error: " . $e->getMessage());
    }
}

// Check if user is already logged in as admin through regular login
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // User is already logged in as admin from regular site, set admin session variables
    $_SESSION['admin_id'] = $_SESSION['user_id'];
    $_SESSION['admin_username'] = $_SESSION['username'];
    $_SESSION['admin_role'] = $_SESSION['role'];
    
    // Redirect to admin dashboard
    header('Location: index.php');
    exit();
}

// Check if admin is already logged in through admin login
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
    header('Location: index.php');
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        if (password_verify($password, $admin['password'])) {
            // Generate access token
            $access_key = bin2hex(random_bytes(32));
            $expires = time() + 60 * 60 * 24 * 30; // 30 days
            
            // Set session variables
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['logged_in'] = true;
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Store user data in cookies for persistent auth
            $user_data = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'role' => $admin['role']
            ];
            
            // Set cookies with user data and access key
            setcookie('user_data', json_encode($user_data), $expires, '/');
            setcookie('access_key', $access_key, $expires, '/');
            
            try {
                // Check if access_key column exists and update it
                $check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'access_key'");
                if ($check_col->rowCount() > 0) {
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), access_key = ? WHERE id = ?");
                    $stmt->execute([$access_key, $admin['id']]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                }
            } catch (PDOException $e) {
                // Error updating database - still continue
                error_log("Database error: " . $e->getMessage());
            }

            header('Location: index.php');
            exit();
        } else {
            $error = 'Mật khẩu không đúng!';
        }
    } else {        $error = 'Tài khoản không tồn tại hoặc không có quyền admin!';
    }
    } // Close the username/password validation if-else block
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .login-form {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>    <div class="container">
        <div class="login-form">
            <h2 class="text-center mb-4">Đăng nhập Admin</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION)): ?>
                <!-- Debug information, remove in production -->
                <div class="alert alert-info small">
                    <p><strong>Debug:</strong> Form submission status: <?php echo ($_SERVER['REQUEST_METHOD'] == 'POST' ? 'Submitted' : 'Not submitted'); ?></p>
                    <p>Admin login button present: <?php echo (isset($_POST['admin_login']) ? 'Yes' : 'No'); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="admin_login" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>
</body>

</html>