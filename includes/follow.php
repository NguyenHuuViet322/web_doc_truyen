<?php
session_start();
require_once 'config.php';
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $db = new Database();
    $conn = $db->getConnection();

    $user_id = $_SESSION['user_id'];
    $comic_id = $_POST['comic_id'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'follow' hoặc 'unfollow'

    try {
        if ($action == 'follow') {
            $stmt = $conn->prepare("INSERT IGNORE INTO follows (user_id, comic_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $comic_id]);
        } else if ($action == 'unfollow') {
            $stmt = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND comic_id = ?");
            $stmt->execute([$user_id, $comic_id]);
        }

        // Đếm số người theo dõi
        $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE comic_id = ?");
        $stmt->execute([$comic_id]);
        $followers_count = $stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'followersCount' => $followers_count,
            'action' => $action
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
    }
}
