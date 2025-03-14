<?php
require_once '../includes/session.php';
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Lấy ID truyện từ URL
$comic_id = isset($_GET['comic_id']) ? (int)$_GET['comic_id'] : 0;
if (!$comic_id) {
    header('Location: manage-comics.php');
    exit();
}

// Lấy thông tin truyện
$stmt = $conn->prepare("SELECT * FROM comics WHERE id = ?");
$stmt->execute([$comic_id]);
$comic = $stmt->fetch();

if (!$comic) {
    header('Location: manage-comics.php');
    exit();
}

// Xử lý thêm chapter mới
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chapter_number = $_POST['chapter_number'];
    $title = $_POST['title'];

    try {
        $conn->beginTransaction();

        // Kiểm tra chapter number đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM chapters WHERE comic_id = ? AND chapter_number = ?");
        $stmt->execute([$comic_id, $chapter_number]);
        if ($stmt->fetch()) {
            throw new Exception('Số chapter này đã tồn tại!');
        }

        // Thêm chapter mới
        $stmt = $conn->prepare("INSERT INTO chapters (comic_id, chapter_number, title) VALUES (?, ?, ?)");
        $stmt->execute([$comic_id, $chapter_number, $title]);
        $chapter_id = $conn->lastInsertId();

        // Upload và lưu ảnh chapter
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/chapters/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $image_order = 1; // Bắt đầu từ số 1
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file = [
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ];

                $image_path = uploadFile($file, $upload_dir);
                if ($image_path) {
                    $stmt = $conn->prepare("
                        INSERT INTO chapter_images (chapter_id, image_path, image_order) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$chapter_id, $image_path, $image_order]);
                    $image_order++; // Tăng số thứ tự lên 1
                }
            }
        }

        $conn->commit();

        // Sau khi thêm chapter thành công, cập nhật thời gian updated_at của truyện
        $stmt = $conn->prepare("
            UPDATE comics 
            SET updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$comic_id]);

        header("Location: manage-chapters.php?comic_id=$comic_id&success=add");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm chapter mới - <?php echo htmlspecialchars($comic['title']); ?></title>
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
                    <h1>Thêm chapter mới - <?php echo htmlspecialchars($comic['title']); ?></h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Số chapter</label>
                                                <input type="number" step="0.1" class="form-control" name="chapter_number" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Tiêu đề (không bắt buộc)</label>
                                                <input type="text" class="form-control" name="title">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Ảnh chapter</label>
                                        <input type="file" class="form-control" name="images[]" accept="image/*" multiple required>
                                        <div class="form-text">Có thể chọn nhiều ảnh. Thứ tự ảnh sẽ được sắp xếp theo thứ tự chọn.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Xem trước ảnh</label>
                                        <div class="row" id="imagePreview"></div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Thêm chapter
                                    </button>
                                    <a href="manage-chapters.php?comic_id=<?php echo $comic_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Quay lại
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview ảnh trước khi upload
        document.querySelector('input[name="images[]"]').addEventListener('change', function() {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            for (const file of this.files) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML += `
                        <div class="col-md-4 mb-3">
                            <img src="${e.target.result}" class="img-thumbnail" alt="Preview">
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>