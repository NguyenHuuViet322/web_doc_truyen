<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$db = new Database();
$conn = $db->getConnection();

// Delete all reading history for this user
$stmt = $conn->prepare("DELETE FROM read_history WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Set success message and redirect
$_SESSION['message'] = 'Lịch sử đọc truyện đã được xóa thành công.';
$_SESSION['message_type'] = 'success';
header('Location: ../history.php');
exit();
?>
