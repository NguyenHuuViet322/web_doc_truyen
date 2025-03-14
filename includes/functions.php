<?php
// Kiểm tra quyền admin
function requireAdmin()
{
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Kiểm tra đăng nhập
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Kiểm tra quyền admin
function isAdmin()
{
    return isset($_SESSION['admin_id']);
}

// Format ngày tháng
function formatDate($date)
{
    return date('d/m/Y H:i', strtotime($date));
}

// Upload file
function uploadFile($file, $target_dir)
{
    $target_file = $target_dir . basename($file['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Kiểm tra file ảnh
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false;
    }

    // Kiểm tra định dạng
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return false;
    }

    // Tạo tên file mới
    $newFileName = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $newFileName;
    }

    return false;
}

// Tạo slug từ chuỗi
function createSlug($str)
{
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str;
}

// Lấy thông tin người dùng
function getUserInfo($user_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy thông tin truyện
function getComicInfo($comic_id)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM chapters WHERE comic_id = c.id) as total_chapters,
               (SELECT COUNT(*) FROM follows WHERE comic_id = c.id) as total_follows
        FROM comics c
        WHERE c.id = ?
    ");
    $stmt->execute([$comic_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy danh sách thể loại của truyện
function getComicGenres($comic_id)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT g.* 
        FROM genres g
        JOIN comic_genres cg ON g.id = cg.genre_id
        WHERE cg.comic_id = ?
    ");
    $stmt->execute([$comic_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Kiểm tra người dùng đã theo dõi truyện chưa
function isFollowing($user_id, $comic_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND comic_id = ?");
    $stmt->execute([$user_id, $comic_id]);
    return $stmt->fetch() ? true : false;
}

// Lấy chapter mới nhất của truyện
function getLatestChapter($comic_id)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT * FROM chapters 
        WHERE comic_id = ? 
        ORDER BY chapter_number DESC 
        LIMIT 1
    ");
    $stmt->execute([$comic_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy chapter tiếp theo
function getNextChapter($comic_id, $current_chapter)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT * FROM chapters 
        WHERE comic_id = ? AND chapter_number > ?
        ORDER BY chapter_number ASC 
        LIMIT 1
    ");
    $stmt->execute([$comic_id, $current_chapter]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy chapter trước đó
function getPreviousChapter($comic_id, $current_chapter)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT * FROM chapters 
        WHERE comic_id = ? AND chapter_number < ?
        ORDER BY chapter_number DESC 
        LIMIT 1
    ");
    $stmt->execute([$comic_id, $current_chapter]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
