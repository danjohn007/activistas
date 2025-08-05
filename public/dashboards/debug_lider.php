<?php
/**
 * Página de debug para el dashboard del líder
 * Para usar temporalmente en caso de errores 500
 */

// Habilitar reporte de errores detallado
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Función de debug segura
function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] DEBUG: $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . print_r($data, true);
    }
    $logEntry .= "\n";
    
    $logFile = __DIR__ . '/../../logs/debug_lider.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    echo "<pre>$logEntry</pre>";
}

debugLog("=== INICIO DEBUG LIDER DASHBOARD ===");

try {
    debugLog("1. Cargando dependencias...");
    
    // Cargar archivos paso a paso
    require_once __DIR__ . '/../../config/app.php';
    debugLog("✓ app.php cargado");
    
    require_once __DIR__ . '/../../includes/functions.php';
    debugLog("✓ functions.php cargado");
    
    require_once __DIR__ . '/../../includes/auth.php';
    debugLog("✓ auth.php cargado");
    
    require_once __DIR__ . '/../../controllers/dashboardController.php';
    debugLog("✓ dashboardController.php cargado");
    
    // Iniciar sesión
    debugLog("2. Iniciando sesión...");
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        debugLog("✓ Sesión iniciada");
    } else {
        debugLog("✓ Sesión ya existía");
    }
    
    // Mostrar datos de sesión (sin datos sensibles)
    debugLog("Datos de sesión disponibles", array_keys($_SESSION));
    
    // Verificar usuario logueado
    debugLog("3. Verificando usuario...");
    if (isset($_SESSION['user_id'])) {
        debugLog("✓ Usuario logueado ID: " . $_SESSION['user_id']);
        debugLog("✓ Rol: " . ($_SESSION['user_role'] ?? 'No definido'));
        debugLog("✓ Nombre: " . ($_SESSION['user_name'] ?? 'No definido'));
    } else {
        debugLog("✗ Usuario NO logueado");
    }
    
    // Probar instanciación del controlador
    debugLog("4. Instanciando controlador...");
    $controller = new DashboardController();
    debugLog("✓ DashboardController instanciado");
    
    // Probar método liderDashboard
    debugLog("5. Ejecutando liderDashboard...");
    $controller->liderDashboard();
    debugLog("✓ liderDashboard ejecutado");
    
    // Verificar variables globales
    debugLog("6. Verificando variables globales...");
    $globals = ['teamStats', 'teamMembers', 'memberMetrics', 'recentActivities'];
    foreach ($globals as $var) {
        if (isset($GLOBALS[$var])) {
            debugLog("✓ \$GLOBALS['$var'] disponible", is_array($GLOBALS[$var]) ? "Array con " . count($GLOBALS[$var]) . " elementos" : gettype($GLOBALS[$var]));
        } else {
            debugLog("✗ \$GLOBALS['$var'] NO disponible");
        }
    }
    
    debugLog("=== DEBUG COMPLETADO CON ÉXITO ===");
    
    echo "<h2>✅ Debug completado exitosamente</h2>";
    echo "<p>El dashboard debería funcionar correctamente. Si aún hay problemas, revisa el archivo de log: /logs/debug_lider.log</p>";
    echo "<p><a href='lider.php'>Ir al Dashboard del Líder</a></p>";
    
} catch (Exception $e) {
    debugLog("❌ ERROR CAPTURADO: " . $e->getMessage());
    debugLog("Stack trace: " . $e->getTraceAsString());
    
    echo "<h2>❌ Error encontrado</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
}
?>