<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize user data variables
$user_id = null;
$username = null;
$user_role = null;
$is_logged_in = false;
$is_admin = false;

// First try to get user data from cookies
if (isset($_COOKIE['user_data'])) {
    try {
        $user_data = json_decode($_COOKIE['user_data'], true);
        if ($user_data && isset($user_data['id'])) {
            $user_id = $user_data['id'];
            $username = $user_data['username'] ?? 'User';
            $user_role = $user_data['role'] ?? '';
            $is_logged_in = true;
            $is_admin = ($user_role === 'admin');
        }
    } catch (Exception $e) {
        // Failed to parse cookie, will check session instead
    }
}

// If not found in cookies, check session
if (!$is_logged_in && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'User';
    $user_role = $_SESSION['role'] ?? '';
    $is_logged_in = true;
    $is_admin = ($user_role === 'admin');
}

// Get current page for nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Website Truyện</a>
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
                <?php endif; ?>
            </ul>
            
            <form class="d-flex me-2" action="search.php" method="GET">
                <input class="form-control me-2" type="search" name="keyword" placeholder="Tìm truyện...">
                <button class="btn btn-outline-light" type="submit">Tìm</button>
            </form>
            
            <?php if ($is_logged_in): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php?id=<?php echo $user_id; ?>">Trang cá nhân</a></li>
                        <li><a class="dropdown-item" href="following.php">Truyện đang theo dõi</a></li>
                        <?php if ($is_admin): ?>
                            <li><a class="dropdown-item" href="admin/">Quản lý website</a></li>
                        <?php endif; ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light me-2">Đăng nhập</a>
                <a href="register.php" class="btn btn-light">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Debug info (remove in production) -->
<script>
console.log('User Auth Info:', {
    'user_id': <?php echo json_encode($user_id); ?>,
    'is_logged_in': <?php echo json_encode($is_logged_in); ?>,
    'is_admin': <?php echo json_encode($is_admin); ?>,
    'cookie_data': <?php echo isset($_COOKIE['user_data']) ? $_COOKIE['user_data'] : 'null'; ?>,
    'session_data': <?php echo json_encode($_SESSION); ?>
});
</script>