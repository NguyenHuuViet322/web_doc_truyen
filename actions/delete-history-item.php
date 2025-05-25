<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get history item ID
$history_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($history_id <= 0) {
    $_SESSION['message'] = 'ID lịch sử không hợp lệ.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../history.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Verify this history item belongs to the current user
    $check_stmt = $conn->prepare("SELECT comic_id FROM read_history WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$history_id, $user_id]);
    $result = $check_stmt->fetch();
    
    if (!$result) {
        $_SESSION['message'] = 'Bạn không có quyền xóa mục này.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../history.php');
        exit();
    }
    
    // Delete the history item
    $delete_stmt = $conn->prepare("DELETE FROM read_history WHERE id = ?");
    $delete_stmt->execute([$history_id]);
    
    $_SESSION['message'] = 'Đã xóa truyện khỏi lịch sử đọc.';
    $_SESSION['message_type'] = 'success';
    
} catch (PDOException $e) {
    $_SESSION['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to history page
header('Location: ../history.php');
exit();
