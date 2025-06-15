<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Initialize database connection at the beginning
$db = new Database();
$conn = $db->getConnection();

// Get parameters
$comic_id = isset($_GET['comic']) ? (int)$_GET['comic'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$chapter_id = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 0;

// If we only have comic_id but no chapter_id, this is the comic details page
if ($comic_id > 0 && $chapter_id == 0) {
    // Redirect to comic.php for comic details
    header('Location: comic.php?id=' . $comic_id);
    exit();
}

// Validate parameters
if ($comic_id <= 0 || $chapter_id <= 0) {
    header('Location: index.php');
    exit();
}

// Get chapter info
$stmt = $conn->prepare("
    SELECT ch.*, c.title as comic_title, c.id as comic_id
    FROM chapters ch
    JOIN comics c ON ch.comic_id = c.id
    WHERE ch.id = :chapter_id AND ch.comic_id = :comic_id
");
$stmt->bindParam(':chapter_id', $chapter_id, PDO::PARAM_INT);
$stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
$stmt->execute();
$chapter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chapter) {
    header('Location: comic.php?id=' . $comic_id);
    exit();
}

// Get chapter images
$stmt = $conn->prepare("
    SELECT * FROM chapter_images
    WHERE chapter_id = :chapter_id
    ORDER BY 
        CASE 
            WHEN page_chapter_image IS NULL OR page_chapter_image = '' THEN 2 
            ELSE 1 
        END,
        page_chapter_image ASC, 
        image_order ASC, 
        id ASC
");
$stmt->bindParam(':chapter_id', $chapter_id, PDO::PARAM_INT);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get previous and next chapter
$prev_chapter = $conn->prepare("
    SELECT id FROM chapters
    WHERE comic_id = :comic_id AND chapter_number < :current_number
    ORDER BY chapter_number DESC
    LIMIT 1
");
$prev_chapter->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
$prev_chapter->bindParam(':current_number', $chapter['chapter_number'], PDO::PARAM_INT);
$prev_chapter->execute();
$prev_id = $prev_chapter->fetchColumn();

$next_chapter = $conn->prepare("
    SELECT id FROM chapters
    WHERE comic_id = :comic_id AND chapter_number > :current_number
    ORDER BY chapter_number ASC
    LIMIT 1
");
$next_chapter->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
$next_chapter->bindParam(':current_number', $chapter['chapter_number'], PDO::PARAM_INT);
$next_chapter->execute();
$next_id = $next_chapter->fetchColumn();

// Track reading in history
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
        error_log("Error tracking reading history: " . $e->getMessage());
    }
}

// Record view
$conn->prepare("
    INSERT INTO views (chapter_id, user_id, ip_address)
    VALUES (:chapter_id, :user_id, :ip_address)
")->execute([
    ':chapter_id' => $chapter_id,
    ':user_id' => $_SESSION['user_id'] ?? null,
    ':ip_address' => $_SERVER['REMOTE_ADDR']
]);
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
    <style>
        .chapter-navigation {
            position: sticky;
            top: 10px;
            z-index: 100;
        }
        .chapter-image {
            max-width: 100%;
            margin-bottom: 10px;
        }
        body {
            background: #f5f5f5;
        }
        .reading-container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .btn-chapter {
            width: 120px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Chapter info and navigation -->        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h3"><?php echo htmlspecialchars($chapter['comic_title']); ?></h1>
                    <div>
                        <button id="togglePageNames" class="btn btn-outline-secondary btn-sm me-2" title="Ẩn/Hiện tên trang">
                            <i class="fas fa-tag"></i> <span id="toggleText">Ẩn tên trang</span>
                        </button>
                        <a href="comic.php?id=<?php echo $chapter['comic_id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                    </div>
                </div>
                <h2 class="h5">Chapter <?php echo $chapter['chapter_number']; ?> 
                    <?php if (!empty($chapter['title'])): ?>
                        - <?php echo htmlspecialchars($chapter['title']); ?>
                    <?php endif; ?>
                </h2>
            </div>
        </div>
        
        <!-- Chapter Navigation -->
        <div class="chapter-navigation d-flex justify-content-between mb-3">
            <div>
                <?php if ($prev_id): ?>
                <a href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $prev_id; ?>" class="btn btn-primary btn-chapter">
                    <i class="fas fa-arrow-left me-1"></i>Trước
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-chapter" disabled>
                    <i class="fas fa-arrow-left me-1"></i>Trước
                </button>
                <?php endif; ?>
            </div>
            
            <div>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Chapter <?php echo $chapter['chapter_number']; ?>
                    </button>
                    <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                        <?php 
                        $chapters_list = $conn->prepare("
                            SELECT id, chapter_number, title 
                            FROM chapters 
                            WHERE comic_id = :comic_id
                            ORDER BY chapter_number DESC
                        ");
                        $chapters_list->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
                        $chapters_list->execute();
                        
                        while ($ch = $chapters_list->fetch()): 
                        ?>
                            <li>
                                <a class="dropdown-item <?php echo ($ch['id'] == $chapter_id) ? 'active' : ''; ?>" 
                                   href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $ch['id']; ?>">
                                    Chapter <?php echo $ch['chapter_number']; ?>
                                    <?php if (!empty($ch['title'])): ?> - <?php echo htmlspecialchars($ch['title']); ?><?php endif; ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            
            <div>
                <?php if ($next_id): ?>
                <a href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $next_id; ?>" class="btn btn-primary btn-chapter">
                    Tiếp<i class="fas fa-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-chapter" disabled>
                    Tiếp<i class="fas fa-arrow-right ms-1"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chapter Content -->
        <div class="reading-container text-center">            <?php if (count($images) > 0): ?>
                <?php foreach ($images as $image): ?>
                <div class="mb-3">
                    <img src="uploads/chapters/<?php echo htmlspecialchars($image['image_path']); ?>" 
                         alt="Chapter <?php echo $chapter['chapter_number']; ?> - <?php echo !empty($image['page_chapter_image']) ? htmlspecialchars($image['page_chapter_image']) : 'Image ' . $image['image_order']; ?>"
                         class="chapter-image">
                    <?php if (!empty($image['page_chapter_image'])): ?>
                        <div class="text-muted small mt-1"><?php echo htmlspecialchars($image['page_chapter_image']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Không có hình ảnh nào cho chapter này.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bottom Chapter Navigation -->
        <div class="d-flex justify-content-between mt-4 mb-5">
            <div>
                <?php if ($prev_id): ?>
                <a href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $prev_id; ?>" class="btn btn-primary btn-chapter">
                    <i class="fas fa-arrow-left me-1"></i>Trước
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-chapter" disabled>
                    <i class="fas fa-arrow-left me-1"></i>Trước
                </button>
                <?php endif; ?>
            </div>
            
            <a href="comic.php?id=<?php echo $comic_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-list me-1"></i>Danh sách chapter
            </a>
            
            <div>
                <?php if ($next_id): ?>
                <a href="read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $next_id; ?>" class="btn btn-primary btn-chapter">
                    Tiếp<i class="fas fa-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-chapter" disabled>
                    Tiếp<i class="fas fa-arrow-right ms-1"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
      <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>    <script>
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            // Left arrow key - go to previous chapter
            if (e.keyCode === 37) {
                <?php if ($prev_id): ?>
                window.location.href = 'read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $prev_id; ?>';
                <?php endif; ?>
            }
            
            // Right arrow key - go to next chapter
            if (e.keyCode === 39) {
                <?php if ($next_id): ?>
                window.location.href = 'read.php?comic=<?php echo $comic_id; ?>&chapter=<?php echo $next_id; ?>';
                <?php endif; ?>
            }
        });

        // Toggle page names
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('togglePageNames');
            const toggleText = document.getElementById('toggleText');
            const pageNames = document.querySelectorAll('.text-muted.small.mt-1');
            
            // Check saved preference
            const hidePageNames = localStorage.getItem('hidePageNames') === 'true';
            
            // Apply initial state
            if (hidePageNames) {
                pageNames.forEach(name => name.style.display = 'none');
                toggleText.textContent = 'Hiện tên trang';
            }
            
            // Toggle button click handler
            toggleButton.addEventListener('click', function() {
                const isHidden = pageNames[0].style.display === 'none';
                
                pageNames.forEach(name => {
                    name.style.display = isHidden ? '' : 'none';
                });
                
                toggleText.textContent = isHidden ? 'Ẩn tên trang' : 'Hiện tên trang';
                localStorage.setItem('hidePageNames', !isHidden);
            });
        });
    </script>
</body>
</html>