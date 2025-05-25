<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Check if a specific category is selected
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Pagination settings
$items_per_page = 20;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get all categories/genres for the sidebar
$all_genres_raw = $conn->query("SELECT * FROM genres ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Filter out duplicates by name
$seen_names = [];
$all_genres = [];
foreach ($all_genres_raw as $genre) {
    if (!isset($seen_names[strtolower($genre['name'])])) {
        $seen_names[strtolower($genre['name'])] = true;
        $all_genres[] = $genre;
    }
}

// If a specific category is selected
if ($category_id > 0 || !empty($category_slug)) {
    // Get category info
    if ($category_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM genres WHERE id = ?");
        $stmt->execute([$category_id]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM genres WHERE slug = ?");
        $stmt->execute([$category_slug]);
    }
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        header('Location: categories.php');
        exit();
    }
    
    // Count comics in this category for pagination
    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT c.id) as total
        FROM comics c
        JOIN comic_genres cg ON c.id = cg.comic_id
        WHERE cg.genre_id = ?
    ");
    $count_stmt->execute([$category['id']]);
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / $items_per_page);
    
    // Get comics in this category
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM chapters ch WHERE ch.comic_id = c.id) as chapter_count
        FROM comics c
        JOIN comic_genres cg ON c.id = cg.comic_id
        WHERE cg.genre_id = ?
        GROUP BY c.id
        ORDER BY c.updated_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindParam(1, $category['id'], PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->bindParam(3, $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($category) ? htmlspecialchars($category['name']) : 'Thể Loại'; ?> - Website Truyện</title>
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
        .genre-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .genre-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .comic-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .comic-card:hover {
            transform: translateY(-5px);
        }
        .comic-cover {
            height: 220px;
            object-fit: cover;
        }
        .genre-icon {
            font-size: 2rem;
            margin-bottom: 15px;
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
            <?php if (isset($category)): ?>
                <!-- Specific Category View -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="categories.php">Thể loại</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">
                        <i class="fas fa-tags me-2 text-primary"></i>
                        Thể loại: <?php echo htmlspecialchars($category['name']); ?>
                    </h1>
                </div>
                
                <?php if (!empty($category['description'])): ?>
                    <div class="alert alert-info mb-4">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Comics List -->
                    <div class="col-md-9">
                        <?php if (count($comics) > 0): ?>
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-4">
                                <?php foreach ($comics as $comic): ?>
                                    <div class="col">
                                        <div class="card comic-card h-100">
                                            <img src="uploads/comics/<?php echo htmlspecialchars($comic['cover_image']); ?>" 
                                                 class="card-img-top comic-cover" 
                                                 alt="<?php echo htmlspecialchars($comic['title']); ?>">
                                            

                                            <div class="card-body">
                                                <h5 class="card-title text-truncate">
                                                    <?php echo htmlspecialchars($comic['title']); ?>
                                                </h5>
                                                
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="badge bg-primary"><?php echo $comic['chapter_count']; ?> chapter</span>
                                                    <span class="badge <?php echo ($comic['status'] == 'ongoing') ? 'bg-info' : 'bg-success'; ?>">
                                                        <?php echo ($comic['status'] == 'ongoing') ? 'Đang tiến hành' : 'Hoàn thành'; ?>
                                                    </span>
                                                </div>
                                                
                                                <p class="card-text small text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($comic['author'] ?? 'Không rõ'); ?>
                                                </p>
                                            </div>
                                            
                                            <div class="card-footer text-center bg-white">
                                                <a href="comic.php?id=<?php echo $comic['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-info-circle me-1"></i>Chi tiết
                                                </a>
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
                                            <a class="page-link" href="?id=<?php echo $category['id']; ?>&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $category['id']; ?>&page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $category['id']; ?>&page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Không có truyện nào thuộc thể loại này.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Categories Sidebar -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Tất cả thể loại</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($all_genres as $genre): ?>
                                        <li class="list-group-item <?php echo (isset($category) && $category['id'] == $genre['id']) ? 'active' : ''; ?>">
                                            <a href="categories.php?id=<?php echo $genre['id']; ?>" class="text-decoration-none <?php echo (isset($category) && $category['id'] == $genre['id']) ? 'text-white' : 'text-body'; ?>">
                                                <?php echo htmlspecialchars($genre['name']); ?>
                                                <span class="float-end">
                                                    <i class="fas fa-chevron-right"></i>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- All Categories View -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">
                        <i class="fas fa-tags me-2 text-primary"></i>
                        Tất cả thể loại truyện
                    </h1>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách thể loại</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            // Split genres into two columns for better display
                            $genres_count = count($all_genres);
                            $half_count = ceil($genres_count / 2);
                            $first_half = array_slice($all_genres, 0, $half_count);
                            $second_half = array_slice($all_genres, $half_count);
                            ?>
                            
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($first_half as $genre): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php 
                                            // Icon based on genre
                                            $icon = 'fa-tag';
                                            if (strpos($genre['slug'], 'hanh-dong') !== false || strpos($genre['slug'], 'action') !== false) {
                                                $icon = 'fa-fire';
                                            } elseif (strpos($genre['slug'], 'phieu-luu') !== false || strpos($genre['slug'], 'adventure') !== false) {
                                                $icon = 'fa-mountain';
                                            } elseif (strpos($genre['slug'], 'hai-huoc') !== false || strpos($genre['slug'], 'comedy') !== false) {
                                                $icon = 'fa-laugh';
                                            } elseif (strpos($genre['slug'], 'tinh-cam') !== false || strpos($genre['slug'], 'romance') !== false) {
                                                $icon = 'fa-heart';
                                            } elseif (strpos($genre['slug'], 'kinh-di') !== false || strpos($genre['slug'], 'horror') !== false) {
                                                $icon = 'fa-ghost';
                                            } elseif (strpos($genre['slug'], 'vien-tuong') !== false || strpos($genre['slug'], 'sci-fi') !== false) {
                                                $icon = 'fa-rocket';
                                            }
                                            ?>
                                            <a href="categories.php?id=<?php echo $genre['id']; ?>" class="text-decoration-none text-dark">
                                                <i class="fas <?php echo $icon; ?> me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($genre['name']); ?>
                                            </a>
                                            <span class="badge bg-primary rounded-pill">
                                                <?php 
                                                // Count comics in this category
                                                $count_stmt = $conn->prepare("
                                                    SELECT COUNT(DISTINCT c.id) as count
                                                    FROM comics c
                                                    JOIN comic_genres cg ON c.id = cg.comic_id
                                                    WHERE cg.genre_id = ?
                                                ");
                                                $count_stmt->execute([$genre['id']]);
                                                echo $count_stmt->fetch()['count']; 
                                                ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($second_half as $genre): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php 
                                            // Icon based on genre
                                            $icon = 'fa-tag';
                                            if (strpos($genre['slug'], 'hanh-dong') !== false || strpos($genre['slug'], 'action') !== false) {
                                                $icon = 'fa-fire';
                                            } elseif (strpos($genre['slug'], 'phieu-luu') !== false || strpos($genre['slug'], 'adventure') !== false) {
                                                $icon = 'fa-mountain';
                                            } elseif (strpos($genre['slug'], 'hai-huoc') !== false || strpos($genre['slug'], 'comedy') !== false) {
                                                $icon = 'fa-laugh';
                                            } elseif (strpos($genre['slug'], 'tinh-cam') !== false || strpos($genre['slug'], 'romance') !== false) {
                                                $icon = 'fa-heart';
                                            } elseif (strpos($genre['slug'], 'kinh-di') !== false || strpos($genre['slug'], 'horror') !== false) {
                                                $icon = 'fa-ghost';
                                            } elseif (strpos($genre['slug'], 'vien-tuong') !== false || strpos($genre['slug'], 'sci-fi') !== false) {
                                                $icon = 'fa-rocket';
                                            }
                                            ?>
                                            <a href="categories.php?id=<?php echo $genre['id']; ?>" class="text-decoration-none text-dark">
                                                <i class="fas <?php echo $icon; ?> me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($genre['name']); ?>
                                            </a>
                                            <span class="badge bg-primary rounded-pill">
                                                <?php 
                                                // Count comics in this category
                                                $count_stmt = $conn->prepare("
                                                    SELECT COUNT(DISTINCT c.id) as count
                                                    FROM comics c
                                                    JOIN comic_genres cg ON c.id = cg.comic_id
                                                    WHERE cg.genre_id = ?
                                                ");
                                                $count_stmt->execute([$genre['id']]);
                                                echo $count_stmt->fetch()['count']; 
                                                ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
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