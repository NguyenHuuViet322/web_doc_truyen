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

// Lấy danh sách thể loại
$genres = $conn->query("SELECT * FROM genres ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $genre_ids = $_POST['genres'] ?? [];

    // Validate dữ liệu
    if (empty($title) || empty($author) || empty($description)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        try {
            $conn->beginTransaction();

            // Upload ảnh bìa
            $cover_image = 'default.jpg';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $cover_image = uploadFile($_FILES['cover_image'], '../uploads/comics/');
                if (!$cover_image) {
                    throw new Exception('Lỗi upload ảnh bìa!');
                }
            }

            // Thêm truyện mới
            $stmt = $conn->prepare("
                INSERT INTO comics (title, author, description, cover_image, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$title, $author, $description, $cover_image, $status]);
            $comic_id = $conn->lastInsertId();

            // Thêm thể loại cho truyện
            if (!empty($genre_ids)) {
                $values = str_repeat('(?,?),', count($genre_ids) - 1) . '(?,?)';
                $params = [];
                foreach ($genre_ids as $genre_id) {
                    $params[] = $comic_id;
                    $params[] = $genre_id;
                }

                $stmt = $conn->prepare("
                    INSERT INTO comic_genres (comic_id, genre_id)
                    VALUES $values
                ");
                $stmt->execute($params);
            }

            $conn->commit();
            $success = 'Thêm truyện mới thành công!';

            // Chuyển hướng sau khi thêm thành công
            header('Location: manage-comics.php');
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm truyện mới</title>
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
                    <h1>Thêm truyện mới</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Tên truyện</label>
                                        <input type="text" class="form-control" name="title" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tác giả</label>
                                        <input type="text" class="form-control" name="author" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea class="form-control" name="description" rows="5" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Ảnh bìa</label>
                                        <input type="file" class="form-control" name="cover_image" accept="image/*">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select class="form-select" name="status" required>
                                            <option value="ongoing">Đang tiến hành</option>
                                            <option value="completed">Hoàn thành</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Thể loại</label>
                                        <div class="row">
                                            <?php foreach ($genres as $genre): ?>
                                                <div class="col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="genres[]"
                                                            value="<?php echo $genre['id']; ?>" id="genre<?php echo $genre['id']; ?>">
                                                        <label class="form-check-label" for="genre<?php echo $genre['id']; ?>">
                                                            <?php echo htmlspecialchars($genre['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu truyện
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>