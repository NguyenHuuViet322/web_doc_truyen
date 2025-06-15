<?php
// Enable error reporting for diagnostics
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<p style='color:green;'>✓ Database connection established successfully!</p>";
    
    // Test a simple query
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Database Tables:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Test users table specifically
    if (in_array('users', $tables)) {
        echo "<h2>Users Table Structure:</h2>";
        $stmt = $conn->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Test query to get users
        $stmt = $conn->query("SELECT * FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Sample User Data (up to 5 users):</h2>";
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='5'>";
            // Print headers
            echo "<tr>";
            foreach (array_keys($users[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr>";
            
            // Print data
            foreach ($users as $user) {
                echo "<tr>";
                foreach ($user as $key => $value) {
                    // Mask password for security
                    if ($key === 'password') {
                        echo "<td>[HIDDEN]</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No users found in database.</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ Users table not found in database.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Connection details used:</p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars(DB_HOST) . "</li>";
    echo "<li>Database Name: " . htmlspecialchars(DB_NAME) . "</li>";
    echo "<li>Username: " . htmlspecialchars(DB_USER) . "</li>";
    echo "<li>Password: [HIDDEN]</li>";
    echo "</ul>";
}
?>
