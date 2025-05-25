<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Pagination settings
$items_per_page = 10; // Display 10 comics per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count for pagination
$count_stmt = $conn->query("SELECT COUNT(*) as total FROM comics");
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get latest comics
$stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM chapters ch WHERE ch.comic_id = c.id) as chapter_count,
           (SELECT MAX(ch.created_at) FROM chapters ch WHERE ch.comic_id = c.id) as latest_update
    FROM comics c
    ORDER BY c.updated_at DESC, c.id DESC
    LIMIT :offset, :items_per_page
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
$stmt->execute();
$latest_comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truyện mới cập nhật - Website Truyện</title>
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
        .comic-item {
            transition: all 0.2s;
        }
        .comic-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .comic-cover {
            height: 200px;
            object-fit: cover;
        }
        footer {
            margin-top: auto;
        }
        .badge-corner {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .comic-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .last-updated {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main>
        <div class="container mt-5 pt-3">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-book-open me-2 text-primary"></i>Truyện mới cập nhật</h1>
            </div>
            
            <?php if (count($latest_comics) > 0): ?>
                <div class="row g-4 mb-4">
                    <?php foreach ($latest_comics as $comic): ?>
                        <div class="col-md-6">
                            <div class="card comic-item">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="uploads/comics/<?php echo htmlspecialchars($comic['cover_image']); ?>" 
                                             class="img-fluid rounded-start comic-cover" 
                                             alt="<?php echo htmlspecialchars($comic['title']); ?>">
                                        
                                        <div class="badge-corner">
                                            <span class="badge <?php echo ($comic['status'] == 'ongoing') ? 'bg-primary' : 'bg-success'; ?>">
                                                <?php echo ($comic['status'] == 'ongoing') ? 'Đang tiến hành' : 'Hoàn thành'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($comic['title']); ?></h5>
                                            
                                            <p class="card-text comic-meta">
                                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($comic['author'] ?? 'Không rõ'); ?>
                                            </p>
                                            
                                            <p class="card-text">
                                                <span class="badge bg-info me-2">
                                                    <i class="fas fa-book me-1"></i><?php echo $comic['chapter_count']; ?> chapter
                                                </span>
                                                
                                                <?php 
                                                // Get comic genres
                                                $genre_stmt = $conn->prepare("
                                                    SELECT g.name 
                                                    FROM comic_genres cg
                                                    JOIN genres g ON cg.genre_id = g.id
                                                    WHERE cg.comic_id = ?
                                                    LIMIT 3
                                                ");
                                                $genre_stmt->execute([$comic['id']]);
                                                $genres = $genre_stmt->fetchAll(PDO::FETCH_COLUMN);
                                                
                                                foreach ($genres as $genre): ?>
                                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($genre); ?></span>
                                                <?php endforeach; ?>
                                            </p>
                                            
                                            <p class="card-text last-updated">
                                                <i class="fas fa-clock me-1"></i> Cập nhật: 
                                                <?php echo formatDate($comic['updated_at']); ?>
                                            </p>
                                            
                                            <div class="mt-2">
                                                <a href="comic.php?id=<?php echo $comic['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-info-circle me-1"></i>Chi tiết
                                                </a>
                                                <a href="read.php?comic=<?php echo $comic['id']; ?>&chapter=<?php echo $comic['latest_chapter'] ?? ''; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-book-reader me-1"></i>Đọc ngay
                                                </a>
                                            </div>
                                        </div>
                                    </div>
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
                            
                            <?php 
                            // Show limited page numbers with ellipsis for many pages
                            $start_page = max(1, min($current_page - 2, $total_pages - 4));
                            $end_page = min($total_pages, max($current_page + 2, 5));
                            
                            if ($start_page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Không có truyện nào để hiển thị.
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
