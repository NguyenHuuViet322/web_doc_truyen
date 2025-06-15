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

// Ensure upload directories exist
function ensureUploadDirectoriesExist() {
    $directories = [
        '../uploads/',
        '../uploads/avatars/',
        '../uploads/comics/',
        '../uploads/chapters/'
    ];
    
    foreach ($directories as $dir) {
        $absolute_path = realpath(dirname(__FILE__) . '/' . $dir);
        if (!$absolute_path) {
            $absolute_path = dirname(__FILE__) . '/' . $dir;
        }
        
        if (!is_dir($absolute_path)) {
            if (!mkdir($absolute_path, 0777, true)) {
                error_log("Failed to create directory: $absolute_path");
            } else {
                error_log("Created directory: $absolute_path");
            }
        }
    }
}

/**
 * Clear all authentication cookies consistently
 */
function clearAllAuthCookies() {
    $cookie_options = [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    // Add secure flag if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $cookie_options['secure'] = true;
    }
    
    // Clear cookies with options
    setcookie('access_key', '', $cookie_options);
    setcookie('user_data', '', $cookie_options);
    setcookie('remember_token', '', $cookie_options);
    setcookie('remember_user', '', $cookie_options);
    setcookie('auth_token', '', $cookie_options);
    
    // For compatibility with older browsers
    setcookie('access_key', '', time() - 3600, '/');
    setcookie('user_data', '', time() - 3600, '/');
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
    setcookie('auth_token', '', time() - 3600, '/');
}

/**
 * Set remember me cookies for a user
 * @param array $user User data array with at least id and username
 * @param string $access_key Generated access key
 */
function setRememberMeCookies($user, $access_key) {
    // Clear existing cookies first
    clearAllAuthCookies();
    
    // Prepare user data for cookie
    $user_data = [
        'id' => $user['id'],
        'username' => $user['username']
    ];
    
    // Set cookie parameters
    $options = [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 days
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    // Add secure flag if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $options['secure'] = true;
    }
    
    // Set new cookies
    setcookie('access_key', $access_key, $options);
    setcookie('user_data', json_encode($user_data), $options);
}

/**
 * Generate a secure authentication token for a user without database storage
 * 
 * @param array $user User data (must contain at least id and username)
 * @param string $secret Optional secret key, defaults to config value
 * @return string The generated token
 */
function generateAuthToken($user, $secret = null) {
    if ($secret === null) {
        // Use a site-wide secret key
        // Ideally this would be stored in a config file or environment variable
        $secret = 'your-secret-key-change-this-in-production';
    }
    
    // Create payload with user data and expiration
    $payload = [
        'id' => $user['id'],
        'username' => $user['username'],
        'exp' => time() + (30 * 24 * 60 * 60), // 30 days expiration
        'iat' => time() // Issued at time
    ];
    
    // Convert payload to JSON and base64 encode
    $encodedPayload = base64_encode(json_encode($payload));
    
    // Create signature with HMAC
    $signature = hash_hmac('sha256', $encodedPayload, $secret);
    
    // Combine payload and signature for the token
    $token = $encodedPayload . '.' . $signature;
    
    return $token;
}

/**
 * Verify an authentication token and extract user data
 * 
 * @param string $token The authentication token
 * @param string $secret Optional secret key, defaults to config value
 * @return array|false User data if valid, false if invalid
 */
function verifyAuthToken($token, $secret = null) {
    if ($secret === null) {
        // Use the same site-wide secret key as in generateAuthToken
        $secret = 'your-secret-key-change-this-in-production';
    }
    
    // Split token into payload and signature
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return false; // Invalid token format
    }
    
    list($encodedPayload, $signature) = $parts;
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', $encodedPayload, $secret);
    if (!hash_equals($expectedSignature, $signature)) {
        return false; // Invalid signature
    }
    
    // Decode payload
    $payload = json_decode(base64_decode($encodedPayload), true);
    if (!$payload) {
        return false; // Invalid payload format
    }
    
    // Check if token has expired
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false; // Token expired
    }
    
    return $payload; // Return user data from token
}

