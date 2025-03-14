<?php
session_start();
require_once 'config.php';
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $db = new Database();
    $conn = $db->getConnection();

    $user_id = $_SESSION['user_id'];
    $comic_id = $_POST['comic_id'] ?? 0;
    $rating = intval($_POST['rating']);

    if ($rating >= 1 && $rating <= 5) {
        try {
            // Thêm hoặc cập nhật đánh giá
            $stmt = $conn->prepare("
                INSERT INTO ratings (user_id, comic_id, rating) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE rating = ?
            ");
            $stmt->execute([$user_id, $comic_id, $rating, $rating]);

            // Tính trung bình đánh giá mới
            $stmt = $conn->prepare("
                SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
                FROM ratings 
                WHERE comic_id = ?
            ");
            $stmt->execute([$comic_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'avgRating' => round($result['avg_rating'], 1),
                'totalRatings' => $result['total_ratings']
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi đánh giá']);
        }
    }
}
