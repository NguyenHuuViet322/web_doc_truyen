<?php
require_once '../includes/session.php';
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Thống kê tổng quan
$stats = [
    'total_comics' => $conn->query("SELECT COUNT(*) FROM comics")->fetchColumn(),
    'total_chapters' => $conn->query("SELECT COUNT(*) FROM chapters")->fetchColumn(),
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_comments' => $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn()
];

// Thống kê truyện mới trong 7 ngày qua
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM comics
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute();
$new_comics_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê chapter mới trong 7 ngày qua
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM chapters
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute();
$new_chapters_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 truyện nhiều lượt xem nhất
$stmt = $conn->prepare("
    SELECT c.title, COUNT(v.id) as view_count
    FROM comics c
    LEFT JOIN chapters ch ON c.id = ch.comic_id
    LEFT JOIN views v ON ch.id = v.chapter_id
    GROUP BY c.id
    ORDER BY view_count DESC
    LIMIT 10
");
$stmt->execute();
$top_comics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 truyện nhiều lượt theo dõi nhất
$stmt = $conn->prepare("
    SELECT c.title, COUNT(f.id) as follow_count
    FROM comics c
    LEFT JOIN follows f ON c.id = f.comic_id
    GROUP BY c.id
    ORDER BY follow_count DESC
    LIMIT 10
");
$stmt->execute();
$top_followed_comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Thống kê</h1>
                </div>

                <!-- Thống kê tổng quan -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <h5 class="card-title">Tổng số truyện</h5>
                                <p class="card-text display-6"><?php echo number_format($stats['total_comics']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <h5 class="card-title">Tổng số chapter</h5>
                                <p class="card-text display-6"><?php echo number_format($stats['total_chapters']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <h5 class="card-title">Tổng số người dùng</h5>
                                <p class="card-text display-6"><?php echo number_format($stats['total_users']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <h5 class="card-title">Tổng số bình luận</h5>
                                <p class="card-text display-6"><?php echo number_format($stats['total_comments']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ thống kê -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Truyện mới trong 7 ngày qua</h5>
                                <canvas id="newComicsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Chapter mới trong 7 ngày qua</h5>
                                <canvas id="newChaptersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top truyện -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top 10 truyện nhiều lượt xem nhất</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tên truyện</th>
                                                <th>Lượt xem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_comics as $index => $comic): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($comic['title']); ?></td>
                                                    <td><?php echo number_format($comic['view_count']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top 10 truyện nhiều lượt theo dõi nhất</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tên truyện</th>
                                                <th>Lượt theo dõi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_followed_comics as $index => $comic): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($comic['title']); ?></td>
                                                    <td><?php echo number_format($comic['follow_count']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Biểu đồ truyện mới
        new Chart(document.getElementById('newComicsChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($new_comics_stats, 'date')); ?>,
                datasets: [{
                    label: 'Số truyện mới',
                    data: <?php echo json_encode(array_column($new_comics_stats, 'count')); ?>,
                    borderColor: '#2470dc',
                    tension: 0.1
                }]
            }
        });

        // Biểu đồ chapter mới
        new Chart(document.getElementById('newChaptersChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($new_chapters_stats, 'date')); ?>,
                datasets: [{
                    label: 'Số chapter mới',
                    data: <?php echo json_encode(array_column($new_chapters_stats, 'count')); ?>,
                    borderColor: '#20c997',
                    tension: 0.1
                }]
            }
        });
    </script>
</body>

</html>