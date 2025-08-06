<?php
/**
 * Database Configuration
 * Configuración de la base de datos para el sistema de activistas digitales
 */

class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Use SQLite for testing environment
            $dbPath = __DIR__ . '/../test_database.sqlite';
            $dsn = "sqlite:" . $dbPath;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, null, null, $options);
            
            // Create tables if they don't exist
            $this->createTables();
            
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
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre_completo VARCHAR(255) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            foto_perfil VARCHAR(255),
            password_hash VARCHAR(255) NOT NULL,
            direccion TEXT NOT NULL,
            rol TEXT NOT NULL CHECK(rol IN ('SuperAdmin', 'Gestor', 'Líder', 'Activista')),
            lider_id INTEGER NULL,
            estado TEXT DEFAULT 'pendiente' CHECK(estado IN ('pendiente', 'activo', 'suspendido', 'desactivado')),
            email_verificado BOOLEAN DEFAULT 0,
            token_verificacion VARCHAR(100),
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lider_id) REFERENCES usuarios(id) ON DELETE SET NULL
        );
        
        CREATE TABLE IF NOT EXISTS tipos_actividades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            activo BOOLEAN DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        $this->conn->exec($sql);
        
        // Insert default admin user if not exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute(['admin@activistas.com']);
        if ($stmt->fetchColumn() == 0) {
            $adminSql = "INSERT INTO usuarios (nombre_completo, telefono, email, password_hash, direccion, rol, estado, email_verificado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($adminSql);
            $stmt->execute([
                'Administrador del Sistema', 
                '0000000000', 
                'admin@activistas.com', 
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'Oficina Central', 
                'SuperAdmin', 
                'activo', 
                1
            ]);
        }
    }
}
?>