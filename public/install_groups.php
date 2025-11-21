<?php
/**
 * INSTALADOR DE GRUPOS - EJECUTAR UNA SOLA VEZ
 * Este script crea los grupos manualmente
 * IMPORTANTE: Eliminar este archivo después de usarlo
 */

// Intentar cargar el archivo de configuración
$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile)) {
    die("Error: No se encontró el archivo de configuración en: $configFile");
}

require_once $configFile;

try {
    // Crear conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Instalando sistema de grupos...</h2>";
    echo "<pre>";
    
    // 1. Crear tabla grupos si no existe
    echo "1. Creando tabla grupos...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS grupos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL UNIQUE,
        descripcion TEXT NULL,
        lider_id INT NULL,
        activo TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (lider_id) REFERENCES usuarios(id) ON DELETE SET NULL
    )");
    echo "   ✅ Tabla grupos creada\n\n";
    
    // 2. Agregar columna grupo_id a usuarios si no existe
    echo "2. Agregando columna grupo_id a usuarios...\n";
    try {
        $db->exec("ALTER TABLE usuarios ADD COLUMN grupo_id INT NULL AFTER lider_id");
        echo "   ✅ Columna grupo_id agregada\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ️ Columna grupo_id ya existe\n\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Agregar foreign key
    echo "3. Agregando foreign key...\n";
    try {
        $db->exec("ALTER TABLE usuarios ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL");
        echo "   ✅ Foreign key agregada\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ℹ️ Foreign key ya existe\n\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Insertar grupos por defecto
    echo "4. Insertando grupos por defecto...\n";
    $grupos = [
        ['GeneracionesVa', 'Grupo principal de activistas de GeneracionesVa'],
        ['Grupo mujeres Lupita', 'Grupo enfocado en activismo femenino'],
        ['Grupo Herman', 'Grupo de activistas coordinado por Herman'],
        ['Grupo Anita', 'Grupo de activistas coordinado por Anita']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO grupos (nombre, descripcion, activo) VALUES (?, ?, 1)");
    foreach ($grupos as $grupo) {
        $stmt->execute($grupo);
        echo "   ✅ Grupo '{$grupo[0]}' creado\n";
    }
    echo "\n";
    
    // 5. Verificar grupos creados
    $stmt = $db->query("SELECT COUNT(*) as total FROM grupos WHERE activo = 1");
    $result = $stmt->fetch();
    
    echo "5. Verificación final:\n";
    echo "   ✅ Grupos activos encontrados: " . $result['total'] . "\n\n";
    
    // Mostrar los grupos creados
    $stmt = $db->query("SELECT id, nombre, descripcion FROM grupos WHERE activo = 1");
    $grupos = $stmt->fetchAll();
    
    echo "   Grupos instalados:\n";
    foreach ($grupos as $grupo) {
        echo "   - ID: {$grupo['id']} | {$grupo['nombre']} | {$grupo['descripcion']}\n";
    }
    
    echo "\n✅ INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "\n⚠️ IMPORTANTE: Elimina este archivo (install_groups.php) por seguridad\n";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "❌ Error al ejecutar la migración:\n";
    echo $e->getMessage();
    echo "\n\nDetalles técnicos:\n";
    echo "Código de error: " . $e->getCode() . "\n";
    echo "</pre>";
}
?>
