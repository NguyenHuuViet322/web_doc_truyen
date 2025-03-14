<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Xử lý xóa bình luận
if (isset($_POST['delete'])) {
    $comment_id = $_POST['comment_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        header('Location: manage-comments.php?success=delete');
        exit();
    } catch (Exception $e) {
        $error = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Đếm tổng số bình luận
$stmt = $conn->prepare("SELECT COUNT(*) FROM comments");
$stmt->execute();
$total_comments = $stmt->fetchColumn();
$total_pages = ceil($total_comments / $limit);

// Lấy danh sách bình luận
$stmt = $conn->prepare("
    SELECT c.*, u.username, co.title as comic_title, ch.chapter_number
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN comics co ON c.comic_id = co.id
    LEFT JOIN chapters ch ON c.chapter_id = ch.id
    ORDER BY c.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bình luận</title>
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
                    <h1>Quản lý bình luận</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        switch ($_GET['success']) {
                            case 'delete':
                                echo 'Xóa bình luận thành công!';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Người dùng</th>
                                <th>Truyện</th>
                                <th>Chapter</th>
                                <th>Nội dung</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['comic_title']); ?></td>
                                    <td>
                                        <?php echo $comment['chapter_number'] ? 'Chapter ' . $comment['chapter_number'] : ''; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($comment['content']); ?></td>
                                    <td><?php echo formatDate($comment['created_at']); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa bình luận này?');">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
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