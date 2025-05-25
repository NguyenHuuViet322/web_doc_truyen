<?php
/**
 * Track user reading history
 * Include this file on pages where users view comics (comic.php and read.php)
 */

// Only track if user is logged in and comic_id is set
if (isset($_SESSION['user_id']) && isset($comic_id) && is_numeric($comic_id)) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if a record already exists for this user and comic
        $check_stmt = $conn->prepare("
            SELECT id FROM read_history 
            WHERE user_id = :user_id AND comic_id = :comic_id
        ");
        $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update the timestamp on existing record
            $update_stmt = $conn->prepare("
                UPDATE read_history 
                SET created_at = NOW() 
                WHERE user_id = :user_id AND comic_id = :comic_id
            ");
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            // Create new record
            $insert_stmt = $conn->prepare("
                INSERT INTO read_history (user_id, comic_id)
                VALUES (:user_id, :comic_id)
            ");
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':comic_id', $comic_id, PDO::PARAM_INT);
            $insert_stmt->execute();
        }
    } catch (PDOException $e) {
        // Log error but continue execution to not disrupt user experience
        error_log("Error tracking reading history: " . $e->getMessage());
    }
}
?>
