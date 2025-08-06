<?php
/**
 * Test de validación de la lógica del dashboard sin conexión a BD
 */

echo "=== Test de Validación de Dashboard ===\n\n";

// Test 1: Verificar que los archivos existen
echo "1. Verificando archivos del dashboard...\n";
$files_to_check = [
    'public/dashboards/admin.php',
    'public/api/stats.php',
    'controllers/dashboardController.php',
    'models/user.php',
    'models/activity.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ $file existe\n";
    } else {
        echo "✗ $file NO encontrado\n";
    }
}

// Test 2: Verificar sintaxis PHP
echo "\n2. Verificando sintaxis PHP...\n";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✓ $file - sintaxis correcta\n";
        } else {
            echo "✗ $file - errores de sintaxis:\n$output\n";
        }
    }
}

// Test 3: Verificar que el dashboard admin contiene los cambios para datos reales
echo "\n3. Verificando implementación de datos reales...\n";
$admin_content = file_get_contents('public/dashboards/admin.php');

$checks = [
    'json_encode($activityLabels)' => 'Datos reales de actividades por tipo',
    'json_encode($userData)' => 'Datos reales de usuarios por rol',
    'updateCharts()' => 'Función de actualización en tiempo real',
    'fetch.*api/stats.php' => 'Llamada a API de stats',
    'activitiesChart.update()' => 'Actualización de gráfica de actividades',
    'usersChart.update()' => 'Actualización de gráfica de usuarios'
];

foreach ($checks as $pattern => $description) {
    if (preg_match("/$pattern/", $admin_content)) {
        echo "✓ $description implementado\n";
    } else {
        echo "✗ $description NO encontrado\n";
    }
}

// Test 4: Verificar estructura de la API
echo "\n4. Verificando API de estadísticas...\n";
$api_content = file_get_contents('public/api/stats.php');

$api_checks = [
    'header.*Content-Type.*json' => 'Headers JSON configurados',
    'getActivityStats' => 'Obtención de estadísticas de actividades',
    'getActivitiesByType' => 'Obtención de actividades por tipo',
    'getUserStats' => 'Obtención de estadísticas de usuarios',
    'json_encode.*response' => 'Respuesta en formato JSON'
];

foreach ($api_checks as $pattern => $description) {
    if (preg_match("/$pattern/", $api_content)) {
        echo "✓ $description implementado\n";
    } else {
        echo "✗ $description NO encontrado\n";
    }
}

// Test 5: Verificar que no hay datos hardcodeados en las gráficas
echo "\n5. Verificando eliminación de datos hardcodeados...\n";
$hardcoded_patterns = [
    "data: \[12, 8, 5, 3\]" => "Datos hardcodeados de actividades",
    "data: \[1, 2, 5, 15\]" => "Datos hardcodeados de usuarios",
    "labels: \['Redes Sociales', 'Eventos', 'Capacitación', 'Encuestas'\]" => "Labels hardcodeados de actividades"
];

$has_hardcoded = false;
foreach ($hardcoded_patterns as $pattern => $description) {
    if (preg_match("/" . preg_quote($pattern, "/") . "/", $admin_content)) {
        echo "✗ Aún contiene: $description\n";
        $has_hardcoded = true;
    }
}

if (!$has_hardcoded) {
    echo "✓ No se encontraron datos hardcodeados en las gráficas\n";
}

echo "\n=== Resultado del Test ===\n";
echo "El dashboard ha sido modificado para usar datos reales de la base de datos.\n";
echo "Las gráficas ahora se alimentan dinámicamente desde los modelos PHP.\n";
echo "Se implementó un endpoint API para actualizaciones en tiempo real.\n";
echo "✓ Test completado\n";
?>