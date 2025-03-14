<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Thống kê nhanh
$stats = [
    'total_comics' => $conn->query("SELECT COUNT(*) FROM comics")->fetchColumn(),
    'total_chapters' => $conn->query("SELECT COUNT(*) FROM chapters")->fetchColumn(),
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_comments' => $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn()
];

// Truyện mới cập nhật
$stmt = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM chapters WHERE comic_id = c.id) as chapter_count,
           (SELECT COUNT(*) FROM follows WHERE comic_id = c.id) as follow_count
    FROM comics c
    ORDER BY c.updated_at DESC
    LIMIT 5
");
$recent_comics = $stmt->fetchAll();

// Chapter mới cập nhật
$stmt = $conn->query("
    SELECT ch.*, c.title as comic_title
    FROM chapters ch
    JOIN comics c ON ch.comic_id = c.id
    ORDER BY ch.created_at DESC
    LIMIT 5
");
$recent_chapters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Trang quản trị</h1>
                </div>

                <!-- Thống kê nhanh -->
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

                <!-- Truyện mới cập nhật -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Truyện mới cập nhật</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Tên truyện</th>
                                                <th>Số chapter</th>
                                                <th>Lượt theo dõi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_comics as $comic): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($comic['title']); ?></td>
                                                    <td><?php echo number_format($comic['chapter_count']); ?></td>
                                                    <td><?php echo number_format($comic['follow_count']); ?></td>
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
                                <h5 class="card-title">Chapter mới cập nhật</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Tên truyện</th>
                                                <th>Chapter</th>
                                                <th>Thời gian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_chapters as $chapter): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($chapter['comic_title']); ?></td>
                                                    <td>Chapter <?php echo $chapter['chapter_number']; ?></td>
                                                    <td><?php echo formatDate($chapter['created_at']); ?></td>
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
</body>

</html>