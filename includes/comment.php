<?php
session_start();
require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $db = new Database();
    $conn = $db->getConnection();

    $user_id = $_SESSION['user_id'];
    $comic_id = $_POST['comic_id'] ?? 0;
    $chapter_id = $_POST['chapter_id'] ?? 0;
    $content = trim($_POST['content']);

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, comic_id, chapter_id, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $comic_id, $chapter_id, $content]);

        // Trả về HTML của comment mới
        $comment_id = $conn->lastInsertId();
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.avatar 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'html' => generateCommentHTML($comment)
        ]);
    }
}

function generateCommentHTML($comment)
{
    $html = '<div class="comment mb-3" id="comment-' . $comment['id'] . '">';
    $html .= '<div class="d-flex">';
    $html .= '<img src="uploads/avatars/' . $comment['avatar'] . '" class="rounded-circle me-2" width="40" height="40">';
    $html .= '<div class="flex-grow-1">';
    $html .= '<div class="comment-header">';
    $html .= '<strong>' . htmlspecialchars($comment['username']) . '</strong>';
    $html .= '<small class="text-muted ms-2">' . formatDate($comment['created_at']) . '</small>';
    $html .= '</div>';
    $html .= '<div class="comment-content">' . nl2br(htmlspecialchars($comment['content'])) . '</div>';
    $html .= '</div></div></div>';
    return $html;
}
