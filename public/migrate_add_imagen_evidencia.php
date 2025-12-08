<?php
/**
 * Migración: Agregar columna para guardar ruta de imagen de evidencia
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>Migración: Agregar columna imagen_evidencia</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si la columna ya existe
    $stmt = $db->query("SHOW COLUMNS FROM actividades LIKE 'imagen_evidencia'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠ La columna imagen_evidencia ya existe</p>";
    } else {
        echo "<p>Agregando columna imagen_evidencia...</p>";
        
        // Agregar la columna después de descripcion
        $db->exec("ALTER TABLE actividades ADD COLUMN imagen_evidencia VARCHAR(500) NULL AFTER descripcion");
        echo "<p style='color: green;'>✓ Columna imagen_evidencia agregada</p>";
    }
    
    // Verificar estructura final
    echo "<h3>Estructura final:</h3>";
    $stmt = $db->query("DESCRIBE actividades");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] == 'imagen_evidencia') ? "style='background-color: yellow;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color: green;'>✓ Migración completada exitosamente</h2>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-top: 20px;'>";
    echo "<h3>⚠️ Importante:</h3>";
    echo "<p>La columna se ha agregado pero está vacía. Las imágenes que ya existen en el servidor NO están vinculadas a las actividades.</p>";
    echo "<p><strong>¿Qué significa esto?</strong></p>";
    echo "<ul>";
    echo "<li>Las nuevas actividades con evidencia SÍ guardarán correctamente la ruta</li>";
    echo "<li>Las actividades antiguas NO tienen referencia a sus imágenes (están huérfanas)</li>";
    echo "<li>Tienes 2949 imágenes en el servidor pero no sabemos cuál pertenece a qué actividad</li>";
    echo "</ul>";
    echo "<p><strong>Solución para imágenes antiguas:</strong></p>";
    echo "<p>Si necesitas recuperar las imágenes antiguas, puedo crear un script que intente hacer match entre:</p>";
    echo "<ul>";
    echo "<li>El ID de la actividad en el nombre del archivo (ej: task_13824_*.jpg)</li>";
    echo "<li>La fecha de creación del archivo vs fecha de la actividad</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
