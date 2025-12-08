<?php
/**
 * Agregar columna usuario_id a cortes_periodo
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>Migración: Agregar usuario_id a cortes_periodo</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("ERROR: No hay conexión a la base de datos");
    }
    
    echo "<p style='color: green;'>✓ Conexión establecida</p>";
    
    // Verificar si la columna ya existe
    $stmt = $db->query("SHOW COLUMNS FROM cortes_periodo LIKE 'usuario_id'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠ La columna usuario_id ya existe</p>";
    } else {
        echo "<p>Agregando columna usuario_id...</p>";
        
        // Agregar la columna
        $db->exec("ALTER TABLE cortes_periodo ADD COLUMN usuario_id INT(11) NULL AFTER actividad_id");
        echo "<p style='color: green;'>✓ Columna usuario_id agregada</p>";
        
        // Agregar índice
        $db->exec("ALTER TABLE cortes_periodo ADD INDEX idx_usuario_id (usuario_id)");
        echo "<p style='color: green;'>✓ Índice agregado</p>";
        
        // Agregar foreign key
        $db->exec("ALTER TABLE cortes_periodo ADD CONSTRAINT fk_cortes_usuario 
                   FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL");
        echo "<p style='color: green;'>✓ Foreign key agregada</p>";
    }
    
    // Verificar estructura final
    echo "<h3>Estructura final:</h3>";
    $stmt = $db->query("DESCRIBE cortes_periodo");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] == 'usuario_id') ? "style='background-color: yellow;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color: green;'>✓ Migración completada exitosamente</h2>";
    echo "<p><a href='test_corte.php'>Volver a ejecutar el test</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
