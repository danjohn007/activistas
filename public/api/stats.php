<?php
/**
 * API endpoint para obtener estadísticas en tiempo real
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir dependencias
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../models/user.php';
    require_once __DIR__ . '/../../models/activity.php';
    
    // Verificar autenticación
    $auth = getAuth();
    $auth->requireAuth();
    
    $currentUser = $auth->getCurrentUser();
    if (!$currentUser) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Inicializar modelos
    $userModel = new User();
    $activityModel = new Activity();
    
    // Preparar filtros según el rol del usuario
    $filters = [];
    switch ($currentUser['rol']) {
        case 'Activista':
            $filters['usuario_id'] = $currentUser['id'];
            break;
        case 'Líder':
            $filters['lider_id'] = $currentUser['id'];
            break;
        // SuperAdmin y Gestor ven todas las estadísticas
    }
    
    // Obtener datos reales de la base de datos
    $response = [
        'success' => true,
        'timestamp' => date('c'),
        'user_role' => $currentUser['rol'],
        'data' => [
            'activity_stats' => $activityModel->getActivityStats($filters),
            'activities_by_type' => $activityModel->getActivitiesByType($filters),
            'user_stats' => $userModel->getUserStats()
        ]
    ];
    
    // Solo incluir datos de usuarios para admin/gestor
    if (in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
        $response['data']['pending_users'] = $userModel->getPendingUsers();
    }
    
    // Para líderes, incluir métricas del equipo
    if ($currentUser['rol'] === 'Líder') {
        $response['data']['team_members'] = $userModel->getActivistsOfLeader($currentUser['id']);
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>