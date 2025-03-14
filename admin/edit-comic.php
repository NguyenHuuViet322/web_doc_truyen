<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/constants.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Lấy thông tin truyện
$comic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$comic_id) {
    header('Location: manage-comics.php');
    exit();
}

// Lấy danh sách thể loại
$genres = $conn->query("SELECT * FROM genres ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin truyện hiện tại
$stmt = $conn->prepare("SELECT * FROM comics WHERE id = ?");
$stmt->execute([$comic_id]);
$comic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comic) {
    header('Location: manage-comics.php');
    exit();
}

// Lấy thể loại của truyện
$stmt = $conn->prepare("SELECT genre_id FROM comic_genres WHERE comic_id = ?");
$stmt->execute([$comic_id]);
$comic_genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

            // Upload ảnh bìa mới nếu có
            $cover_image = $comic['cover_image'];
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $new_cover = uploadFile($_FILES['cover_image'], '../uploads/comics/');
                if (!$new_cover) {
                    throw new Exception('Lỗi upload ảnh bìa!');
                }
                $cover_image = $new_cover;

                // Xóa ảnh cũ nếu không phải ảnh mặc định
                if ($comic['cover_image'] != 'default.jpg') {
                    @unlink('../uploads/comics/' . $comic['cover_image']);
                }
            }

            // Xử lý categories
            $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
            $valid_categories = array_keys(CATEGORIES);
            $categories = array_intersect($categories, $valid_categories);
            $categories_str = implode(',', $categories);

            // Debug
            error_log("Categories: " . print_r($categories, true));
            error_log("Categories string: " . $categories_str);

            // Cập nhật thông tin truyện
            $stmt = $conn->prepare("
                UPDATE comics 
                SET title = ?, author = ?, description = ?, cover_image = ?, status = ?, categories = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $author, $description, $cover_image, $status, $categories_str, $comic_id]);

            // Cập nhật thể loại
            $stmt = $conn->prepare("DELETE FROM comic_genres WHERE comic_id = ?");
            $stmt->execute([$comic_id]);

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
            $success = 'Cập nhật truyện thành công!';

            // Reload để cập nh��t thông tin mới
            header("Location: edit-comic.php?id=$comic_id&success=1");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Hiển thị thông báo thành công từ redirect
if (isset($_GET['success'])) {
    $success = 'Cập nhật truyện thành công!';
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa truyện</title>
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
                    <h1>Chỉnh sửa truyện</h1>
                    <div>
                        <a href="manage-chapters.php?comic_id=<?php echo $comic_id; ?>" class="btn btn-primary">
                            <i class="fas fa-list"></i> Quản lý chapter
                        </a>
                        <a href="../comic.php?id=<?php echo $comic_id; ?>" class="btn btn-info" target="_blank">
                            <i class="fas fa-eye"></i> Xem truyện
                        </a>
                    </div>
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
                                        <input type="text" class="form-control" name="title"
                                            value="<?php echo htmlspecialchars($comic['title']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tác giả</label>
                                        <input type="text" class="form-control" name="author"
                                            value="<?php echo htmlspecialchars($comic['author']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea class="form-control" name="description" rows="5" required><?php echo htmlspecialchars($comic['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Ảnh bìa hiện tại</label>
                                        <img src="../uploads/comics/<?php echo $comic['cover_image']; ?>"
                                            class="img-thumbnail d-block mb-2" alt="Cover image">
                                        <input type="file" class="form-control" name="cover_image" accept="image/*">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select class="form-select" name="status" required>
                                            <option value="ongoing" <?php echo $comic['status'] == 'ongoing' ? 'selected' : ''; ?>>
                                                Đang tiến hành
                                            </option>
                                            <option value="completed" <?php echo $comic['status'] == 'completed' ? 'selected' : ''; ?>>
                                                Hoàn thành
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Thể loại</label>
                                        <div class="row categories-grid">
                                            <?php
                                            $current_categories = explode(',', $comic['categories']);
                                            $total_categories = count(CATEGORIES);
                                            $categories_per_column = ceil($total_categories / 3);
                                            $counter = 0;

                                            foreach (CATEGORIES as $value => $label):
                                                if ($counter % $categories_per_column == 0) {
                                                    echo '<div class="col-md-4">';
                                                }
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="categories[]" value="<?php echo $value; ?>"
                                                        id="category_<?php echo $value; ?>"
                                                        <?php echo in_array($value, $current_categories) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="category_<?php echo $value; ?>">
                                                        <?php echo $label; ?>
                                                    </label>
                                                </div>
                                            <?php
                                                $counter++;
                                                if ($counter % $categories_per_column == 0 || $counter == $total_categories) {
                                                    echo '</div>';
                                                }
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu thay đổi
                                </button>
                                <a href="manage-comics.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
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