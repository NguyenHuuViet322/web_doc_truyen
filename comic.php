<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Get comic ID from URL
$comic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($comic_id <= 0) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get comic details
$stmt = $conn->prepare("
    SELECT c.*, COUNT(ch.id) AS chapter_count
    FROM comics c
    LEFT JOIN chapters ch ON c.id = ch.comic_id
    WHERE c.id = :comic_id
    GROUP BY c.id
");
$stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
$stmt->execute();
$comic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comic) {
    header('Location: index.php');
    exit();
}

// Get comic genres
$stmt = $conn->prepare("
    SELECT g.name 
    FROM comic_genres cg
    JOIN genres g ON cg.genre_id = g.id
    WHERE cg.comic_id = :comic_id
");
$stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
$stmt->execute();
$genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get chapters
$stmt = $conn->prepare("
    SELECT ch.*, 
           (SELECT COUNT(*) FROM chapter_images ci WHERE ci.chapter_id = ch.id) AS image_count
    FROM chapters ch
    WHERE ch.comic_id = :comic_id
    ORDER BY ch.chapter_number DESC
");
$stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
$stmt->execute();
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Track this view in reading history if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if we already have a history entry
        $check_stmt = $conn->prepare("
            SELECT id FROM read_history 
            WHERE user_id = :user_id AND comic_id = :comic_id
        ");
        $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing record
            $update_stmt = $conn->prepare("
                UPDATE read_history 
                SET created_at = NOW() 
                WHERE user_id = :user_id AND comic_id = :comic_id
            ");
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            // Insert new record
            $insert_stmt = $conn->prepare("
                INSERT INTO read_history (user_id, comic_id)
                VALUES (:user_id, :comic_id)
            ");
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
            $insert_stmt->execute();
        }
    } catch (PDOException $e) {
        // Log error but don't interrupt user experience
        error_log("Error tracking reading history: " . $e->getMessage());
    }
}

// Check if user is following this comic
$is_following = false;
if (isset($_SESSION['user_id'])) {
    $check_follow = $conn->prepare("
        SELECT id FROM follows
        WHERE user_id = :user_id AND comic_id = :comic_id
    ");
    $check_follow->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $check_follow->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
    $check_follow->execute();
    $is_following = ($check_follow->rowCount() > 0);
}

// Increment view count
$conn->prepare("UPDATE comics SET views = views + 1 WHERE id = :id")->execute(['id' => $comic_id]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($comic['title']); ?> - Website Truyện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .comic-cover {
            max-height: 400px;
            object-fit: contain;
        }
        .chapter-item {
            transition: all 0.2s;
        }
        .chapter-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .genre-badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        /* Fix card size with scrollable content */
        .description-card {
            width: 600px; /* Changed to exactly 600px */
            height: 400px; /* Keep height at 350px */
            max-width: 100%;
            margin: 0 auto;
        }
        .description-card .card-body {
            height: 350px; /* Keep height at 350px */
            overflow-y: auto;
            /* Custom scrollbar styling */
            scrollbar-width: thin;
        }
        .description-card .card-body::-webkit-scrollbar {
            width: 6px;
        }
        .description-card .card-body::-webkit-scrollbar-thumb {
            background-color: #adb5bd;
            border-radius: 10px;
        }
        /* Add styles to fix spacing issues */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
            margin-bottom: 3rem; /* Add space before footer */
        }
        .content-section {
            margin-bottom: 2rem; /* Space between sections */
        }
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main>
        <div class="container mt-5 pt-3">
            <!-- Comic Details -->
            <div class="row mb-5 content-section">
                <div class="col-md-4 text-center">
                    <img src="uploads/comics/<?php echo htmlspecialchars($comic['cover_image']); ?>" 
                         alt="<?php echo htmlspecialchars($comic['title']); ?>"
                         class="img-fluid rounded shadow comic-cover mb-3">
                         
                    <div class="d-grid gap-2">
                        <?php if (count($chapters) > 0): ?>
                        <a href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $chapters[0]['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-book-open me-2"></i>Đọc mới nhất
                        </a>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($is_following): ?>
                            <form action="actions/unfollow.php" method="post">
                                <input type="hidden" name="comic_id" value="<?php echo $comic_id; ?>">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-heart-broken me-2"></i>Bỏ theo dõi
                                </button>
                            </form>
                            <?php else: ?>
                            <form action="actions/follow.php" method="post">
                                <input type="hidden" name="comic_id" value="<?php echo $comic_id; ?>">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-heart me-2"></i>Theo dõi
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <h1 class="mb-3"><?php echo htmlspecialchars($comic['title']); ?></h1>
                    
                    <div class="mb-3">
                        <?php foreach ($genres as $genre): ?>
                        <span class="badge bg-secondary genre-badge"><?php echo htmlspecialchars($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th width="120">Tác giả</th>
                            <td><?php echo htmlspecialchars($comic['author'] ?? 'Không rõ'); ?></td>
                        </tr>
                        <tr>
                            <th>Trạng thái</th>
                            <td>
                                <?php if ($comic['status'] == 'ongoing'): ?>
                                    <span class="badge bg-primary">Đang tiến hành</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Hoàn thành</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Số chapter</th>
                            <td><?php echo $comic['chapter_count']; ?></td>
                        </tr>
                        <tr>
                            <th>Lượt xem</th>
                            <td><?php echo number_format($comic['views']); ?></td>
                        </tr>
                        <tr>
                            <th>Cập nhật</th>
                            <td><?php echo formatDate($comic['updated_at']); ?></td>
                        </tr>
                    </table>
                    
                    <div class="card mt-3 description-card">
                        <div class="card-header">
                            <h5 class="mb-0">Nội dung</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($comic['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chapter List -->
            <div class="card mb-5 content-section">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Danh sách chapter</h4>
                    <span class="badge bg-light text-primary"><?php echo count($chapters); ?> chapter</span>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($chapters as $chapter): ?>
                        <a href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $chapter['id']; ?>" 
                           class="list-group-item list-group-item-action chapter-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold">Chapter <?php echo $chapter['chapter_number']; ?></span>
                                <?php if (!empty($chapter['title'])): ?>
                                    - <?php echo htmlspecialchars($chapter['title']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small">
                                <i class="far fa-calendar-alt me-1"></i><?php echo formatDate($chapter['created_at']); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Comment Section -->
            <div class="card mb-5 content-section">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Bình luận</h4>
                </div>
                <div class="card-body">
                    <?php
                    // Get comments for this comic
                    $comments_stmt = $conn->prepare("
                        SELECT c.*, u.username, u.avatar
                        FROM comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.comic_id = :comic_id AND c.chapter_id IS NULL AND c.status = 'active'
                        ORDER BY c.created_at DESC
                    ");
                    $comments_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
                    $comments_stmt->execute();
                    $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <!-- Comment Form -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="actions/add-comment.php" method="post" class="mb-4">
                            <input type="hidden" name="comic_id" value="<?php echo $comic_id; ?>">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Viết bình luận của bạn:</label>
                                <textarea class="form-control" id="comment" name="content" rows="3" required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>Gửi bình luận
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <a href="login.php?redirect=<?php echo urlencode('comic.php?id=' . $comic_id); ?>" class="alert-link">Đăng nhập</a> để viết bình luận.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Comments List -->
                    <h5 class="mb-3"><?php echo count($comments); ?> bình luận</h5>
                    
                    <?php if (count($comments) > 0): ?>
                        <div class="comments-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <img src="uploads/avatars/<?php echo htmlspecialchars($comment['avatar']); ?>" 
                                                 class="rounded-circle me-3" width="40" height="40" 
                                                 alt="<?php echo htmlspecialchars($comment['username']); ?>">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($comment['username']); ?></h6>
                                                    <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                                
                                                <?php if (isset($_SESSION['user_id']) && 
                                                       ($_SESSION['user_id'] == $comment['user_id'] || 
                                                        ($_SESSION['role'] ?? '') == 'admin')): ?>
                                                    <div class="text-end">
                                                        <a href="actions/delete-comment.php?id=<?php echo $comment['id']; ?>&comic=<?php echo $comic_id; ?>" 
                                                           class="text-danger small" 
                                                           onclick="return confirm('Bạn có chắc muốn xóa bình luận này?');">
                                                            <i class="fas fa-trash-alt me-1"></i>Xóa
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-comment-slash fa-3x mb-3"></i>
                            <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>