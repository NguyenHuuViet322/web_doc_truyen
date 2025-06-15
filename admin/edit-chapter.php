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

// Lấy thông tin chapter
$chapter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$chapter_id) {
    header('Location: manage-comics.php');
    exit();
}

// Lấy thông tin chapter và truyện
$stmt = $conn->prepare("
    SELECT ch.*, c.title as comic_title, c.id as comic_id
    FROM chapters ch
    JOIN comics c ON ch.comic_id = c.id
    WHERE ch.id = ?
");
$stmt->execute([$chapter_id]);
$chapter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chapter) {
    header('Location: manage-comics.php');
    exit();
}

// Lấy danh sách ảnh của chapter
$stmt = $conn->prepare("SELECT * FROM chapter_images WHERE chapter_id = ? ORDER BY image_order");
$stmt->execute([$chapter_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chapter_number = $_POST['chapter_number'];
    $title = $_POST['title'];
    $deleted_images = $_POST['delete_images'] ?? [];
    $page_names = $_POST['page_names'] ?? [];

    try {
        $conn->beginTransaction();

        // Kiểm tra số chapter có bị trùng không
        if ($chapter_number != $chapter['chapter_number']) {
            $stmt = $conn->prepare("
                SELECT id FROM chapters 
                WHERE comic_id = ? AND chapter_number = ? AND id != ?
            ");
            $stmt->execute([$chapter['comic_id'], $chapter_number, $chapter_id]);
            if ($stmt->fetch()) {
                throw new Exception('Chapter này đã tồn tại!');
            }
        }        // Cập nhật thông tin chapter
        $stmt = $conn->prepare("
            UPDATE chapters 
            SET chapter_number = ?, title = ?
            WHERE id = ?
        ");
        $stmt->execute([$chapter_number, $title, $chapter_id]);
        
        // Cập nhật tên trang cho các ảnh
        if (!empty($page_names)) {
            foreach ($page_names as $image_id => $page_name) {
                $stmt = $conn->prepare("
                    UPDATE chapter_images 
                    SET page_chapter_image = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$page_name, $image_id]);
            }
        }

        // Xóa các ảnh đã chọn
        if (!empty($deleted_images)) {
            $placeholders = str_repeat('?,', count($deleted_images) - 1) . '?';
            $stmt = $conn->prepare("
                SELECT image_path FROM chapter_images 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($deleted_images);
            $images_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($images_to_delete as $image) {
                @unlink('../uploads/chapters/' . $image);
            }

            $stmt = $conn->prepare("DELETE FROM chapter_images WHERE id IN ($placeholders)");
            $stmt->execute($deleted_images);
        }

        // Thêm ảnh mới nếu có
        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $new_images = $_FILES['new_images'];
            $total = count($new_images['name']);
            $values = [];
            $params = [];            // Lấy số thứ tự ảnh lớn nhất hiện tại
            $stmt = $conn->prepare("
                SELECT COALESCE(MAX(image_order), 0) 
                FROM chapter_images 
                WHERE chapter_id = ?
            ");
            $stmt->execute([$chapter_id]);
            $max_page = $stmt->fetchColumn();

            for ($i = 0; $i < $total; $i++) {
                if ($new_images['error'][$i] == 0) {
                    $temp = [
                        'name' => $new_images['name'][$i],
                        'type' => $new_images['type'][$i],
                        'tmp_name' => $new_images['tmp_name'][$i],
                        'error' => $new_images['error'][$i],
                        'size' => $new_images['size'][$i]
                    ];                    $image_path = uploadFile($temp, '../uploads/chapters/');
                    if ($image_path) {
                        // Extract the file name without extension to use as page_chapter_image
                        $page_name = pathinfo($new_images['name'][$i], PATHINFO_FILENAME);
                        
                        $values[] = "(?, ?, ?, ?)";
                        $params = array_merge($params, [
                            $chapter_id,
                            $image_path,
                            $max_page + $i + 1,
                            $page_name
                        ]);
                    }
                }
            }

            if (!empty($values)) {                $stmt = $conn->prepare(
                    "
                    INSERT INTO chapter_images (chapter_id, image_path, image_order, page_chapter_image)
                    VALUES " . implode(',', $values)
                );
                $stmt->execute($params);
            }
        }

        $conn->commit();
        header("Location: manage-chapters.php?comic_id={$chapter['comic_id']}&success=edit");
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
    <title>Chỉnh sửa chapter - <?php echo htmlspecialchars($chapter['comic_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .position-absolute.top-0.start-0 input {
            width: 120px;
            opacity: 0.8;
        }
        .position-absolute.top-0.start-0 input:focus {
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Chỉnh sửa chapter - <?php echo htmlspecialchars($chapter['comic_title']); ?></h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Số chapter</label>
                                        <input type="number" class="form-control" name="chapter_number"
                                            value="<?php echo $chapter['chapter_number']; ?>" step="0.1" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tiêu đề (không bắt buộc)</label>
                                        <input type="text" class="form-control" name="title"
                                            value="<?php echo htmlspecialchars($chapter['title']); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Thêm ảnh mới</label>
                                        <input type="file" class="form-control" name="new_images[]" accept="image/*" multiple>
                                        <div class="form-text">Ảnh mới sẽ được thêm vào sau các ảnh hiện tại</div>
                                    </div>

                                    <div id="imagePreview" class="row mt-3">
                                        <!-- Ảnh preview sẽ hiển thị ở đây -->
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Ảnh hiện tại</label>
                                        <div class="row">
                                            <?php foreach ($images as $image): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="position-relative">                                                        <img src="../uploads/chapters/<?php echo $image['image_path']; ?>"
                                                            class="img-thumbnail" alt="Page <?php echo $image['image_order']; ?>">
                                                        <div class="form-check position-absolute top-0 end-0 m-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="delete_images[]" value="<?php echo $image['id']; ?>"
                                                                id="img<?php echo $image['id']; ?>">
                                                            <label class="form-check-label" for="img<?php echo $image['id']; ?>">
                                                                Xóa
                                                            </label>
                                                        </div>
                                                        <div class="position-absolute top-0 start-0 m-2">
                                                            <input type="text" class="form-control form-control-sm" 
                                                                name="page_names[<?php echo $image['id']; ?>]" 
                                                                placeholder="Tên trang" 
                                                                value="<?php echo htmlspecialchars($image['page_chapter_image'] ?? ''); ?>">
                                                        </div><div class="position-absolute bottom-0 start-0 m-2 badge bg-dark">
                                                            <?php 
                                                                // Show page_chapter_image if available, otherwise show image_order
                                                                echo !empty($image['page_chapter_image']) ? htmlspecialchars($image['page_chapter_image']) : 'Trang ' . $image['image_order']; 
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Lưu thay đổi
                                        </button>
                                        <a href="manage-chapters.php?comic_id=<?php echo $chapter['comic_id']; ?>"
                                            class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Quay lại
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview ảnh trước khi upload
        document.querySelector('input[name="new_images[]"]').addEventListener('change', function() {
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