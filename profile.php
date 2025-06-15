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

// Ensure avatar directory exists
$avatar_dir = 'uploads/avatars/';
if (!is_dir($avatar_dir)) {
    mkdir($avatar_dir, 0777, true);
    error_log("Created avatar directory: $avatar_dir");
}

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);



// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $password_changed = false;
    $error_message = null;

    // Chỉ kiểm tra mật khẩu khi người dùng muốn thay đổi mật khẩu
    if (!empty($new_password)) {
        // Kiểm tra mật khẩu hiện tại trước khi cập nhật mật khẩu mới
        if (!empty($current_password) && password_verify($current_password, $user['password'])) {
            // Cập nhật mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $password_changed = true;
        } else {
            $error_message = "Mật khẩu hiện tại không đúng!";
        }
    }
    
    // Nếu không thay đổi mật khẩu hoặc đã xác nhận mật khẩu thành công
    if (empty($new_password) || $password_changed) {        // Cập nhật email
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $user_id]);
          // Xử lý upload avatar
        if (!empty($_FILES['avatar']['name'])) {
            $avatar_dir = 'uploads/avatars/';
            
            // Make sure avatar directory exists
            if (!is_dir($avatar_dir)) {
                mkdir($avatar_dir, 0777, true);
            }
            
            // Upload new avatar
            $avatar = uploadFile($_FILES['avatar'], $avatar_dir);
            
            if ($avatar) {
                // If upload successful, delete old avatar if it exists and isn't the default
                if (isset($user['avatar']) && !empty($user['avatar']) && $user['avatar'] != 'default.jpg') {
                    $old_avatar_path = $avatar_dir . $user['avatar'];
                    if (file_exists($old_avatar_path)) {
                        // Use @ to suppress warnings if file doesn't exist
                        @unlink($old_avatar_path);
                        error_log("Deleted old avatar: " . $old_avatar_path);
                    }
                }
                
                // Update database with new avatar filename
                $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$avatar, $user_id]);
                
                // Update session avatar if being used
                if (isset($_SESSION['avatar'])) {
                    $_SESSION['avatar'] = $avatar;
                }
                
                error_log("Updated user avatar: User ID $user_id, New avatar: $avatar");
            } else {
                // Log debug information if upload fails
                error_log("Avatar upload failed for user ID: $user_id");
                error_log(print_r(debugFileUpload($_FILES['avatar'], $avatar_dir), true));
                $error_message = "Không thể tải lên ảnh đại diện. Vui lòng thử lại với tệp hợp lệ.";
            }
        }

        if (!$error_message) {
            $success_message = "Cập nhật thông tin thành công!";
        }
        
        // Refresh thông tin người dùng
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
            <div class="col-md-4">                <div class="card">
                    <div class="card-body text-center">                        <?php 
                        // Make sure the avatar directory exists
                        $avatar_dir = 'uploads/avatars/';
                        if (!is_dir($avatar_dir)) {
                            mkdir($avatar_dir, 0777, true);
                            error_log("Created avatar directory: $avatar_dir");
                        }
                        
                        // Set default avatar path
                        $avatar_path = 'default.jpg';
                        
                        // Try to get avatar from user data first (most reliable)
                        if (isset($user['avatar']) && !empty($user['avatar'])) {
                            $avatar_path = htmlspecialchars($user['avatar']);
                            error_log("PROFILE: Using avatar from user data: " . $avatar_path);
                        }
                        // Fallback to session if database retrieval failed
                        else if (isset($_SESSION['avatar']) && !empty($_SESSION['avatar'])) {
                            $avatar_path = htmlspecialchars($_SESSION['avatar']);
                            error_log("PROFILE: Using avatar from session: " . $avatar_path);
                        } else {
                            error_log("PROFILE: No avatar found in user data or session, using default");
                        }
                        
                        // Make sure the default avatar exists
                        if ($avatar_path === 'default.jpg' && !file_exists($avatar_dir . 'default.jpg')) {
                            // Create an empty file to prevent errors
                            // In a real scenario, you'd copy a default avatar image
                            file_put_contents($avatar_dir . 'default.jpg', '');
                            error_log("PROFILE: Created empty default.jpg avatar file");
                        }
                        
                        $username = isset($user['username']) ? htmlspecialchars($user['username']) : 'User';
                        ?>
                        <img src="uploads/avatars/<?php echo $avatar_path; ?>" class="profile-avatar mb-3" 
                             alt="<?php echo $username; ?>'s avatar">
                        <h4><?php echo $username; ?></h4>
                        <p class="text-muted">
                            Tham gia: 
                            <?php 
                            // Explicitly verify we have a date from the user table
                            if (isset($user['created_at']) && !empty($user['created_at'])) {
                                echo formatDate($user['created_at']);
                            } else {
                                // If you need to debug, uncomment this line
                                // error_log('Missing created_at date for user ID: ' . $user_id);
                                echo "Không có thông tin";
                            }
                            ?>
                        </p>

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
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label>Avatar mới</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>                        <hr>
                        <h6>Thay đổi mật khẩu (tùy chọn)</h6>
                        <div class="mb-3">
                            <label>Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control" id="currentPassword">
                            <small class="text-muted">Chỉ cần nhập nếu bạn muốn đổi mật khẩu</small>
                        </div>
                        <div class="mb-3">
                            <label>Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control" id="newPassword">
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </form>
                </div>
            </div>
        </div>
    </div>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script to enforce password validation rules
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordField = document.getElementById('newPassword');
            const currentPasswordField = document.getElementById('currentPassword');
            const form = document.querySelector('#editProfileModal form');
            
            // Add validation before form submission
            form.addEventListener('submit', function(e) {
                // If new password is provided, current password is required
                if (newPasswordField.value.trim() !== '' && currentPasswordField.value.trim() === '') {
                    e.preventDefault();
                    alert('Vui lòng nhập mật khẩu hiện tại để xác nhận thay đổi mật khẩu.');
                    currentPasswordField.focus();
                }
            });
            
            // Make current password required when new password is entered
            newPasswordField.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    currentPasswordField.setAttribute('required', 'required');
                } else {
                    currentPasswordField.removeAttribute('required');
                }
            });
        });
    </script>
</body>

</html>