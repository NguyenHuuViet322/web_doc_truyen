<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$comment_id = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT c.*, 
           u.username,
           co.title as comic_title,
           (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) as likes_count
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN comics co ON c.comic_id = co.id
    WHERE c.id = ?
");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($comment) {
    $comment['created_at'] = formatDate($comment['created_at']);
    echo json_encode($comment);
} else {
    echo json_encode(['error' => 'Comment not found']);
}
