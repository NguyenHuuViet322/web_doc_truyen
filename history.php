<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
    FROM read_history 
    WHERE user_id = :user_id
");
$count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$count_stmt->execute();
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get reading history with comic details
$stmt = $conn->prepare("
    SELECT rh.id, rh.comic_id, rh.created_at as read_date,
           c.title as comic_title, c.cover_image, c.latest_chapter
    FROM read_history rh
    JOIN comics c ON rh.comic_id = c.id
    WHERE rh.user_id = :user_id
    ORDER BY rh.created_at DESC
    LIMIT :offset, :items_per_page
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$history_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đọc truyện - Website Truyện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .history-item {
            transition: transform 0.2s;
        }
        .history-item:hover {
            transform: translateY(-5px);
        }
        .history-cover {
            height: 200px;
            object-fit: cover;
        }
        .clear-history {
            cursor: pointer;
        }
        .read-progress {
            height: 5px;
        }
        /* Add padding to body if the navbar is fixed */
        body {
            padding-top: 20px;
        }
        /* Add specific class to prevent overlap */
        .navbar-spacer {
            margin-bottom: 20px;
        }
        /* Position delete button */
        .delete-history-item {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .delete-history-item:hover {
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Add spacer div to prevent navbar overlap -->
    <div class="navbar-spacer" style="height: 20px;"></div>
    
    <div class="container mt-5">  <!-- Changed from mt-4 to mt-5 for more space -->
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['message']; 
                // Clear the message after displaying
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử đọc truyện</h1>
            
            <?php if (count($history_items) > 0): ?>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                <i class="fas fa-trash me-1"></i>Xóa tất cả
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (count($history_items) > 0): ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-4">
                <?php foreach ($history_items as $item): ?>
                    <div class="col">
                        <div class="card h-100 history-item position-relative">
                            <!-- Add delete button for individual history item -->
                            <a href="#" class="delete-history-item" 
                               onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['comic_title']); ?>')">
                                <i class="fas fa-times"></i>
                            </a>
                            
                            <img src="uploads/comics/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                 class="card-img-top history-cover" 
                                 alt="<?php echo htmlspecialchars($item['comic_title']); ?>">
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['comic_title']); ?></h5>
                                
                                <?php if (isset($item['latest_chapter'])): ?>
                                <p class="card-text">
                                    Chapter mới nhất: <?php echo htmlspecialchars($item['latest_chapter']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        Đọc lúc: <?php echo formatDate($item['read_date']); ?>
                                    </small>
                                </p>
                            </div>
                            
                            <div class="card-footer d-flex justify-content-between bg-transparent">
                                <a href="comic.php?id=<?php echo $item['comic_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-info-circle me-1"></i>Chi tiết
                                </a>
                                
                                <a href="comic.php?id=<?php echo $item['comic_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-book-reader me-1"></i>Đọc ngay
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
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h4>Bạn chưa đọc truyện nào</h4>
                <p class="mb-0">Hãy khám phá và đọc truyện để lưu lại lịch sử đọc truyện của bạn.</p>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Khám phá truyện ngay
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Clear History Modal -->
    <div class="modal fade" id="clearHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xóa lịch sử đọc truyện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa tất cả lịch sử đọc truyện? Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <a href="actions/clear-history.php" class="btn btn-danger">Xóa lịch sử</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(historyId, comicTitle) {
            if (confirm(`Bạn có chắc muốn xóa "${comicTitle}" khỏi lịch sử đọc?`)) {
                window.location.href = `actions/delete-history-item.php?id=${historyId}`;
            }
        }
    </script>
</body>
</html>
