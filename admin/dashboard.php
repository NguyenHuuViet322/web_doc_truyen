<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập
if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Thống kê tổng quan
$stats = [
    'total_comics' => $conn->query("SELECT COUNT(*) FROM comics")->fetchColumn(),
    'total_chapters' => $conn->query("SELECT COUNT(*) FROM chapters")->fetchColumn(),
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_comments' => $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
];

// Truyện mới cập nhật
$latest_comics = $conn->query("
    SELECT c.*, COUNT(ch.id) as chapter_count 
    FROM comics c 
    LEFT JOIN chapters ch ON c.id = ch.comic_id 
    GROUP BY c.id 
    ORDER BY c.updated_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Truyện nhiều lượt xem
$popular_comics = $conn->query("
    SELECT * FROM comics 
    ORDER BY views DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Thống kê theo tháng
$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total
    FROM chapters
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block bg-dark sidebar min-vh-100">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="dashboard.php">
                                <i class="fas fa-home"></i> Tổng quan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage-comics.php">
                                <i class="fas fa-book"></i> Quản lý truyện
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage-users.php">
                                <i class="fas fa-users"></i> Quản lý người dùng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage-comments.php">
                                <i class="fas fa-comments"></i> Quản lý bình luận
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Bảng điều khiển</h1>
                </div>

                <!-- Thống kê tổng quan -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tổng số truyện</h5>
                                <h2><?php echo $stats['total_comics']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tổng số chapter</h5>
                                <h2><?php echo $stats['total_chapters']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Người dùng</h5>
                                <h2><?php echo $stats['total_users']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Bình luận</h5>
                                <h2><?php echo $stats['total_comments']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ thống kê -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Thống kê chapter theo tháng</h5>
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Truyện xem nhiều nhất</h5>
                                <ul class="list-group">
                                    <?php foreach ($popular_comics as $comic): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo $comic['title']; ?>
                                            <span class="badge bg-primary rounded-pill">
                                                <?php echo number_format($comic['views']); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Biểu đồ thống kê theo tháng
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_stats, 'month')); ?>,
                datasets: [{
                    label: 'Số chapter mới',
                    data: <?php echo json_encode(array_column($monthly_stats, 'total')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>