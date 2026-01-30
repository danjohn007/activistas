<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/activity.php';

if (!isset($_GET['id'])) {
    die("Falta parámetro id");
}

$activityId = intval($_GET['id']);
$activityModel = new Activity();
$referenceFiles = $activityModel->getReferenceFiles($activityId);

echo "<h1>🔍 Debug Rutas de Imágenes - Actividad #$activityId</h1>";

foreach ($referenceFiles as $item) {
    echo "<div style='border:1px solid #ccc; padding:20px; margin:20px 0;'>";
    echo "<h3>Archivo: {$item['archivo']}</h3>";
    
    // Simular la misma lógica que usa la vista
    $archivoOriginal = $item['archivo'];
    
    // Path relativo que se construiría
    $archivo = $archivoOriginal;
    if (strpos($archivo, 'http://') !== 0 && strpos($archivo, 'https://') !== 0) {
        if (strpos($archivo, 'assets/') !== 0 && strpos($archivo, 'public/') !== 0) {
            $archivo = 'assets/uploads/evidencias/' . $archivo;
        }
    }
    if (strpos($archivo, 'public/') === 0) {
        $archivo = substr($archivo, 7);
    }
    
    // URL final
    $baseUrl = 'https://ejercitodigital.com.mx/';
    $archivoUrl = (strpos($archivo, 'http://') === 0 || strpos($archivo, 'https://') === 0) 
        ? $archivo 
        : $baseUrl . $archivo;
    
    echo "<p><strong>Nombre original:</strong> {$archivoOriginal}</p>";
    echo "<p><strong>Path procesado:</strong> {$archivo}</p>";
    echo "<p><strong>URL final:</strong> <a href='{$archivoUrl}' target='_blank'>{$archivoUrl}</a></p>";
    
    // Verificar si existe localmente
    $localPath = __DIR__ . '/' . $archivo;
    $altPath1 = __DIR__ . '/public/' . $archivo;
    $altPath2 = __DIR__ . '/public/assets/uploads/evidencias/' . $archivoOriginal;
    
    echo "<h4>Verificación de existencia:</h4>";
    echo "<ul>";
    echo "<li>Path 1: $localPath - " . (file_exists($localPath) ? '✅ EXISTE' : '❌ NO EXISTE') . "</li>";
    echo "<li>Path 2: $altPath1 - " . (file_exists($altPath1) ? '✅ EXISTE' : '❌ NO EXISTE') . "</li>";
    echo "<li>Path 3: $altPath2 - " . (file_exists($altPath2) ? '✅ EXISTE' : '❌ NO EXISTE') . "</li>";
    echo "</ul>";
    
    // Intentar mostrar la imagen
    echo "<h4>Preview:</h4>";
    echo "<img src='{$archivoUrl}' style='max-width:300px; border:2px solid #ddd;' onerror=\"this.style.border='2px solid red'; this.alt='❌ ERROR: No se pudo cargar'\">";
    
    echo "</div>";
}

if (empty($referenceFiles)) {
    echo "<p>❌ No hay archivos de referencia para esta actividad.</p>";
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
