<?php
require_once '../includes/session.php';
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Kiểm tra quyền admin
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Xử lý khóa/mở khóa tài khoản
if (isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['status'] == 'active' ? 'banned' : 'active';
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    header('Location: manage-users.php');
    exit();
}

// Xử lý xóa người dùng
if (isset($_POST['delete'])) {
    $user_id = $_POST['user_id'];
    try {
        $conn->beginTransaction();

        // Xóa người dùng
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        // Cập nhật lại ID của các người dùng còn lại
        $conn->query("SET @count = 0");
        $conn->query("UPDATE users SET id = @count:= @count + 1 ORDER BY id");

        // Reset auto increment
        require_once 'reset-auto-increment.php';
        resetAutoIncrement($conn, 'users');

        $conn->commit();
        header('Location: manage-users.php?success=delete');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Có lỗi xảy ra khi xóa người dùng!';
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%"]);
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if ($role_filter) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Lấy tổng số người dùng
$stmt = $conn->prepare("SELECT COUNT(*) FROM users $where_clause");
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Lấy danh sách người dùng
$stmt = $conn->prepare("
    SELECT u.*,
           COUNT(DISTINCT c.id) as comments_count,
           COUNT(DISTINCT f.comic_id) as follows_count
    FROM users u
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN follows f ON u.id = f.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
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
                    <h1>Quản lý người dùng</h1>
                </div>

                <!-- Form tìm kiếm và lọc -->
                <form class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Tìm kiếm người dùng..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Trạng thái</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="banned" <?php echo $status_filter == 'banned' ? 'selected' : ''; ?>>Đã khóa</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-select">
                            <option value="">Vai trò</option>
                            <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </form>

                <!-- Danh sách người dùng -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Avatar</th>
                                <th>Tên người dùng</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Bình luận</th>
                                <th>Theo dõi</th>
                                <th>Trạng thái</th>
                                <th>Ngày tham gia</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <img src="../uploads/avatars/<?php echo $user['avatar'] ?: 'default.jpg'; ?>"
                                            alt="Avatar" class="rounded-circle" width="40" height="40">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                            <?php echo $user['role'] == 'admin' ? 'Admin' : 'Người dùng'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['comments_count']; ?></td>
                                    <td><?php echo $user['follows_count']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo $user['status'] == 'active' ? 'Hoạt động' : 'Đã khóa'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <?php if ($user['role'] != 'admin'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $user['status'] == 'active' ? 'warning' : 'success'; ?>">
                                                    <i class="fas fa-<?php echo $user['status'] == 'active' ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                                            data-bs-target="#userStatsModal" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&role=<?php echo urlencode($role_filter); ?>">
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

    <!-- Modal thống kê chi tiết -->
    <div class="modal fade" id="userStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thống kê chi tiết người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="userStats">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>