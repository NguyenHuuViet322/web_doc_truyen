<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=following.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Pagination settings
$items_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count for pagination
$count_stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM follows 
    WHERE user_id = :user_id
");
$count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$count_stmt->execute();
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get followed comics with details
$stmt = $conn->prepare("
    SELECT f.id, f.created_at as followed_at, 
           c.id as comic_id, c.title, c.cover_image, c.description, c.latest_chapter, c.author, c.status,
           (SELECT COUNT(*) FROM chapters ch WHERE ch.comic_id = c.id) as chapter_count
    FROM follows f
    JOIN comics c ON f.comic_id = c.id
    WHERE f.user_id = :user_id
    ORDER BY f.created_at DESC
    LIMIT :offset, :items_per_page
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$follows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truyện đang theo dõi - Website Truyện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
            margin-bottom: 3rem;
        }
        .comic-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .comic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .comic-cover {
            height: 250px;
            object-fit: cover;
        }
        .card-footer {
            background: transparent;
            border-top: none;
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="fas fa-heart me-2 text-danger"></i>Truyện đang theo dõi</h1>
            </div>
            
            <?php if (count($follows) > 0): ?>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-4">
                    <?php foreach ($follows as $follow): ?>
                        <div class="col">
                            <div class="card comic-card h-100">
                                <img src="uploads/comics/<?php echo htmlspecialchars($follow['cover_image']); ?>" 
                                     class="card-img-top comic-cover" 
                                     alt="<?php echo htmlspecialchars($follow['title']); ?>">
                                
                                <div class="card-body">
                                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($follow['title']); ?></h5>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-primary"><?php echo $follow['chapter_count']; ?> chapter</span>
                                        <span class="badge <?php echo ($follow['status'] == 'ongoing') ? 'bg-info' : 'bg-success'; ?>">
                                            <?php echo ($follow['status'] == 'ongoing') ? 'Đang tiến hành' : 'Hoàn thành'; ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text small text-muted mb-2">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($follow['author'] ?? 'Không rõ'); ?>
                                    </p>
                                    
                                    <p class="card-text small text-muted">
                                        <i class="far fa-clock me-1"></i>Theo dõi: <?php echo formatDate($follow['followed_at']); ?>
                                    </p>
                                </div>
                                
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="comic.php?id=<?php echo $follow['comic_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-info-circle me-1"></i>Chi tiết
                                    </a>
                                    
                                    <form action="actions/unfollow.php" method="post">
                                        <input type="hidden" name="comic_id" value="<?php echo $follow['comic_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-heart-broken me-1"></i>Bỏ theo dõi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info text-center p-5">
                    <i class="fas fa-heart-broken fa-3x mb-3"></i>
                    <h4>Bạn chưa theo dõi truyện nào</h4>
                    <p class="mb-0">Khi bạn theo dõi một truyện, nó sẽ xuất hiện ở đây để bạn dễ dàng theo dõi cập nhật.</p>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Khám phá truyện ngay
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
