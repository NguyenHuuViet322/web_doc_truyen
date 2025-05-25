<?php
/**
 * This script updates the database schema to add required columns
 */
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if access_key column exists in users table
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'access_key'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add access_key column to users table
        $conn->exec("ALTER TABLE users ADD COLUMN access_key VARCHAR(255) NULL");
        echo "Successfully added access_key column to users table.<br>";
    } else {
        echo "Column access_key already exists in users table.<br>";
    }
    
    echo "Database schema update completed successfully.";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
