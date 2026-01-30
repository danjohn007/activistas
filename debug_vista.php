<?php
/**
 * DEBUG SESIÓN Y VISTA
 */
session_start();

echo "<h1>🔍 Debug Sesión y Vista</h1><pre>";

echo "=== 1. INFORMACIÓN DE SESIÓN ===\n";
echo "Usuario logueado: " . (isset($_SESSION['user_id']) ? 'SÍ' : 'NO') . "\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: {$_SESSION['user_id']}\n";
    echo "Username: {$_SESSION['username']}\n";
    echo "Nombre: {$_SESSION['user_name']}\n";
    echo "Rol: {$_SESSION['user_role']}\n";
}

echo "\n=== 2. CARGAR CONTROLADOR ===\n";
try {
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/controllers/activityController.php';
    
    $controller = new ActivityController();
    echo "✅ Controlador cargado correctamente\n";
    
    // Simular llamada al método listActivities
    echo "\n=== 3. SIMULAR VARIABLES DE LA VISTA ===\n";
    // Simular exactamente lo que hace el controlador
    $_GET = $_GET ?? [];
    $_GET['page'] = $_GET['page'] ?? 1;
    
    require_once __DIR__ . '/models/activity.php';
    $activityModel = new Activity();
    
    $filters = [
        'user_id' => $_SESSION['user_id'],
        'user_role' => $_SESSION['user_role'],
        'include_evidence_count' => true
    ];
    
    $activities = $activityModel->getActivities($filters);
    echo "Actividades obtenidas directamente: " . count($activities) . "\n";
    
    echo "\n=== 4. VERIFICAR VARIABLE EN VISTA ===\n";
    echo "¿\$activities está definido antes del include? " . (isset($activities) ? "SÍ" : "NO") . "\n";
    echo "¿\$activities es array? " . (is_array($activities) ? "SÍ" : "NO") . "\n";
    echo "¿\$activities está vacío? " . (empty($activities) ? "SÍ" : "NO") . "\n";
    
    echo "\n=== 5. CAPTURAR OUTPUT DEL CONTROLADOR ===\n";
    ob_start();
    $controller->listActivities();
    $output = ob_get_clean();
    
    // Buscar la variable $activities en el output
    if (strpos($output, 'foreach ($activities') !== false) {
        echo "✅ La vista tiene el foreach correcto\n";
    } else {
        echo "❌ No se encontró el foreach en la vista\n";
    }
    
    // Contar cuántas filas de tabla hay
    $tableRows = substr_count($output, '<tr>');
    echo "Filas de tabla generadas: " . ($tableRows - 1) . " (descontando header)\n";
    
    // Buscar "No hay actividades"
    if (stripos($output, 'No hay actividades') !== false) {
        echo "⚠️ ENCONTRADO: Mensaje 'No hay actividades' en el HTML\n";
        // Extraer contexto
        $pos = stripos($output, 'No hay actividades');
        $context = substr($output, max(0, $pos - 200), 400);
        echo "Contexto:\n" . htmlspecialchars($context) . "\n";
    }
    
    // Buscar si el card de porcentaje está
    if (strpos($output, 'Porcentaje de Cumplimiento') !== false) {
        echo "✅ Card de porcentaje ESTÁ en el HTML\n";
    } else {
        echo "❌ Card de porcentaje NO ESTÁ (indica que !empty(\$activities) fue FALSE)\n";
    }
    
    // Verificar si hay errores de PHP en el output
    if (strpos($output, 'Warning:') !== false || 
        strpos($output, 'Notice:') !== false || 
        strpos($output, 'Error:') !== false) {
        echo "❌ HAY ERRORES PHP EN EL OUTPUT:\n";
        preg_match_all('/(Warning|Notice|Error):.*$/m', $output, $matches);
        foreach ($matches[0] as $error) {
            echo "  $error\n";
        }
    }
    
    echo "\n=== 4. EXTRACTO DEL HTML GENERADO ===\n";
    // Buscar la sección de tbody
    if (preg_match('/<tbody>(.*?)<\/tbody>/s', $output, $matches)) {
        $tbody = $matches[1];
        $lines = explode("\n", trim($tbody));
        $preview = array_slice($lines, 0, 20);
        echo implode("\n", $preview);
        if (count($lines) > 20) {
            echo "\n... (truncado, " . count($lines) . " líneas totales)";
        }
    } else {
        echo "❌ No se encontró <tbody> en el HTML\n";
    }
    
    echo "\n\n=== 5. BUSCAR MENSAJE DE ERROR ===\n";
    // Buscar el bloque else que muestra cuando no hay actividades
    if (preg_match('/<div class="alert[^>]*>.*?(No hay actividades|no se encontraron).*?<\/div>/is', $output, $matches)) {
        echo "⚠️ ENCONTRADO bloque de 'No hay actividades':\n";
        echo htmlspecialchars($matches[0]) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n</pre>";

// Guardar el HTML completo para inspección
file_put_contents(__DIR__ . '/debug_output.html', $output ?? 'No output');
echo "<p>✅ HTML completo guardado en: <a href='debug_output.html' target='_blank'>debug_output.html</a></p>";
?>
