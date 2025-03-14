<?php
// Cấu hình cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_NAME', 'comic_website');
define('DB_USER', 'root');
define('DB_PASS', '');

// Cấu hình đường dẫn
define('BASE_URL', 'http://localhost/perfect-web');
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// Cấu hình phân trang
define('ITEMS_PER_PAGE', 20);

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Báo lỗi khi phát triển
error_reporting(E_ALL);
ini_set('display_errors', 1);
