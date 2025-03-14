<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                    href="index.php">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-comics.php', 'add-comic.php', 'edit-comic.php']) ? 'active' : ''; ?>"
                    href="manage-comics.php">
                    <i class="fas fa-book"></i> Quản lý truyện
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-genres.php' ? 'active' : ''; ?>"
                    href="manage-genres.php">
                    <i class="fas fa-tags"></i> Quản lý thể loại
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>"
                    href="manage-users.php">
                    <i class="fas fa-users"></i> Quản lý người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-comments.php' ? 'active' : ''; ?>"
                    href="manage-comments.php">
                    <i class="fas fa-comments"></i> Quản lý bình luận
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>"
                    href="statistics.php">
                    <i class="fas fa-chart-bar"></i> Thống kê
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"
                    href="settings.php">
                    <i class="fas fa-cog"></i> Cài đặt
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Báo cáo</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="reports.php?type=monthly">
                    <i class="fas fa-file-alt"></i> Báo cáo tháng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php?type=yearly">
                    <i class="fas fa-file-alt"></i> Báo cáo năm
                </a>
            </li>
        </ul>
    </div>
</nav>