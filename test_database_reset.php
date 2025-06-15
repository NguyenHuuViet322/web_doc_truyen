<?php
// Reset database and user data script
// This script will reset the session and verify database connection

// Start with a clean session
session_start();
session_destroy();
session_start();

echo '<h1>Database and User Reset Tool</h1>';

// Load required files
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check database connection
echo '<h2>Database Connection Test</h2>';
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo '<p style="color: green;">Database connection successful!</p>';
        
        // Test database query
        $stmt = $conn->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo '<p>Database version: ' . $version['version'] . '</p>';
        
        // Check users table
        echo '<h2>Users Table Structure</h2>';
        $stmt = $conn->query("DESCRIBE users");
        if ($stmt) {
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<pre>';
            print_r($columns);
            echo '</pre>';
            
            // Count users
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch();
            echo '<p>Total users in database: ' . $count['count'] . '</p>';
            
            // List the first 5 users
            $stmt = $conn->query("SELECT id, username, email, role, avatar, status, created_at FROM users LIMIT 5");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<h3>First 5 Users</h3>';
            echo '<pre>';
            print_r($users);
            echo '</pre>';
        } else {
            echo '<p style="color: red;">Could not query users table structure</p>';
        }
    } else {
        echo '<p style="color: red;">Database connection failed!</p>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}

// Check avatar directory
echo '<h2>Avatar Directory Test</h2>';
$avatar_dir = 'uploads/avatars/';
if (is_dir($avatar_dir)) {
    echo '<p style="color: green;">Avatar directory exists: ' . $avatar_dir . '</p>';
    
    // List files
    $files = scandir($avatar_dir);
    echo '<p>Files in directory:</p>';
    echo '<ul>';
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo '<li>' . $file . ' - ' . (file_exists($avatar_dir . $file) ? 'Accessible' : 'Not accessible') . '</li>';
        }
    }
    echo '</ul>';
} else {
    echo '<p style="color: red;">Avatar directory does not exist!</p>';
    
    // Try to create it
    if (@mkdir($avatar_dir, 0777, true)) {
        echo '<p style="color: green;">Successfully created avatar directory</p>';
    } else {
        echo '<p style="color: red;">Failed to create avatar directory</p>';
    }
}

echo '<p><a href="login.php">Go to login page</a></p>';
echo '<p><a href="create_default_avatar.php">Create default avatar</a></p>';
echo '<p><a href="debug_user_data.php">Debug user data (after login)</a></p>';
?>
