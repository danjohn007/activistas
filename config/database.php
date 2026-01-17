<?php
/**
 * Database Configuration
 * Configuración de la base de datos para el sistema de activistas digitales
 */

class Database {
    private static $instance = null;
    private $host = 'localhost';
    private $db_name = 'ejercito_activistas';
    private $username = 'ejercito_activistas';
    private $password = 'Danjohn007!';
    private $charset = 'utf8mb4';
    private $conn;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true, // CRÍTICO: Reutilizar conexiones
                PDO::ATTR_TIMEOUT => 30, // Aumentado para AWS (latencia red)
                // CRÍTICO: Timezone DEBE estar en INIT_COMMAND para conexiones persistentes
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-06:00'",
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Mejorar memoria
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Verificar timezone (debug)
            // $tz = $this->conn->query("SELECT @@session.time_zone")->fetchColumn();
            // error_log("MySQL timezone: $tz");
            
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