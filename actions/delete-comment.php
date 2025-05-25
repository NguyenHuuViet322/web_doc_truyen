<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Only logged-in users can delete comments
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$comment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$comic_id = isset($_GET['comic']) ? (int)$_GET['comic'] : 0;
$chapter_id = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 0;

if ($comment_id <= 0 || $comic_id <= 0) {
    $_SESSION['message'] = 'Yêu cầu không hợp lệ.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] ?? '') == 'admin';

$db = new Database();
$conn = $db->getConnection();

try {
    // Check if user is the comment owner or admin
    if (!$is_admin) {
        $check = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
        $check->execute([$comment_id]);
        $comment = $check->fetch();
        
        if (!$comment || $comment['user_id'] != $user_id) {
            $_SESSION['message'] = 'Bạn không có quyền xóa bình luận này.';
            $_SESSION['message_type'] = 'danger';
            
            if ($chapter_id) {
                header('Location: ../read.php?comic=' . $comic_id . '&chapter=' . $chapter_id);
            } else {
                header('Location: ../comic.php?id=' . $comic_id);
            }
            exit();
        }
    }
    
    // Delete the comment
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    $_SESSION['message'] = 'Đã xóa bình luận thành công!';
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
