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
        
        // Añadir datos para las nuevas gráficas
        try {
            // Datos mensuales de actividades
            $stmt = $activityModel->getDb()->prepare("
                SELECT 
                    DATE_FORMAT(fecha_actividad, '%Y-%m') as mes,
                    COUNT(*) as cantidad
                FROM actividades 
                WHERE fecha_actividad >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(fecha_actividad, '%Y-%m')
                ORDER BY mes
            ");
            $stmt->execute();
            $response['data']['monthly_activities'] = $stmt->fetchAll();
            
            // Ranking de equipos
            $stmt = $activityModel->getDb()->prepare("
                SELECT 
                    l.nombre_completo as lider_nombre,
                    COUNT(a.id) as total_actividades,
                    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas,
                    COUNT(DISTINCT u.id) as miembros_equipo
                FROM usuarios l
                LEFT JOIN usuarios u ON l.id = u.lider_id
                LEFT JOIN actividades a ON (u.id = a.usuario_id OR l.id = a.usuario_id)
                WHERE l.rol = 'Líder' AND l.estado = 'activo'
                GROUP BY l.id, l.nombre_completo
                ORDER BY completadas DESC, total_actividades DESC
                LIMIT 10
            ");
            $stmt->execute();
            $response['data']['team_ranking'] = $stmt->fetchAll();
            
        } catch (Exception $e) {
            // En caso de error, incluir arrays vacíos
            $response['data']['monthly_activities'] = [];
            $response['data']['team_ranking'] = [];
        }
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