<?php
/**
 * API endpoint para obtener estadísticas en tiempo real
 * OPTIMIZADO: Implementa caché para reducir carga en base de datos
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir dependencias
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/cache.php';
    require_once __DIR__ . '/../../models/user.php';
    require_once __DIR__ . '/../../models/activity.php';
    
    // Verificar autenticación
    $auth = getAuth();
    
    // Verificar si el usuario está autenticado, si no devolver error específico
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Usuario no autenticado. Por favor, inicie sesión.',
            'error_code' => 'NOT_AUTHENTICATED',
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    $currentUser = $auth->getCurrentUser();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo obtener la información del usuario actual.',
            'error_code' => 'USER_NOT_FOUND',
            'timestamp' => date('c')
        ]);
        exit;
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
    
    // OPTIMIZACIÓN: Usar caché para reducir consultas a base de datos
    // Clave de caché única basada en rol y filtros del usuario
    $cacheKey = 'stats_' . $currentUser['rol'] . '_' . json_encode($filters);
    $cacheTTL = 30; // 30 segundos de caché (balanceo entre actualización y rendimiento)
    
    // Intentar obtener datos del caché
    $response = cache()->get($cacheKey, $currentUser['id']);
    
    if ($response === null) {
        // No hay caché, obtener datos de la base de datos
        $response = [
            'success' => true,
            'timestamp' => date('c'),
            'user_role' => $currentUser['rol'],
            'cached' => false,
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
            // Datos mensuales de actividades (últimos 12 meses incluyendo mes actual)
            $monthlySql = "
                SELECT 
                    DATE_FORMAT(a.fecha_actividad, '%Y-%m') as mes,
                    COUNT(*) as cantidad,
                    CASE WHEN DATE_FORMAT(a.fecha_actividad, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') 
                         THEN 1 ELSE 0 END as es_mes_actual
                FROM actividades a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.fecha_actividad >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
            $monthlyParams = [];
            
            // Aplicar filtros si no es SuperAdmin/Gestor
            if (!empty($filters['usuario_id'])) {
                $monthlySql .= " AND a.usuario_id = ?";
                $monthlyParams[] = $filters['usuario_id'];
            } elseif (!empty($filters['lider_id'])) {
                $monthlySql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $monthlyParams[] = $filters['lider_id'];
                $monthlyParams[] = $filters['lider_id'];
            }
            
            $monthlySql .= " GROUP BY DATE_FORMAT(a.fecha_actividad, '%Y-%m') ORDER BY mes";
            
            $stmt = $activityModel->getDb()->prepare($monthlySql);
            $stmt->execute($monthlyParams);
            $monthlyResults = $stmt->fetchAll();
            $response['data']['monthly_activities'] = $monthlyResults;
            
            // Añadir métricas específicas del mes actual
            $currentMonthSql = "
                SELECT 
                    COUNT(*) as total_actividades_mes,
                    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas_mes,
                    COUNT(CASE WHEN a.estado = 'programada' THEN 1 END) as programadas_mes,
                    COUNT(CASE WHEN a.estado = 'en_progreso' THEN 1 END) as en_progreso_mes
                FROM actividades a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE DATE_FORMAT(a.fecha_actividad, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
            $currentMonthParams = [];
            
            // Aplicar los mismos filtros para el mes actual
            if (!empty($filters['usuario_id'])) {
                $currentMonthSql .= " AND a.usuario_id = ?";
                $currentMonthParams[] = $filters['usuario_id'];
            } elseif (!empty($filters['lider_id'])) {
                $currentMonthSql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $currentMonthParams[] = $filters['lider_id'];
                $currentMonthParams[] = $filters['lider_id'];
            }
            
            $stmt = $activityModel->getDb()->prepare($currentMonthSql);
            $stmt->execute($currentMonthParams);
            $currentMonthData = $stmt->fetch();
            $response['data']['current_month_metrics'] = $currentMonthData;
            
            // Ranking de equipos (solo para SuperAdmin/Gestor)
            if (empty($filters)) {
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
            } else {
                // Para roles específicos, no mostrar ranking global
                $response['data']['team_ranking'] = [];
            }
            
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
    
        // Guardar en caché
        cache()->set($cacheKey, $response, $cacheTTL, $currentUser['id']);
    } else {
        // Datos obtenidos del caché, actualizar timestamp y flag
        $response['timestamp'] = date('c');
        $response['cached'] = true;
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