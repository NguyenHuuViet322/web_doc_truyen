<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/constants.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Lấy tất cả slug hiện có trong database
    $existing_slugs = $conn->query("SELECT slug FROM genres")->fetchAll(PDO::FETCH_COLUMN);

    // Thêm các thể loại mới từ CATEGORIES
    $stmt = $conn->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");

    foreach (CATEGORIES as $slug => $name) {
        if (!in_array($slug, $existing_slugs)) {
            $stmt->execute([$name, $slug]);
        }
    }

    $conn->commit();
    $_SESSION['success'] = 'Đồng bộ thể loại thành công!';
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
}

header('Location: manage-genres.php');
exit;
