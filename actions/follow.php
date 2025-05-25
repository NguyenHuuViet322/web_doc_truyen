<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '../index.php'));
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
    // Check if already following
    $check = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND comic_id = ?");
    $check->execute([$user_id, $comic_id]);
    
    if ($check->rowCount() == 0) {
        // Not following, so add follow
        $stmt = $conn->prepare("INSERT INTO follows (user_id, comic_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $comic_id]);
        
        // Optional: Set success message
        $_SESSION['message'] = 'Đã theo dõi truyện thành công!';
        $_SESSION['message_type'] = 'success';
    }
    
} catch (PDOException $e) {
    // Log error
    error_log("Follow Error: " . $e->getMessage());
    
    // Set error message
    $_SESSION['message'] = 'Có lỗi xảy ra khi theo dõi truyện.';
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to previous page
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
exit();
