<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Only logged-in users can comment
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comic_id']) || !isset($_POST['content'])) {
    $_SESSION['message'] = 'Dữ liệu gửi không hợp lệ.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit();
}

$comic_id = (int)$_POST['comic_id'];
$chapter_id = isset($_POST['chapter_id']) ? (int)$_POST['chapter_id'] : null;
$content = trim($_POST['content']);
$user_id = $_SESSION['user_id'];

// Validate data
if ($comic_id <= 0 || empty($content)) {
    $_SESSION['message'] = 'Vui lòng nhập nội dung bình luận.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../comic.php?id=' . $comic_id);
    exit();
}

// Connect to database
$db = new Database();
$conn = $db->getConnection();

try {
    // Insert the comment
    $stmt = $conn->prepare("
        INSERT INTO comments (user_id, comic_id, chapter_id, content) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $comic_id, $chapter_id, $content]);
    
    $_SESSION['message'] = 'Đã thêm bình luận thành công!';
    $_SESSION['message_type'] = 'success';
    
} catch (PDOException $e) {
    $_SESSION['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to comic or chapter page
if ($chapter_id) {
    header('Location: ../read.php?comic=' . $comic_id . '&chapter=' . $chapter_id);
} else {
    header('Location: ../comic.php?id=' . $comic_id);
}
exit();
