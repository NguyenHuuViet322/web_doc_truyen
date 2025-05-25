<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include the registration handler
require_once 'includes/register.php';

// Redirect to index.php after successful registration
if ($registration['success']) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Website Truyện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Custom styling for registration form */
        .auth-form {
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        
        .auth-body {
            overflow-y: auto;
            padding: 25px;
            max-height: 60vh;
            scrollbar-width: thin;
        }
        
        /* Custom scrollbar for webkit browsers */
        .auth-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .auth-body::-webkit-scrollbar-track {
            background: #f5f5f5;
            border-radius: 10px;
        }
        
        .auth-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .auth-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Make the form more mobile-friendly */
        @media (max-width: 576px) {
            .auth-form {
                max-height: none;
                margin: 10px;
            }
            
            .auth-body {
                max-height: none;
                overflow-y: visible;
            }
        }
    </style>
</head>
<body>
    <!-- <?php include 'includes/navbar.php'; ?> -->
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="auth-form shadow rounded">
                    <div class="auth-header py-4">
                        <h2 class="text-center"><i class="fas fa-user-plus me-2"></i>Đăng ký tài khoản</h2>
                        <p class="text-center mb-0">Tạo tài khoản để đọc và theo dõi truyện yêu thích</p>
                    </div>
                    
                    <?php if ($registration['success']): ?>
                        <div class="p-4">
                            <?php display_registration_success(); ?>
                        </div>
                    <?php else: ?>
                    <div class="auth-body">
                        <?php display_registration_errors(); ?>
                        
                        <form method="POST" action="register.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($registration['username']); ?>" required>
                                <div class="form-text">Từ 4-20 ký tự, chỉ bao gồm chữ cái, số và dấu gạch dưới</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($registration['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Tối thiểu 6 ký tự</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="register" class="btn btn-primary btn-lg w-100 mt-3">
                                <i class="fas fa-user-plus me-2"></i>Đăng ký
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <div class="auth-footer py-3 px-4 text-center">
                        <p class="mb-0">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
