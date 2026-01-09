<?php
/**
 * Debug - Contar elementos en el HTML generado
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    die("ERROR: Usuario no autenticado");
}

// Capturar el output del controlador
ob_start();
require_once __DIR__ . '/../../controllers/activityController.php';

$activityController = new ActivityController();
$activityController->listActivities();

$html = ob_get_clean();

// Analizar el HTML
$trCount = substr_count($html, '<tr>');
$tbodyCount = substr_count($html, '<tbody>');
$tableCount = substr_count($html, '<table');
$foreachCount = substr_count($html, '<?php foreach');

echo "<h2>Análisis del HTML Generado</h2>";
echo "<hr>";

echo "<h3>Estadísticas:</h3>";
echo "<ul>";
echo "<li><strong>&lt;table&gt; encontrados:</strong> $tableCount</li>";
echo "<li><strong>&lt;tbody&gt; encontrados:</strong> $tbodyCount</li>";
echo "<li><strong>&lt;tr&gt; encontrados:</strong> $trCount (incluye header)</li>";
echo "</ul>";

// Buscar filas de actividades específicamente (las que tienen checkbox o título)
preg_match_all('/activity-checkbox/', $html, $matches);
$checkboxCount = count($matches[0]);

echo "<h3>Filas de actividades:</h3>";
echo "<p>Checkboxes encontrados: <strong>$checkboxCount</strong></p>";

// Extraer los IDs de las actividades del HTML
preg_match_all('/value="(\d+)".*?activity-checkbox/', $html, $idMatches);
$activityIds = $idMatches[1];

echo "<h3>IDs de actividades en el HTML:</h3>";
echo "<p>Total: " . count($activityIds) . "</p>";

if (!empty($activityIds)) {
    // Contar duplicados
    $counts = array_count_values($activityIds);
    $duplicates = array_filter($counts, function($count) { return $count > 1; });
    
    if (!empty($duplicates)) {
        echo "<div style='background: #ffcccc; padding: 10px; border: 2px solid red;'>";
        echo "<h4 style='color: red;'>⚠ IDs DUPLICADOS EN EL HTML:</h4>";
        echo "<ul>";
        foreach ($duplicates as $id => $count) {
            echo "<li>ID <strong>$id</strong> aparece <strong>$count veces</strong></li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<p style='color: green;'>✓ No hay IDs duplicados en el HTML</p>";
    }
    
    echo "<p>IDs únicos: " . implode(', ', array_unique($activityIds)) . "</p>";
}

echo "<hr>";
echo "<p><a href='activities/'>Ver página normal</a></p>";

// Opcional: Mostrar fragmento del HTML para debugging
if (isset($_GET['show_html'])) {
    echo "<h3>HTML Generado (primeros 5000 caracteres):</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px;'>";
    echo htmlspecialchars(substr($html, 0, 5000));
    echo "</pre>";
    echo "<p><a href='?show_html=1'>Ver más HTML</a></p>";
}
?>
