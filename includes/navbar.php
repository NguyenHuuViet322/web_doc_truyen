<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Website Truyện</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="latest.php">Mới cập nhật</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">Thể loại</a>
                </li>
            </ul>
            <form class="d-flex me-2" action="search.php" method="GET">
                <input class="form-control me-2" type="search" name="keyword" placeholder="Tìm truyện...">
                <button class="btn btn-outline-light" type="submit">Tìm</button>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">Trang cá nhân</a></li>
                        <li><a class="dropdown-item" href="following.php">Truyện đang theo dõi</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="admin/">Quản lý website</a></li>
                        <?php endif; ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light me-2">Đăng nhập</a>
                <a href="register.php" class="btn btn-light">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</nav>