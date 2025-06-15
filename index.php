<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/constants.php';

// Ensure upload directories exist
$directories = [
    'uploads/',
    'uploads/avatars/',
    'uploads/comics/',
    'uploads/chapters/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            error_log("Created directory: $dir");
        } else {
            error_log("Failed to create directory: $dir");
        }
    }
}

// Create default avatar if it doesn't exist
$default_avatar = 'uploads/avatars/default.jpg';
if (!file_exists($default_avatar)) {
    // Copy from existing default avatar or create a blank one
    if (file_exists('assets/images/default_avatar.jpg')) {
        copy('assets/images/default_avatar.jpg', $default_avatar);
    } else {
        // Create a simple default avatar
        $img = imagecreatetruecolor(200, 200);
        $bg_color = imagecolorallocate($img, 238, 238, 238);
        $text_color = imagecolorallocate($img, 100, 100, 100);
        
        imagefill($img, 0, 0, $bg_color);
        imagestring($img, 5, 70, 90, 'Avatar', $text_color);
        
        imagejpeg($img, $default_avatar);
        imagedestroy($img);
    }
    error_log("Created default avatar: $default_avatar");
}

$db = new Database();
$conn = $db->getConnection();

// Lấy danh sách truyện mới cập nhật kèm số chapter
$stmt = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM chapters WHERE comic_id = c.id) as chapter_count,
           (SELECT COUNT(*) FROM follows WHERE comic_id = c.id) as follow_count
    FROM comics c 
    ORDER BY c.updated_at DESC 
    LIMIT 12
");
$latest_comics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Truyện Tranh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <main>
        <div class="container">
            <h2 class="page-title">
                <i class="fas fa-book me-2"></i>
                Truyện Mới Cập Nhật
            </h2>

            <div class="row">
                <?php foreach ($latest_comics as $comic): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <a href="read.php?id=<?php echo $comic['id']; ?>">
                                <img src="uploads/comics/<?php echo $comic['cover_image']; ?>"
                                    class="card-img-top"
                                    alt="<?php echo htmlspecialchars($comic['title']); ?>">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="read.php?id=<?php echo $comic['id']; ?>" class="text-decoration-none text-dark">
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
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>