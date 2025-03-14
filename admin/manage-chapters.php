<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Lấy ID truyện từ URL
$comic_id = isset($_GET['comic_id']) ? (int)$_GET['comic_id'] : 0;
if (!$comic_id) {
    header('Location: manage-comics.php');
    exit();
}

// Lấy thông tin truyện
$stmt = $conn->prepare("SELECT * FROM comics WHERE id = ?");
$stmt->execute([$comic_id]);
$comic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comic) {
    header('Location: manage-comics.php');
    exit();
}

// Xử lý xóa chapter
if (isset($_POST['delete'])) {
    $chapter_id = $_POST['chapter_id'];
    try {
        $conn->beginTransaction();

        // Xóa ảnh của chapter
        $stmt = $conn->prepare("SELECT image_path FROM chapter_images WHERE chapter_id = ?");
        $stmt->execute([$chapter_id]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($images as $image) {
            @unlink('../uploads/chapters/' . $image);
        }

        // Xóa dữ liệu từ database
        $stmt = $conn->prepare("DELETE FROM chapter_images WHERE chapter_id = ?");
        $stmt->execute([$chapter_id]);

        $stmt = $conn->prepare("DELETE FROM chapters WHERE id = ?");
        $stmt->execute([$chapter_id]);

        $conn->commit();
        header("Location: manage-chapters.php?comic_id=$comic_id&success=delete");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Có lỗi xảy ra khi xóa chapter!';
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Đếm tổng số chapter
$stmt = $conn->prepare("SELECT COUNT(*) FROM chapters WHERE comic_id = ?");
$stmt->execute([$comic_id]);
$total_chapters = $stmt->fetchColumn();
$total_pages = ceil($total_chapters / $limit);

// Lấy danh sách chapter
$stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM chapter_images WHERE chapter_id = c.id) as image_count,
           (SELECT COUNT(*) FROM views WHERE chapter_id = c.id) as view_count
    FROM chapters c
    WHERE c.comic_id = ?
    ORDER BY c.chapter_number DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $comic_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý chapter - <?php echo htmlspecialchars($comic['title']); ?></title>
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
                    <h1>Quản lý chapter - <?php echo htmlspecialchars($comic['title']); ?></h1>
                    <div>
                        <a href="add-chapter.php?comic_id=<?php echo $comic_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm chapter mới
                        </a>
                        <a href="edit-comic.php?id=<?php echo $comic_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php
                        switch ($_GET['success']) {
                            case 'add':
                                echo 'Thêm chapter mới thành công!';
                                break;
                            case 'edit':
                                echo 'Cập nhật chapter thành công!';
                                break;
                            case 'delete':
                                echo 'Xóa chapter thành công!';
                                break;
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Danh sách chapter -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Chapter</th>
                                <th>Tiêu đề</th>
                                <th>Số trang</th>
                                <th>Lượt xem</th>
                                <th>Ngày đăng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chapters as $chapter): ?>
                                <tr>
                                    <td>Chapter <?php echo $chapter['chapter_number']; ?></td>
                                    <td><?php echo htmlspecialchars($chapter['title']); ?></td>
                                    <td><?php echo $chapter['image_count']; ?></td>
                                    <td><?php echo number_format($chapter['view_count']); ?></td>
                                    <td><?php echo formatDate($chapter['created_at']); ?></td>
                                    <td>
                                        <a href="edit-chapter.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa chapter này?');">
                                            <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <a href="../read.php?comic_id=<?php echo $comic_id; ?>&chapter=<?php echo $chapter['chapter_number']; ?>"
                                            class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?comic_id=<?php echo $comic_id; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>