<?php
/**
 * Test Database Configuration for Development
 * Uses SQLite for testing without external dependencies
 */

class Database {
    private $db_path;
    private $conn;

    public function __construct() {
        $this->db_path = __DIR__ . '/../test_database.sqlite';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "sqlite:" . $this->db_path;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, null, null, $options);
            
            // Test the connection
            $this->conn->query("SELECT 1");
            
        } catch(PDOException $exception) {
            // Log the error but don't expose it to the user interface
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // For development, show more details
            if (defined('APP_ENV') && APP_ENV === 'development') {
                echo "Error de conexión (Dev): " . $exception->getMessage();
            } else {
                echo "Error de conexión a la base de datos. Contacte al administrador.";
            }
            
            $this->conn = null;
        }
        
        return $this->conn;
    }
}
?>