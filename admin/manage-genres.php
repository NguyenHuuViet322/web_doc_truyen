<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Xử lý thêm thể loại mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_genre'])) {
    $name = trim($_POST['name']);
    $slug = createSlug($name);

    if (empty($name)) {
        $error = 'Vui lòng nhập tên thể loại!';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $success = 'Thêm thể loại thành công!';
        } catch (PDOException $e) {
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa thể loại
if (isset($_POST['delete_genre'])) {
    $genre_id = (int)$_POST['genre_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM genres WHERE id = ?");
        $stmt->execute([$genre_id]);
        $success = 'Xóa thể loại thành công!';
    } catch (PDOException $e) {
        $error = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}

// Lấy danh sách thể loại không trùng lặp tên
$genres = $conn->query("
    SELECT g1.* 
    FROM genres g1
    LEFT JOIN (
        SELECT name, MIN(id) as min_id
        FROM genres
        GROUP BY name
    ) g2 ON g1.name = g2.name AND g1.id = g2.min_id
    WHERE g1.id = g2.min_id
    ORDER BY g1.name ASC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thể loại - Admin</title>
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
                    <h1>Quản lý thể loại</h1>
                    <div>
                        <a href="sync-genres.php" class="btn btn-success me-2">
                            <i class="fas fa-sync"></i> Đồng bộ thể loại
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGenreModal">
                            <i class="fas fa-plus"></i> Thêm thể loại
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                                    unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success'];
                                                        unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <!-- <th>ID</th> -->
                                <th>Tên thể loại</th>
                                <th>Slug</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genres as $genre): ?>
                                <tr>
                                    <!-- <td><?php echo $genre['id']; ?></td> -->
                                    <td><?php echo htmlspecialchars($genre['name']); ?></td>
                                    <td><?php echo htmlspecialchars($genre['slug']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($genre['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bn có chắc muốn xóa thể loại này?');">
                                            <input type="hidden" name="genre_id" value="<?php echo $genre['id']; ?>">
                                            <button type="submit" name="delete_genre" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal thêm thể loại -->
    <div class="modal fade" id="addGenreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm thể loại mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên thể loại</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_genre" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>