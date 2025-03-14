<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

$db = new Database();
$conn = $db->getConnection();

// Mật khẩu mới: admin123
$password = password_hash('admin123', PASSWORD_DEFAULT);

// Cập nhật mật khẩu cho tài khoản admin
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$password]);

echo "Đã cập nhật mật khẩu thành công!";
