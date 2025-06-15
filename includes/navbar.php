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
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $is_logged_in = true;
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $user = [
        'id' => $_SESSION['user_id'],
        'username' => isset($_SESSION['username']) ? $_SESSION['username'] : 'User',
        'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '',
        'role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'user',
        'avatar' => isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'default.jpg',
        'created_at' => isset($_SESSION['created_at']) ? $_SESSION['created_at'] : date('Y-m-d H:i:s'),
        'status' => isset($_SESSION['status']) ? $_SESSION['status'] : 'active',
        // 'last_login' => isset($_SESSION['last_login']) ? $_SESSION['last_login'] : date('Y-m-d H:i:s')
    ];
}
// If no session but we have auth token - only on pages that aren't login/register/logout
else if (isset($_COOKIE['auth_token']) && isset($_COOKIE['user_data']) && 
         stripos($_SERVER['SCRIPT_NAME'], 'login.php') === false && 
         stripos($_SERVER['SCRIPT_NAME'], 'register.php') === false && 
         stripos($_SERVER['SCRIPT_NAME'], 'logout.php') === false) {
    try {
        // Important: Only try to use cookies if we're not already in a session
        // This prevents cookie conflicts with new registrations
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            require_once 'functions.php';
            
            // Verify the token without database lookup
            $token_data = verifyAuthToken($_COOKIE['auth_token']);
            
            // Only proceed if we have valid token data
            if ($token_data && isset($token_data['id'])) {
                // Check if database connection exists, if not create it
                if (!isset($conn) || !$conn) {
                    require_once 'database.php';
                    $db = new Database();
                    $conn = $db->getConnection();
                }
                  // Get the user record to make sure they still exist and are not banned
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status != 'banned'");
                $stmt->execute([$token_data['id']]);
                $verified_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug user data in navbar.php
                error_log("Navbar token auth: User ID: " . $token_data['id']);
                error_log("User data found in navbar: " . ($verified_user ? "Yes" : "No"));
                if ($verified_user) {
                    error_log("User columns in navbar: " . implode(", ", array_keys($verified_user)));
                } else {
                    // Debug the query itself
                    error_log("Last SQL query: " . $stmt->queryString);
                    error_log("Parameters: " . $token_data['id']);
                }
                
                if ($verified_user) {                    // Dump the entire user object for debugging
                    error_log("User data retrieved: " . print_r($verified_user, true));
                      // Valid access key - set session data directly from database with careful checks
                    // Essential fields - with safe fallbacks
                    $_SESSION['user_id'] = $verified_user['id'] ?? 0; // Should always exist
                    
                    if (isset($verified_user['username'])) {
                        $_SESSION['username'] = $verified_user['username'];
                    } else {
                        $_SESSION['username'] = 'User';
                        error_log("username field missing in user record");
                    }
                    
                    if (isset($verified_user['email'])) {
                        $_SESSION['email'] = $verified_user['email'];
                    } else {
                        $_SESSION['email'] = '';
                        error_log("email field missing in user record");
                    }
                    
                    if (isset($verified_user['role'])) {
                        $_SESSION['role'] = $verified_user['role'];
                    } else {
                        $_SESSION['role'] = 'user';
                        error_log("role field missing in user record");
                    }
                    
                    $_SESSION['logged_in'] = true;
                      // Optional fields with explicit checks and debug logging
                    if (isset($verified_user['created_at'])) {
                        $_SESSION['created_at'] = $verified_user['created_at'];
                    } else {
                        $_SESSION['created_at'] = date('Y-m-d H:i:s');
                        error_log("NAVBAR: created_at not found in user data, using current date");
                    }
                    
                    if (isset($verified_user['status'])) {
                        $_SESSION['status'] = $verified_user['status'];
                        error_log("NAVBAR: Setting status from database: " . $verified_user['status']);
                    } else {
                        $_SESSION['status'] = 'active';
                        error_log("NAVBAR: status not found in user data, using 'active'");
                    }
                      // More optional fields with null coalescing operator for cleaner code
                    $_SESSION['last_login'] = $verified_user['last_login'] ?? date('Y-m-d H:i:s');
                    
                    // Store access_key if present in database
                    if (isset($verified_user['access_key'])) {
                        $_SESSION['access_key'] = $verified_user['access_key'];
                    }
                      // Set avatar from database or default 
                    // The ?? operator checks if the left side is null or doesn't exist (undefined)
                    $_SESSION['avatar'] = !empty($verified_user['avatar']) ? $verified_user['avatar'] : 'default.jpg';
                    
                    // Update last login time
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$verified_user['id']]);
                    
                    // Update user variables for this page load
                    $is_logged_in = true;
                    $is_admin = $verified_user['role'] === 'admin';
                    $user = $verified_user;
                } else {
                    // Invalid cookie data - clear all cookies
                    require_once 'functions.php';
                    clearAllAuthCookies();
                }
            }        }
    }
    catch (Exception $e) {
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
                            <?php
                            // Make sure avatar directory exists
                            $avatar_dir = 'uploads/avatars/';
                            if (!file_exists($avatar_dir)) {
                                // Try to create it if it doesn't exist
                                @mkdir($avatar_dir, 0777, true);
                            }
                            
                            // Try to get avatar from session with fallbacks
                            $avatar_file = !empty($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : 'default.jpg';
                            
                            // Check if the file exists
                            $avatar_path = $avatar_dir . $avatar_file;
                            $avatar_url = "../" . $avatar_path; // For when navbar is included in admin subdirectory
                            
                            // Handle the path differently based on if we're in admin or not
                            if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
                                echo '<img src="' . $avatar_url . '" alt="Avatar" class="rounded-circle me-2" width="32" height="32">';
                            } else {
                                echo '<img src="' . $avatar_path . '" alt="Avatar" class="rounded-circle me-2" width="32" height="32">';
                            }
                            ?>
                            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
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
                            <!-- <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>Cài đặt tài khoản
                                </a>
                            </li> -->
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