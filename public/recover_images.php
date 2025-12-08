<?php
/**
 * Script de Recuperación de Imágenes
 * Vincula las imágenes existentes con sus actividades
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos

require_once __DIR__ . '/../config/database.php';

echo "<h1>Recuperación de Imágenes de Evidencias</h1>";
echo "<p><strong>Inicio:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Directorio de evidencias
    $uploadDir = __DIR__ . '/assets/uploads/evidencias';
    
    if (!is_dir($uploadDir)) {
        die("<p style='color: red;'>ERROR: El directorio de evidencias no existe: $uploadDir</p>");
    }
    
    echo "<p>✓ Directorio encontrado: <code>$uploadDir</code></p>";
    
    // Obtener todas las imágenes
    $imageFiles = glob($uploadDir . '/*');
    $totalFiles = count($imageFiles);
    
    echo "<p>✓ Total de archivos encontrados: <strong>$totalFiles</strong></p>";
    
    if ($totalFiles == 0) {
        die("<p style='color: orange;'>No se encontraron archivos para procesar.</p>");
    }
    
    // Contadores
    $vinculadas = 0;
    $noEncontradas = 0;
    $errores = 0;
    $yaVinculadas = 0;
    
    echo "<h3>Procesando archivos...</h3>";
    echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;'>";
    
    foreach ($imageFiles as $filePath) {
        $fileName = basename($filePath);
        
        // Intentar extraer el ID de la actividad del nombre del archivo
        // Formatos esperados: task_13824_1761706511.jpg, activity_123.jpg, etc.
        $activityId = null;
        
        if (preg_match('/task_(\d+)_/', $fileName, $matches)) {
            $activityId = intval($matches[1]);
        } elseif (preg_match('/activity_(\d+)/', $fileName, $matches)) {
            $activityId = intval($matches[1]);
        } elseif (preg_match('/^(\d+)_/', $fileName, $matches)) {
            $activityId = intval($matches[1]);
        }
        
        if (!$activityId) {
            echo "<small style='color: gray;'>⊗ $fileName - No se pudo extraer ID</small><br>";
            $noEncontradas++;
            continue;
        }
        
        // Verificar si la actividad existe
        $stmt = $db->prepare("SELECT id, imagen_evidencia FROM actividades WHERE id = ?");
        $stmt->execute([$activityId]);
        $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$actividad) {
            echo "<small style='color: orange;'>⊗ $fileName - Actividad ID $activityId no existe en BD</small><br>";
            $noEncontradas++;
            continue;
        }
        
        // Verificar si ya tiene imagen
        if (!empty($actividad['imagen_evidencia'])) {
            echo "<small style='color: blue;'>⊙ $fileName - Ya vinculada (ID: $activityId)</small><br>";
            $yaVinculadas++;
            continue;
        }
        
        // Actualizar la BD con la ruta de la imagen
        $rutaRelativa = 'public/assets/uploads/evidencias/' . $fileName;
        
        $stmt = $db->prepare("UPDATE actividades SET imagen_evidencia = ? WHERE id = ?");
        $result = $stmt->execute([$rutaRelativa, $activityId]);
        
        if ($result) {
            echo "<small style='color: green;'>✓ $fileName - Vinculada a actividad ID $activityId</small><br>";
            $vinculadas++;
        } else {
            echo "<small style='color: red;'>✗ $fileName - Error al actualizar BD</small><br>";
            $errores++;
        }
        
        // Flush para mostrar progreso en tiempo real
        if ($vinculadas % 50 == 0) {
            flush();
            ob_flush();
        }
    }
    
    echo "</div>";
    
    // Resumen
    echo "<h2>Resumen del Proceso</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
    echo "<tr><td>✓ Vinculadas correctamente</td><td style='color: green; font-weight: bold;'>$vinculadas</td></tr>";
    echo "<tr><td>⊙ Ya estaban vinculadas</td><td style='color: blue;'>$yaVinculadas</td></tr>";
    echo "<tr><td>⊗ No se encontró actividad</td><td style='color: orange;'>$noEncontradas</td></tr>";
    echo "<tr><td>✗ Errores</td><td style='color: red;'>$errores</td></tr>";
    echo "<tr style='font-weight: bold;'><td>Total procesados</td><td>$totalFiles</td></tr>";
    echo "</table>";
    
    if ($vinculadas > 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-top: 20px;'>";
        echo "<h3 style='color: #155724;'>✓ ¡Éxito!</h3>";
        echo "<p>Se vincularon <strong>$vinculadas imágenes</strong> correctamente.</p>";
        echo "<p>Ahora deberías poder ver las evidencias en las actividades.</p>";
        echo "</div>";
    }
    
    if ($noEncontradas > 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-top: 20px;'>";
        echo "<h3>⚠️ Archivos no vinculados</h3>";
        echo "<p>Hay <strong>$noEncontradas archivos</strong> que no se pudieron vincular porque:</p>";
        echo "<ul>";
        echo "<li>El nombre del archivo no contiene un ID de actividad válido</li>";
        echo "<li>La actividad con ese ID no existe en la base de datos</li>";
        echo "</ul>";
        echo "<p>Estos archivos permanecen en el servidor pero no están asociados a ninguna actividad.</p>";
        echo "</div>";
    }
    
    echo "<p><strong>Fin:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='test_images.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver Test de Imágenes</a> ";
    echo "<a href='../activities/' class='btn' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Actividades</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
