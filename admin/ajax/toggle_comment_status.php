<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isAdmin() || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false]);
    exit();
}

$comment_id = $_POST['comment_id'] ?? 0;
$status = $_POST['status'] ?? '';

if ($comment_id && in_array($status, ['active', 'hidden'])) {
    $db = new Database();
    $conn = $db->getConnection();

    try {
        $stmt = $conn->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $comment_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
