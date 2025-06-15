<?php
class Database
{
    private $conn = null;    public function getConnection()
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
                
                // Test connection with a simple query
                $this->conn->query("SELECT 1");
                
                // Log successful connection
                error_log("Database connection established successfully");
                
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                
                // Don't die in production, return null and handle the error gracefully
                $this->conn = null;
                
                // Only die in development environment
                if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                    die("Kết nối database thất bại: " . $e->getMessage());
                }
            }
        }
        return $this->conn;
    }
}
