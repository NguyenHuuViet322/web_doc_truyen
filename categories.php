<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/constants.php';

$db = new Database();
$conn = $db->getConnection();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Lấy danh sách truyện theo thể loại
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM chapters WHERE comic_id = c.id) as chapter_count,
               (SELECT COUNT(*) FROM follows WHERE comic_id = c.id) as follow_count
        FROM comics c 
        WHERE FIND_IN_SET(?, categories) > 0";
$params = [$type];

// Đếm tổng số truyện
$count_sql = "SELECT COUNT(*) FROM comics WHERE FIND_IN_SET(?, categories) > 0";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_comics = $stmt->fetchColumn();
$total_pages = ceil($total_comics / $limit);

// Thêm LIMIT và OFFSET cho truy vấn chính
$sql .= " ORDER BY updated_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$comics = $stmt->fetchAll();

// Mảng tên thể loại
$category_names = [
    'action' => 'Hành động',
    'adventure' => 'Phiêu lưu',
    'comedy' => 'Hài hước',
    'drama' => 'Drama',
    'fantasy' => 'Fantasy',
    'romance' => 'Tình cảm'
];

// Sau khi lấy dữ liệu
foreach ($comics as $comic) {
    echo "Comic ID: {$comic['id']}, Title: {$comic['title']}, Categories: {$comic['categories']}<br>";
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_names[$type] ?? 'Thể loại'; ?> - Website Truyện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5 pt-4">
        <h2 class="mb-4">
            <i class="fas fa-tag me-2"></i>
            Thể loại: <?php echo $category_names[$type] ?? 'Không xác định'; ?>
        </h2>

        <div class="row">
            <?php foreach ($comics as $comic): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100">
                        <a href="read.php?id=<?php echo $comic['id']; ?>">
                            <img src="uploads/comics/<?php echo $comic['cover_image']; ?>"
                                class="card-img-top"
                                alt="<?php echo htmlspecialchars($comic['title']); ?>">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="read.php?id=<?php echo $comic['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($comic['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-book-open me-1"></i>
                                    Chapter <?php echo $comic['chapter_count']; ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-heart me-1"></i>
                                    <?php echo number_format($comic['follow_count']); ?> lượt theo dõi
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?type=<?php echo $type; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>