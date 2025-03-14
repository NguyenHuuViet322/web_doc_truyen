<?php
class Database
{
    private $conn = null;

    public function getConnection()
    {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("Kết nối database thất bại: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}