/**
 * Set token-based authentication cookies
 * 
 * @param array $user User data array
 */
function setAuthTokenCookies($user) {
    // Clear existing cookies first
    clearAllAuthCookies();
    
    // Generate token
    $token = generateAuthToken($user);
    
    // Extract minimal user data for the browser
    $user_data = [
        'id' => $user['id'],
        'username' => $user['username']
    ];
    
    // Set cookie parameters
    $options = [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 days
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    // Add secure flag if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $options['secure'] = true;
    }
    
    // Set new cookies
    setcookie('auth_token', $token, $options);
    setcookie('user_data', json_encode($user_data), $options);
}

// Format ngày tháng
function formatDate($date)
{
    return date('d/m/Y H:i', strtotime($date));
}

// Upload file
function uploadFile($file, $target_dir)
{
    // Normalize directory path format and ensure it ends with a slash
    $target_dir = str_replace('\\', '/', $target_dir);
    if (substr($target_dir, -1) !== '/') {
        $target_dir .= '/';
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            error_log("Failed to create directory: $target_dir");
            return false;
        } else {
            error_log("Created directory: $target_dir");
        }
    }

    // Ensure the directory is writable
    if (!is_writable($target_dir)) {
        if (!chmod($target_dir, 0777)) {
            error_log("Failed to make directory writable: $target_dir");
            return false;
        }
    }

    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Kiểm tra file ảnh
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        error_log("File is not an image: {$file['name']}");
        return false;
    }

    // Kiểm tra định dạng
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        error_log("Invalid file type: $imageFileType");
        return false;
    }

    // Tạo tên file mới
    $newFileName = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $newFileName;

    // Perform the upload
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        error_log("File uploaded successfully: $target_file");
        return $newFileName;
    } else {
        // Log detailed error information
        error_log("Failed to move uploaded file. Source: {$file['tmp_name']}, Destination: $target_file");
        error_log("Upload error code: {$file['error']}");
        
        // Check for common issues
        error_log("Target dir exists: " . (is_dir($target_dir) ? 'Yes' : 'No'));
        error_log("Target dir writable: " . (is_writable($target_dir) ? 'Yes' : 'No'));
        error_log("Temp file exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No'));
        
        return false;
    }
}

// Debug function for file uploads
function debugFileUpload($file, $target_dir) {
    $upload_errors = array(
        UPLOAD_ERR_OK         => "No errors.",
        UPLOAD_ERR_INI_SIZE   => "Larger than upload_max_filesize.",
        UPLOAD_ERR_FORM_SIZE  => "Larger than form MAX_FILE_SIZE.",
        UPLOAD_ERR_PARTIAL    => "Partial upload.",
        UPLOAD_ERR_NO_FILE    => "No file.",
        UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
        UPLOAD_ERR_CANT_WRITE => "Can't write to disk.",
        UPLOAD_ERR_EXTENSION  => "File upload stopped by extension."
    );
    
    $error_code = $file['error'];
    $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : "Unknown error";
    
    $debug = array(
        'file_name' => $file['name'],
        'file_size' => $file['size'],
        'file_type' => $file['type'],
        'error_code' => $error_code,
        'error_message' => $error_message,
        'target_dir' => $target_dir,
        'target_dir_exists' => is_dir($target_dir) ? 'Yes' : 'No',
        'target_dir_writable' => is_writable($target_dir) ? 'Yes' : 'No',
        'tmp_file_exists' => file_exists($file['tmp_name']) ? 'Yes' : 'No',
        'tmp_file_readable' => is_readable($file['tmp_name']) ? 'Yes' : 'No'
    );
    
    return $debug;
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

/**
 * Output message to browser console for debugging
 * @param mixed $data Data to log
 */
function console_log($data) {
    if (is_array($data) || is_object($data)) {
        echo "<script>console.log('PHP: " . json_encode($data) . "');</script>";
    } else {
        echo "<script>console.log('PHP: " . $data . "');</script>";
    }
}
