<?php
function resetAutoIncrement($conn, $table)
{
    try {
        // Lấy ID lớn nhất hiện tại
        $stmt = $conn->query("SELECT MAX(id) FROM $table");
        $max_id = $stmt->fetchColumn();

        // Reset auto increment
        if ($max_id === false || $max_id === null) {
            $conn->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        } else {
            $conn->exec("ALTER TABLE $table AUTO_INCREMENT = " . ($max_id + 1));
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}
