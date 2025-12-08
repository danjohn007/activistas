<?php
/**
 * Migración: Agregar columnas grupo_id y actividad_id a cortes_periodo
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Actualización de tabla cortes_periodo</h2>\n";
    echo "<pre>\n";
    
    // Verificar si las columnas ya existen
    $columns = $db->query("SHOW COLUMNS FROM cortes_periodo")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columnas actuales:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }
    echo "\n";
    
    $needsUpdate = !in_array('grupo_id', $columns) || !in_array('actividad_id', $columns);
    
    if ($needsUpdate) {
        echo "Agregando columnas faltantes...\n\n";
        
        // Agregar grupo_id si no existe
        if (!in_array('grupo_id', $columns)) {
            echo "Agregando columna 'grupo_id'...\n";
            $db->exec("ALTER TABLE cortes_periodo ADD COLUMN grupo_id INT NULL AFTER creado_por");
            echo "✓ Columna grupo_id agregada\n";
            
            // Agregar foreign key
            try {
                $db->exec("ALTER TABLE cortes_periodo ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL");
                echo "✓ Foreign key grupo_id agregada\n";
            } catch (Exception $e) {
                echo "⚠️  No se pudo agregar foreign key de grupo_id: " . $e->getMessage() . "\n";
            }
            
            // Agregar índice
            try {
                $db->exec("ALTER TABLE cortes_periodo ADD INDEX idx_grupo (grupo_id)");
                echo "✓ Índice grupo_id agregado\n";
            } catch (Exception $e) {
                echo "⚠️  Índice grupo_id ya existe o error: " . $e->getMessage() . "\n";
            }
        }
        
        // Agregar actividad_id si no existe
        if (!in_array('actividad_id', $columns)) {
            echo "\nAgregando columna 'actividad_id'...\n";
            $db->exec("ALTER TABLE cortes_periodo ADD COLUMN actividad_id INT NULL AFTER grupo_id");
            echo "✓ Columna actividad_id agregada\n";
            
            // Agregar foreign key
            try {
                $db->exec("ALTER TABLE cortes_periodo ADD FOREIGN KEY (actividad_id) REFERENCES tipos_actividades(id) ON DELETE SET NULL");
                echo "✓ Foreign key actividad_id agregada\n";
            } catch (Exception $e) {
                echo "⚠️  No se pudo agregar foreign key de actividad_id: " . $e->getMessage() . "\n";
            }
            
            // Agregar índice
            try {
                $db->exec("ALTER TABLE cortes_periodo ADD INDEX idx_actividad (actividad_id)");
                echo "✓ Índice actividad_id agregado\n";
            } catch (Exception $e) {
                echo "⚠️  Índice actividad_id ya existe o error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
        echo "========================================\n";
        echo "✅ ACTUALIZACIÓN COMPLETADA\n";
        echo "========================================\n";
        echo "\n";
        
        // Verificar columnas después de la actualización
        $columnsAfter = $db->query("SHOW COLUMNS FROM cortes_periodo")->fetchAll(PDO::FETCH_COLUMN);
        echo "Columnas después de la actualización:\n";
        foreach ($columnsAfter as $col) {
            echo "  - $col\n";
        }
        
    } else {
        echo "========================================\n";
        echo "✓ La tabla ya está actualizada\n";
        echo "========================================\n";
        echo "\nTodas las columnas necesarias ya existen.\n";
    }
    
    echo "\nAhora puedes crear cortes con filtros de grupo y actividad.\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "❌ ERROR EN LA ACTUALIZACIÓN\n";
    echo "========================================\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>
