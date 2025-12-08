<?php
/**
 * Migración: Agregar columna usuario_id a cortes_periodo
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Actualización: Agregar filtro por Activista</h2>\n";
    echo "<pre>\n";
    
    // Verificar si la columna ya existe
    $columns = $db->query("SHOW COLUMNS FROM cortes_periodo")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('usuario_id', $columns)) {
        echo "Agregando columna 'usuario_id'...\n";
        $db->exec("ALTER TABLE cortes_periodo ADD COLUMN usuario_id INT NULL AFTER actividad_id");
        echo "✓ Columna usuario_id agregada\n";
        
        // Agregar foreign key
        try {
            $db->exec("ALTER TABLE cortes_periodo ADD FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL");
            echo "✓ Foreign key usuario_id agregada\n";
        } catch (Exception $e) {
            echo "⚠️  No se pudo agregar foreign key de usuario_id: " . $e->getMessage() . "\n";
        }
        
        // Agregar índice
        try {
            $db->exec("ALTER TABLE cortes_periodo ADD INDEX idx_usuario (usuario_id)");
            echo "✓ Índice usuario_id agregado\n";
        } catch (Exception $e) {
            echo "⚠️  Índice usuario_id ya existe o error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
        echo "========================================\n";
        echo "✅ ACTUALIZACIÓN COMPLETADA\n";
        echo "========================================\n";
        echo "\nAhora puedes crear cortes filtrados por activista específico.\n";
        
    } else {
        echo "========================================\n";
        echo "✓ La columna usuario_id ya existe\n";
        echo "========================================\n";
    }
    
} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "❌ ERROR EN LA ACTUALIZACIÓN\n";
    echo "========================================\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
