<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Initialize the $login and $error variables here to ensure they're in scope
$login = [
    'success' => false,
    'admin_redirect' => false,
    'redirect_url' => '',
    'username' => isset($_POST['username']) ? $_POST['username'] : '',
    'errors' => []
];

$error = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include the login handler
require_once 'includes/login.php';

// Redirect after successful login
if ($login['success']) {
    // If user is admin, redirect to admin panel
    if (isset($login['admin_redirect']) && $login['admin_redirect']) {
        header('Location: ' . $login['redirect_url']);
    } else {
        // Otherwise, redirect to home page
        header('Location: index.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Website Truyện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Custom styling for login form */
        .auth-form {
            max-width: 450px;
            margin: 60px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background-color: #f8f9fa;
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .auth-footer {
            padding: 15px;
            text-align: center;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .divider {
            position: relative;
            text-align: center;
            margin: 20px 0;
        }
        
        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #e5e5e5;
            z-index: 1;
        }
        
        .divider span {
            position: relative;
            background-color: #fff;
            padding: 0 15px;
            z-index: 2;
            color: #6c757d;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .social-btn.facebook {
            background-color: #3b5998;
            color: white;
        }
        
        .social-btn.google {
            background-color: #db4437;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <div class="auth-form">
            <div class="auth-header">
                <h2><i class="fas fa-sign-in-alt me-2"></i>Đăng nhập</h2>
                <p class="mb-0">Chào mừng bạn quay trở lại!</p>
            </div>
              <div class="auth-body">                <?php 
                // Safe way to call the function with error handling
                if (function_exists('display_login_errors')) {
                    display_login_errors();
                } else {
                    // Fallback if function doesn't exist
                    if (!empty($error)) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
                    } else if (isset($login['errors']) && !empty($login['errors'])) {
                        echo '<div class="alert alert-danger">';
                        foreach ($login['errors'] as $err) {
                            echo htmlspecialchars($err) . '<br>';
                        }
                        echo '</div>';
                    }
                }
                ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập hoặc Email</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($login['username']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Ghi nhớ đăng nhập
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary w-100 py-2 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                    </button>
                    
                    <div class="divider">
                        <span>hoặc đăng nhập với</span>
                    </div>
                    
                    <div class="social-login my-3">
                        <a href="#" class="social-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-btn google">
                            <i class="fab fa-google"></i>
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="auth-footer">
                <p class="mb-0">Chưa có tài khoản? <a href="register.php" class="text-decoration-none">Đăng ký ngay</a></p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
