<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Xử lý xóa truyện
if (isset($_POST['delete'])) {
    $comic_id = $_POST['comic_id'];
    try {
        $conn->beginTransaction();

        // Lấy danh sách ảnh chapter cần xóa
        $stmt = $conn->prepare("
            SELECT ci.image_path 
            FROM chapter_images ci
            JOIN chapters ch ON ci.chapter_id = ch.id
            WHERE ch.comic_id = ?
        ");
        $stmt->execute([$comic_id]);
        $chapter_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Xóa các ảnh chapter
        foreach ($chapter_images as $image) {
            @unlink('../uploads/chapters/' . $image);
        }

        // Lấy ảnh bìa truyện
        $stmt = $conn->prepare("SELECT cover_image FROM comics WHERE id = ?");
        $stmt->execute([$comic_id]);
        $cover_image = $stmt->fetchColumn();

        // Xóa ảnh bìa nếu không phải ảnh mặc định
        if ($cover_image && $cover_image != 'default.jpg') {
            @unlink('../uploads/comics/' . $cover_image);
        }

        // Xóa truyện
        $stmt = $conn->prepare("DELETE FROM comics WHERE id = ?");
        $stmt->execute([$comic_id]);

        // Cập nhật lại ID của các truyện còn lại
        $conn->query("SET @count = 0");
        $conn->query("UPDATE comics SET id = @count:= @count + 1 ORDER BY id");

        // Reset auto increment
        require_once 'reset-auto-increment.php';
        resetAutoIncrement($conn, 'comics');

        $conn->commit();
        header('Location: manage-comics.php?success=delete');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Có lỗi xảy ra khi xóa truyện!';
    }
}

// Phân trang và tìm kiếm
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xây dựng câu truy vấn cơ bản
$sql = "
    SELECT c.*,
           (SELECT COUNT(*) FROM chapters WHERE comic_id = c.id) as chapter_count,
           (SELECT COUNT(*) FROM follows WHERE comic_id = c.id) as follow_count
    FROM comics c
";

$count_sql = "SELECT COUNT(*) FROM comics";
$params = [];

// Thêm điều kiện tìm kiếm nếu có
if ($search !== '') {
    $sql .= " WHERE (c.title LIKE :search OR c.author LIKE :search)";
    $count_sql .= " WHERE (title LIKE :search OR author LIKE :search)";
    $params[':search'] = "%$search%";
}

// Thêm sắp xếp và phân trang
$sql .= " ORDER BY c.id ASC LIMIT :limit OFFSET :offset";

// Đếm tổng số truyện
$count_stmt = $conn->prepare($count_sql);
if ($search !== '') {
    $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$count_stmt->execute();
$total_comics = $count_stmt->fetchColumn();
$total_pages = ceil($total_comics / $limit);

// Lấy danh sách truyện
$stmt = $conn->prepare($sql);
if ($search !== '') {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý truyện</title>
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
                    <h1>Quản lý truyện</h1>
                    <a href="add-comic.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Thêm truyện mới
                    </a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php
                        switch ($_GET['success']) {
                            case 'add':
                                echo 'Thêm truyện mới thành công!';
                                break;
                            case 'edit':
                                echo 'Cập nhật truyện thành công!';
                                break;
                            case 'delete':
                                echo 'Xóa truyện thành công!';
                                break;
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Thanh tìm kiếm -->
                <form class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search"
                                placeholder="Tìm kiếm theo tên truyện hoặc tác giả..."
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Danh sách truyện -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ảnh bìa</th>
                                <th>Tên truyện</th>
                                <th>Tác giả</th>
                                <th>Số chapter</th>
                                <th>Lượt theo dõi</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comics as $comic): ?>
                                <tr>
                                    <td><?php echo $comic['id']; ?></td>
                                    <td>
                                        <img src="../uploads/comics/<?php echo $comic['cover_image']; ?>"
                                            alt="Cover" class="img-thumbnail" style="width: 50px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($comic['title']); ?></td>
                                    <td><?php echo htmlspecialchars($comic['author']); ?></td>
                                    <td><?php echo $comic['chapter_count']; ?></td>
                                    <td><?php echo number_format($comic['follow_count']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $comic['status'] == 'ongoing' ? 'primary' : 'success'; ?>">
                                            <?php echo $comic['status'] == 'ongoing' ? 'Đang tiến hành' : 'Hoàn thành'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit-comic.php?id=<?php echo $comic['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage-chapters.php?comic_id=<?php echo $comic['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-list"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa truyện này?');">
                                            <input type="hidden" name="comic_id" value="<?php echo $comic['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <a href="../read.php?id=<?php echo $comic['id']; ?>" class="btn btn-sm btn-success" target="_blank">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
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