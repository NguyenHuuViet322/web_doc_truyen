<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Kiểm tra mật khẩu hiện tại
    if (!empty($current_password) && password_verify($current_password, $user['password'])) {
        if (!empty($new_password)) {
            // Cập nhật mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
        }

        // Cập nhật email
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $user_id]);

        // Xử lý upload avatar
        if (!empty($_FILES['avatar']['name'])) {
            $avatar = uploadFile($_FILES['avatar'], 'uploads/avatars/');
            if ($avatar) {
                // Xóa avatar cũ
                if ($user['avatar'] != 'default.jpg') {
                    @unlink('uploads/avatars/' . $user['avatar']);
                }
                $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$avatar, $user_id]);
            }
        }

        $success_message = "Cập nhật thông tin thành công!";
        // Refresh thông tin người dùng
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error_message = "Mật khẩu hiện tại không đúng!";
    }
}

// Lấy danh sách truyện đang theo dõi
$stmt = $conn->prepare("
    SELECT c.*, f.created_at as followed_at 
    FROM follows f 
    JOIN comics c ON f.comic_id = c.id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$following_comics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy lịch sử bình luận
$stmt = $conn->prepare("
    SELECT c.*, co.title as comic_title 
    FROM comments c 
    JOIN comics co ON c.comic_id = co.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân - <?php echo $user['username']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }

        .comic-card {
            transition: transform 0.2s;
        }

        .comic-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Thông tin cá nhân -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="uploads/avatars/<?php echo $user['avatar']; ?>" class="profile-avatar mb-3">
                        <h4><?php echo $user['username']; ?></h4>
                        <p class="text-muted">Tham gia: <?php echo formatDate($user['created_at']); ?></p>

                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Truyện đang theo dõi -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Truyện đang theo dõi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($following_comics as $comic): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="comic-card">
                                        <a href="read.php?id=<?php echo $comic['id']; ?>">
                                            <img src="uploads/comics/<?php echo $comic['cover_image']; ?>"
                                                class="img-fluid rounded"
                                                alt="<?php echo $comic['title']; ?>">
                                            <div class="mt-2">
                                                <h6 class="mb-0"><?php echo $comic['title']; ?></h6>
                                                <small class="text-muted">Chapter <?php echo $comic['latest_chapter']; ?></small>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Bình luận gần đây -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Bình luận gần đây</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_comments as $comment): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <h6><?php echo $comment['comic_title']; ?></h6>
                                    <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa thông tin -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa thông tin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Avatar mới</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label>Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Mật khẩu mới (để trống nếu không đổi)</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>