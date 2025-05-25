<?php 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Try to get user from cookie if not in session
$user = null;
$is_logged_in = false;
$is_admin = false;

// Check session first (fastest method)
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $is_logged_in = true;
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $user = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'],
        'avatar' => $_SESSION['avatar'] ?? '',
        'created_at' => $_SESSION['created_at'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'status' => $_SESSION['status'] ?? '',
        'last_login' => $_SESSION['last_login'] ?? ''
    ];
}
// If no session but we have cookies, verify access_key
else if (isset($_COOKIE['access_key']) && isset($_COOKIE['user_data'])) {
    try {
        $user_data = json_decode($_COOKIE['user_data'], true);
        $access_key = $_COOKIE['access_key'];
        
        if ($user_data && $access_key) {
            // Check if access_key column exists
            $check_column_stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'access_key'");
            $column_exists = $check_column_stmt->rowCount() > 0;
            
            if ($column_exists) {
                // Verify the access key matches what's in the database
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND access_key = ?");
                $stmt->execute([$user_data['id'], $access_key]);
                $verified_user = $stmt->fetch();
            } else {
                // If access_key doesn't exist, just get the user by ID
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_data['id']]);
                $verified_user = $stmt->fetch();
            }
            
            if ($verified_user) {
                // Valid access key - set session data
                $_SESSION['user_id'] = $verified_user['id'];
                $_SESSION['username'] = $verified_user['username'];
                $_SESSION['role'] = $verified_user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['access_key'] = $access_key;
                
                // Update user variables for this page load
                $is_logged_in = true;
                $is_admin = $verified_user['role'] === 'admin';
                $user = [
                    'id' => $verified_user['id'],
                    'username' => $verified_user['username'],
                    'role' => $verified_user['role'],
                    'avatar' => $verified_user['avatar'] ?? ''
                ];
            }
        }
    } catch (Exception $e) {
        // Error parsing cookie data or database error - ignore and treat as logged out
        error_log("Error in navbar authentication: " . $e->getMessage());
    }
}

// Output debug info to console
echo "<!-- User Auth Debug:
Is logged in: " . ($is_logged_in ? 'Yes' : 'No') . "
User ID: " . ($user ? $user['id'] : 'none') . "
-->";
// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg <?php echo ($is_logged_in) ? 'navbar-light bg-light' : 'navbar-dark bg-dark'; ?>">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <?php if (isset($_SESSION['user_id'])): ?>
                <i class="fas fa-book-reader text-primary me-2"></i>
            <?php else: ?>
                <i class="fas fa-book-reader text-light me-2"></i>
            <?php endif; ?>
            Website Truyện
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i>Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'latest.php') ? 'active' : ''; ?>" href="latest.php">
                        <i class="fas fa-clock me-1"></i>Mới cập nhật
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                        <i class="fas fa-list me-1"></i>Thể loại
                    </a>
                </li>
                <?php if ($is_logged_in): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'following.php') ? 'active' : ''; ?>" href="following.php">
                        <i class="fas fa-heart me-1"></i>Theo dõi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'history.php') ? 'active' : ''; ?>" href="history.php">
                        <i class="fas fa-history me-1"></i>Lịch sử đọc
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <form class="d-flex me-2" action="search.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="keyword" placeholder="Tìm truyện..." 
                           value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                    <button class="btn <?php echo ($is_logged_in) ? 'btn-primary' : 'btn-outline-light'; ?>" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <?php if ($is_logged_in): ?>
                <!-- Logged-in user menu -->
                <div class="d-flex align-items-center">
                    <div class="position-relative me-3">
                        <a href="notifications.php" class="btn btn-outline-secondary position-relative">
                            <i class="fas fa-bell"></i>
                            <?php
                            // Check for unread notifications (replace with your actual logic)
                            $unread_count = 0; // Replace with actual count
                            if ($unread_count > 0):
                            ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo ($unread_count > 9) ? '9+' : $unread_count; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <?php if (isset($_SESSION['avatar']) && !empty($_SESSION['avatar'])): ?>
                                <img src="uploads/avatars/<?php echo $_SESSION['avatar']; ?>" 
                                     alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-2"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Trang cá nhân
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="following.php">
                                    <i class="fas fa-heart me-2"></i>Truyện đang theo dõi
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>Cài đặt tài khoản
                                </a>
                            </li>
                            <?php if ($is_admin): ?>
                                <li>
                                    <a class="dropdown-item text-danger" href="admin/">
                                        <i class="fas fa-tools me-2"></i>Quản lý website
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?');">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- Guest user controls -->
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i>Đăng ký
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Debug user authentication information in console
console.log('==== USER AUTH STATUS ====');
console.log('Is logged in: <?php echo $is_logged_in ? "true" : "false"; ?>');
console.log('Session data:', <?php echo json_encode($_SESSION); ?>);
console.log('Cookie data:', {
    access_key: "<?php echo isset($_COOKIE['access_key']) ? 'exists' : 'not set'; ?>",
    user_data: <?php echo isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : 'null'; ?>
});
console.log('========================');
</script>