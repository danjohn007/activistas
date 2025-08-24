<?php
/**
 * Database Configuration
 * Configuraci贸n de la base de datos para el sistema de activistas digitales
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'ejercito_activistas';
    private $username = 'ejercito_activistas';
    private $password = 'Danjohn007!';
    private $charset = 'utf8mb4';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5, // Timeout de 5 segundos
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Test the connection
            $this->conn->query("SELECT 1");
            
        } catch(PDOException $exception) {
            // Log the error but don't expose it to the user interface
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // For development, show more details
            if (defined('APP_ENV') && APP_ENV === 'development') {
                echo "Error de conexi贸n (Dev): " . $exception->getMessage();
            } else {
                echo "Error de conexi贸n a la base de datos. Contacte al administrador.";
            }
            
            $this->conn = null;
        }
        
        return $this->conn;
    }
}
?>
