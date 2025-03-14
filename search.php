<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Lấy từ khóa tìm kiếm
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

try {
    // Chuẩn bị câu truy vấn với tham số được bind
    $stmt = $conn->prepare("SELECT * FROM comics WHERE title LIKE ? OR author LIKE ?");
    $searchTerm = "%{$keyword}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Có lỗi xảy ra: ' . $e->getMessage();
    $results = [];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm - <?php echo htmlspecialchars($keyword); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <main>
        <div class="search-container">
            <div class="container">
                <h1 class="mb-4">Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($keyword); ?>"</h1>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (empty($results)): ?>
                    <div class="alert alert-info">Không tìm thấy kết quả nào phù hợp.</div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                        <?php foreach ($results as $comic): ?>
                            <div class="col">
                                <div class="card h-100">
                                    <img src="uploads/comics/<?php echo htmlspecialchars($comic['cover_image']); ?>"
                                        class="card-img-top"
                                        alt="<?php echo htmlspecialchars($comic['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($comic['title']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">Tác giả: <?php echo htmlspecialchars($comic['author']); ?></small>
                                        </p>
                                        <a href="read.php?id=<?php echo $comic['id']; ?>" class="btn btn-primary">Đọc ngay</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>