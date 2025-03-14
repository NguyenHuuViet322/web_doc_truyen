<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Lấy thông tin truyện
if (isset($_GET['id'])) {
    $comic_id = (int)$_GET['id'];

    // Lấy thông tin truyện
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM chapters WHERE comic_id = c.id) as chapter_count,
               (SELECT COUNT(*) FROM follows WHERE comic_id = c.id) as follow_count
        FROM comics c 
        WHERE c.id = ?
    ");
    $stmt->execute([$comic_id]);
    $comic = $stmt->fetch();

    if (!$comic) {
        header('Location: index.php');
        exit;
    }

    // Lấy danh sách chapter
    $stmt = $conn->prepare("
        SELECT * FROM chapters 
        WHERE comic_id = ? 
        ORDER BY chapter_number DESC
    ");
    $stmt->execute([$comic_id]);
    $chapters = $stmt->fetchAll();
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
    <title><?php echo htmlspecialchars($comic['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <main>
        <div class="comic-detail">
            <div class="container">
                <div class="comic-info">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="uploads/comics/<?php echo $comic['cover_image']; ?>"
                                alt="<?php echo htmlspecialchars($comic['title']); ?>"
                                class="img-fluid">
                        </div>
                        <div class="col-md-8">
                            <h1><?php echo htmlspecialchars($comic['title']); ?></h1>
                            <p><strong>Tác giả:</strong> <?php echo htmlspecialchars($comic['author']); ?></p>
                            <p><strong>Trạng thái:</strong>
                                <?php echo $comic['status'] == 'ongoing' ? 'Đang tiến hành' : 'Hoàn thành'; ?>
                            </p>
                            <p><strong>Số chapter:</strong> <?php echo $comic['chapter_count']; ?></p>
                            <p><strong>Lượt theo dõi:</strong> <?php echo number_format($comic['follow_count']); ?></p>
                            <div class="description">
                                <?php echo nl2br(htmlspecialchars($comic['description'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="chapter-list mt-4">
                        <h2>Danh sách chapter</h2>
                        <div class="list-group">
                            <?php foreach ($chapters as $chapter): ?>
                                <a href="chapter.php?id=<?php echo $chapter['id']; ?>"
                                    class="list-group-item list-group-item-action">
                                    Chapter <?php echo $chapter['chapter_number']; ?>
                                    <small class="text-muted float-end">
                                        <?php echo date('d/m/Y', strtotime($chapter['created_at'])); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>