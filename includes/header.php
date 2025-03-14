<?php
$constants_path = __DIR__ . '/constants.php';
require_once $constants_path;
?>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book-reader me-2"></i>
                Truyện Tranh Online
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-tags me-1"></i> Thể loại
                        </a>
                        <div class="dropdown-menu dropdown-menu-categories">
                            <?php foreach (CATEGORIES as $slug => $name): ?>
                                <a class="dropdown-item" href="categories.php?type=<?php echo $slug; ?>">
                                    <?php echo $name; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'latest.php' ? 'active' : ''; ?>" href="latest.php">
                            <i class="fas fa-clock me-1"></i> Mới cập nhật
                        </a>
                    </li>
                </ul>
                <form class="d-flex me-2" action="search.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="keyword" placeholder="Tìm truyện..." required>
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>
</header>