<?php
/**
 * Utilidad para limpiar el caché del sistema
 * Puede ser llamado manualmente o mediante cron job
 */

require_once __DIR__ . '/../includes/cache.php';

// Verificar si se está ejecutando desde CLI o web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Si es desde web, verificar autenticación de admin
    require_once __DIR__ . '/../includes/auth.php';
    $auth = getAuth();
    
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'No autenticado']));
    }
    
    $currentUser = $auth->getCurrentUser();
    if ($currentUser['rol'] !== 'SuperAdmin') {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Permiso denegado']));
    }
    
    header('Content-Type: application/json');
}

try {
    $cache = cache();
    
    // Opción para limpiar solo cache expirado o todo
    $clearAll = isset($_GET['all']) || (isset($argv[1]) && $argv[1] === 'all');
    
    if ($clearAll) {
        $cache->clear();
        $message = 'Caché completo limpiado exitosamente';
    } else {
        $cache->cleanExpired();
        $message = 'Caché expirado limpiado exitosamente';
    }
    
    if ($isCLI) {
        echo "✅ $message\n";
    } else {
        echo json_encode(['success' => true, 'message' => $message]);
    }
    
} catch (Exception $e) {
    if ($isCLI) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
