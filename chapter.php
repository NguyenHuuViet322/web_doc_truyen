<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $chapter_id = (int)$_GET['id'];

    // Lấy thông tin chapter và truyện
    $stmt = $conn->prepare("
        SELECT ch.*, c.title as comic_title, c.id as comic_id
        FROM chapters ch
        JOIN comics c ON ch.comic_id = c.id
        WHERE ch.id = ?
    ");
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch();

    if (!$chapter) {
        header('Location: index.php');
        exit;
    }

    // Lấy danh sách ảnh của chapter
    $stmt = $conn->prepare("
        SELECT * FROM chapter_images 
        WHERE chapter_id = ? 
        ORDER BY image_order ASC
    ");
    $stmt->execute([$chapter_id]);
    $images = $stmt->fetchAll();

    // Lấy chapter trước và sau
    $stmt = $conn->prepare("
        SELECT id, chapter_number 
        FROM chapters 
        WHERE comic_id = ? AND chapter_number < ? 
        ORDER BY chapter_number DESC LIMIT 1
    ");
    $stmt->execute([$chapter['comic_id'], $chapter['chapter_number']]);
    $prev_chapter = $stmt->fetch();

    $stmt = $conn->prepare("
        SELECT id, chapter_number 
        FROM chapters 
        WHERE comic_id = ? AND chapter_number > ? 
        ORDER BY chapter_number ASC LIMIT 1
    ");
    $stmt->execute([$chapter['comic_id'], $chapter['chapter_number']]);
    $next_chapter = $stmt->fetch();
} else {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter <?php echo $chapter['chapter_number']; ?> - <?php echo htmlspecialchars($chapter['comic_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="read.php?id=<?php echo $chapter['comic_id']; ?>"><?php echo htmlspecialchars($chapter['comic_title']); ?></a></li>
                <li class="breadcrumb-item active">Chapter <?php echo $chapter['chapter_number']; ?></li>
            </ol>
        </nav>

        <div class="chapter-navigation text-center mb-3">
            <?php if ($prev_chapter): ?>
                <a href="chapter.php?id=<?php echo $prev_chapter['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-chevron-left"></i> Chapter trước
                </a>
            <?php endif; ?>

            <a href="read.php?id=<?php echo $chapter['comic_id']; ?>" class="btn btn-secondary">
                Danh sách chapter
            </a>

            <?php if ($next_chapter): ?>
                <a href="chapter.php?id=<?php echo $next_chapter['id']; ?>" class="btn btn-primary">
                    Chapter sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="chapter-content text-center">
            <?php foreach ($images as $image): ?>
                <img src="uploads/chapters/<?php echo $image['image_path']; ?>"
                    alt="Page <?php echo $image['image_order']; ?>"
                    class="img-fluid mb-3">
            <?php endforeach; ?>
        </div>

        <div class="chapter-navigation text-center mt-3">
            <?php if ($prev_chapter): ?>
                <a href="chapter.php?id=<?php echo $prev_chapter['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-chevron-left"></i> Chapter trước
                </a>
            <?php endif; ?>

            <a href="read.php?id=<?php echo $chapter['comic_id']; ?>" class="btn btn-secondary">
                Danh sách chapter
            </a>

            <?php if ($next_chapter): ?>
                <a href="chapter.php?id=<?php echo $next_chapter['id']; ?>" class="btn btn-primary">
                    Chapter sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>