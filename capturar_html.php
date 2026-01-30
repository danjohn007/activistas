<?php
/**
 * CAPTURAR HTML REAL GENERADO
 */
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/activityController.php';

echo "Generando HTML...<br>";

// Capturar el HTML completo
ob_start();
$controller = new ActivityController();
$controller->listActivities();
$html = ob_get_clean();

// Guardar en archivo
file_put_contents(__DIR__ . '/debug_html_output.html', $html);

echo "✅ HTML guardado en: <a href='debug_html_output.html' target='_blank'>debug_html_output.html</a><br><br>";

// Extraer solo las filas de la tabla
if (preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $matches)) {
    $tbody = $matches[1];
    
    // Contar filas
    $rows = substr_count($tbody, '<tr>');
    echo "Filas de tabla encontradas: <strong>$rows</strong><br><br>";
    
    // Extraer títulos de actividades
    preg_match_all('/<strong>(.*?)<\/strong>/s', $tbody, $titles);
    echo "Títulos de actividades en el HTML:<br>";
    echo "<ol>";
    foreach ($titles[1] as $title) {
        $clean_title = strip_tags($title);
        echo "<li>" . htmlspecialchars($clean_title) . "</li>";
    }
    echo "</ol>";
} else {
    echo "❌ No se encontró tabla en el HTML<br>";
}

echo "<br><strong>Abre el archivo HTML y compara con lo que ves en tu navegador</strong>";
?>
