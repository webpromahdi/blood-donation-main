<?php
/**
 * Database Configuration
 * PDO connection with error handling
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'blood_donation';
    private $username = 'root';
    private $password = '';  // Default XAMPP has no password
    private $conn = null;

    /**
     * Get PDO database connection
     * @return PDO|null
     */
    public function getConnection() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            // Log error but don't expose details to client
            error_log("Database Connection Error: " . $e->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}
