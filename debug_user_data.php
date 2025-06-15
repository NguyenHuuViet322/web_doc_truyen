<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

echo '<h1>User Data Debug</h1>';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<p>Not logged in. Please <a href="login.php">login</a> first.</p>';
    exit();
}

// 1. Check session data
echo '<h2>Session Data</h2>';
echo '<pre>';
// Only show relevant data, avoid sensitive information
$safe_session = $_SESSION;
if (isset($safe_session['password'])) {
    $safe_session['password'] = '[HIDDEN]';
}
print_r($safe_session);
echo '</pre>';

// 2. Get database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo '<p style="color: red;">Database connection failed!</p>';
    exit();
}

// 3. Fetch user data directly from database
$user_id = $_SESSION['user_id'];
echo '<h2>Database User Data (ID: ' . $user_id . ')</h2>';

try {
    // First try to get the column list from the users table
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo '<h3>User Table Columns</h3>';
    echo '<pre>';
    print_r($columns);
    echo '</pre>';
    
    // Now fetch the actual user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo '<h3>User Data from Database</h3>';
        echo '<pre>';
        // Hide password for security
        if (isset($user['password'])) {
            $user['password'] = '[HIDDEN]';
        }
        print_r($user);
        echo '</pre>';
        
        // Highlight avatar and status fields
        echo '<h3>Critical Fields</h3>';
        echo '<p><strong>Avatar:</strong> ' . (isset($user['avatar']) ? $user['avatar'] : 'NOT FOUND') . '</p>';
        echo '<p><strong>Status:</strong> ' . (isset($user['status']) ? $user['status'] : 'NOT FOUND') . '</p>';
        
        // Check if avatar file exists
        if (isset($user['avatar'])) {
            $avatar_path = 'uploads/avatars/' . $user['avatar'];
            echo '<p><strong>Avatar file exists:</strong> ' . (file_exists($avatar_path) ? 'Yes' : 'No') . '</p>';
            if (file_exists($avatar_path)) {
                echo '<p><img src="' . $avatar_path . '" style="max-width: 100px; border: 1px solid #ddd;"></p>';
            }
        }
    } else {
        echo '<p style="color: red;">No user found with ID: ' . $user_id . '</p>';
    }
} catch (PDOException $e) {
    echo '<p style="color: red;">Database error: ' . $e->getMessage() . '</p>';
}

// 4. Test setting avatar and status in session
echo '<h2>Update Session Test</h2>';
if (isset($user['avatar'])) {
    $_SESSION['avatar'] = $user['avatar'];
    echo '<p>Updated session avatar to: ' . $_SESSION['avatar'] . '</p>';
}

if (isset($user['status'])) {
    $_SESSION['status'] = $user['status'];
    echo '<p>Updated session status to: ' . $_SESSION['status'] . '</p>';
}

echo '<p><a href="profile.php">Go to profile</a> to see if changes take effect</p>';
?>
