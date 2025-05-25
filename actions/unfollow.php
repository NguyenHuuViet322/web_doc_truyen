<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comic_id'])) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$comic_id = (int)$_POST['comic_id'];

// Validate comic_id
if ($comic_id <= 0) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Remove follow record
    $stmt = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND comic_id = ?");
    $stmt->execute([$user_id, $comic_id]);
    
    // Optional: Set success message
    $_SESSION['message'] = 'Đã bỏ theo dõi truyện!';
    $_SESSION['message_type'] = 'success';
    
} catch (PDOException $e) {
    // Log error
    error_log("Unfollow Error: " . $e->getMessage());
    
    // Set error message
    $_SESSION['message'] = 'Có lỗi xảy ra khi bỏ theo dõi truyện.';
    $_SESSION['message_type'] = 'danger';
}

// Check if we're on the following page (to redirect to it if so)
$is_following_page = strpos($_SERVER['HTTP_REFERER'] ?? '', 'following.php') !== false;

if ($is_following_page) {
    header('Location: ../following.php');
} else {
    // Redirect back to previous page
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
}
exit();
