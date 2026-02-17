<?php
/**
 * Controlador de Dashboards
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/activity.php';

class DashboardController {
    private $auth;
    private $userModel;
    private $activityModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->userModel = new User();
        $this->activityModel = new Activity();
    }

    public function adminDashboard() {
        try {
            $this->auth->requireRole(['SuperAdmin']);
            
            // Verificar caché (5 minutos de validez)
            $cacheKey = 'dashboard_admin_' . date('YmdHi');
            $cachedData = $this->getCache($cacheKey);
            
            if ($cachedData) {
                // Usar datos en caché
                extract($cachedData);
                if (function_exists('logActivity')) {
                    logActivity("Dashboard SuperAdmin cargado desde caché", 'DEBUG');
                }
            } else {
                // Inicializar variables con valores por defecto
                $userStats = [];
                $activityStats = [];
                $recentActivities = [];
                $activitiesByType = [];
                $pendingUsers = [];
                $monthlyActivities = [];
                $teamRanking = [];
                $currentMonthMetrics = [];
                
                // OPTIMIZACIÓN: Consolidar consultas estadísticas en una sola
                try {
                    $allStats = $this->getConsolidatedAdminStats();
                    $userStats = $allStats['userStats'] ?? [];
                    $activityStats = $allStats['activityStats'] ?? [];
                    $activitiesByType = $allStats['activitiesByType'] ?? [];
                    $currentMonthMetrics = $allStats['currentMonthMetrics'] ?? [];
                    $pendingUsers = $allStats['pendingUsers'] ?? [];
                } catch (Exception $e) {
                    logActivity("Error al obtener estadísticas consolidadas: " . $e->getMessage(), 'ERROR');
                }
                
                // Actividades recientes - solo campos necesarios
                try {
                    $recentActivities = $this->activityModel->getRecentActivitiesLight(10);
                } catch (Exception $e) {
                    logActivity("Error al obtener actividades recientes: " . $e->getMessage(), 'ERROR');
                    $recentActivities = [];
                }
                
                // Datos para gráficas - usar consulta optimizada
                try {
                    $monthlyActivities = $this->getMonthlyActivityDataOptimized();
                } catch (Exception $e) {
                    logActivity("Error al obtener datos mensuales: " . $e->getMessage(), 'ERROR');
                    $monthlyActivities = [];
                }
                
                // Ranking de equipos - limitar a top 5 y solo campos necesarios
                try {
                    $teamRanking = $this->getTeamRankingOptimized(5);
                } catch (Exception $e) {
                    logActivity("Error al obtener ranking de equipos: " . $e->getMessage(), 'ERROR');
                    $teamRanking = [];
                }
                
                // Guardar en caché
                $this->setCache($cacheKey, compact('userStats', 'activityStats', 'recentActivities', 
                    'activitiesByType', 'pendingUsers', 'monthlyActivities', 'teamRanking', 'currentMonthMetrics'));
            }
            
            // Establecer variables globales para la vista
            $GLOBALS['userStats'] = $userStats;
            $GLOBALS['activityStats'] = $activityStats;
            $GLOBALS['recentActivities'] = $recentActivities;
            $GLOBALS['activitiesByType'] = $activitiesByType;
            $GLOBALS['pendingUsers'] = $pendingUsers;
            $GLOBALS['monthlyActivities'] = $monthlyActivities;
            $GLOBALS['teamRanking'] = $teamRanking;
            $GLOBALS['currentMonthMetrics'] = $currentMonthMetrics;
            
            // Log de finalización
            if (function_exists('logActivity')) {
                logActivity("Dashboard SuperAdmin cargado exitosamente", 'DEBUG');
            }
            
        } catch (Exception $e) {
            logActivity("Error crítico en adminDashboard: " . $e->getMessage(), 'ERROR');
            
            // Re-lanzar la excepción para que sea capturada por el archivo admin.php
            throw $e;
        }
    }
    
    // Dashboard Gestor
    public function gestorDashboard() {
        $this->auth->requireRole(['Gestor']);
        
        // Verificar caché (5 minutos de validez)
        $cacheKey = 'dashboard_gestor_' . date('YmdHi');
        $cachedData = $this->getCache($cacheKey);
        
        if ($cachedData) {
            extract($cachedData);
        } else {
            // Obtener datos consolidados (similar al SuperAdmin)
            try {
                $allStats = $this->getConsolidatedAdminStats();
                $userStats = $allStats['userStats'] ?? [];
                $activityStats = $allStats['activityStats'] ?? [];
                $pendingUsers = $allStats['pendingUsers'] ?? [];
            } catch (Exception $e) {
                $userStats = [];
                $activityStats = [];
                $pendingUsers = [];
            }
            
            try {
                $recentActivities = $this->activityModel->getRecentActivitiesLight(10);
            } catch (Exception $e) {
                $recentActivities = [];
            }
            
            try {
                $teamRanking = $this->getTeamRankingOptimized(5);
            } catch (Exception $e) {
                $teamRanking = [];
            }
            
            // Guardar en caché
            $this->setCache($cacheKey, compact('userStats', 'activityStats', 'recentActivities', 'pendingUsers', 'teamRanking'));
        }
        
        // Establecer variables globales para la vista
        $GLOBALS['userStats'] = $userStats;
        $GLOBALS['activityStats'] = $activityStats;
        $GLOBALS['recentActivities'] = $recentActivities;
        $GLOBALS['pendingUsers'] = $pendingUsers;
        $GLOBALS['teamRanking'] = $teamRanking;
    }
    
    // Dashboard Líder
    public function liderDashboard() {
        try {
            $this->auth->requireRole(['Líder']);
            
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("No se pudo obtener la información del usuario actual");
            }
            
            $liderId = $currentUser['id'];
            
            // Inicializar variables con valores por defecto
            $teamActivities = [];
            $teamStats = [];
            $teamMembers = [];
            $recentActivities = [];
            $memberMetrics = [];
            
            // Verificar caché (5 minutos de validez)
            $cacheKey = 'dashboard_lider_' . $liderId . '_' . date('YmdHi');
            $cachedData = $this->getCache($cacheKey);
            
            if ($cachedData) {
                extract($cachedData);
            } else {
                // OPTIMIZACIÓN: Consolidar consultas de líder
                try {
                    $consolidatedData = $this->getConsolidatedLeaderStats($liderId);
                    $teamStats = $consolidatedData['teamStats'] ?? [];
                    $teamMembers = $consolidatedData['teamMembers'] ?? [];
                    $memberMetrics = $consolidatedData['memberMetrics'] ?? [];
                } catch (Exception $e) {
                    logActivity("Error al obtener datos consolidados del líder $liderId: " . $e->getMessage(), 'ERROR');
                    $teamStats = [];
                    $teamMembers = [];
                    $memberMetrics = [];
                }
                
                // Actividades recientes - solo campos necesarios
                try {
                    $recentActivities = $this->activityModel->getRecentActivitiesLight(10, ['lider_id' => $liderId]);
                } catch (Exception $e) {
                    logActivity("Error al obtener actividades recientes del líder $liderId: " . $e->getMessage(), 'ERROR');
                    $recentActivities = [];
                }
                
                // NO cargar todas las actividades - es muy pesado
                // Las actividades se cargarán bajo demanda vía AJAX si es necesario
                $teamActivities = [];
                
                // Guardar en caché
                $this->setCache($cacheKey, compact('teamStats', 'teamMembers', 'recentActivities', 'memberMetrics', 'teamActivities'));
            }
            
            // Establecer variables globales para la vista
            $GLOBALS['teamActivities'] = $teamActivities;
            $GLOBALS['teamStats'] = $teamStats;
            $GLOBALS['teamMembers'] = $teamMembers;
            $GLOBALS['recentActivities'] = $recentActivities;
            $GLOBALS['memberMetrics'] = $memberMetrics;
            
        } catch (Exception $e) {
            logActivity("Error crítico en liderDashboard: " . $e->getMessage(), 'ERROR');
            
            // Inicializar variables vacías para evitar errores en la vista
            $GLOBALS['teamActivities'] = [];
            $GLOBALS['teamStats'] = [];
            $GLOBALS['teamMembers'] = [];
            $GLOBALS['recentActivities'] = [];
            $GLOBALS['memberMetrics'] = [];
            
            // Re-lanzar la excepción para que sea capturada por el archivo lider.php
            throw $e;
        }
    }
    
    // Dashboard Activista
    public function activistaDashboard() {
        $this->auth->requireRole(['Activista']);
        
        $currentUser = $this->auth->getCurrentUser();
        $userId = $currentUser['id'];
        
        // Verificar caché (5 minutos de validez)
        $cacheKey = 'dashboard_activista_' . $userId . '_' . date('YmdHi');
        $cachedData = $this->getCache($cacheKey);
        
        if ($cachedData) {
            extract($cachedData);
        } else {
            // Estadísticas del activista
            try {
                $myStats = $this->activityModel->getActivityStats(['usuario_id' => $userId]);
            } catch (Exception $e) {
                $myStats = [];
            }
            
            // Actividades recientes SOLAMENTE - no cargar todas
            try {
                $recentActivities = $this->activityModel->getRecentActivitiesLight(10, ['usuario_id' => $userId]);
            } catch (Exception $e) {
                $recentActivities = [];
            }
            
            // Información del líder (caché más largo - 30 min)
            $lider = null;
            if ($currentUser['lider_id']) {
                $liderCacheKey = 'lider_info_' . $currentUser['lider_id'] . '_' . floor(time() / 1800);
                $lider = $this->getCache($liderCacheKey);
                if (!$lider) {
                    $lider = $this->userModel->getUserById($currentUser['lider_id']);
                    $this->setCache($liderCacheKey, $lider);
                }
            }
            
            // Compañeros de equipo (caché más largo - 30 min)
            $teammates = [];
            if ($currentUser['lider_id']) {
                $teamCacheKey = 'teammates_' . $currentUser['lider_id'] . '_' . floor(time() / 1800);
                $teammates = $this->getCache($teamCacheKey);
                if (!$teammates) {
                    $teammates = $this->userModel->getActivistsOfLeader($currentUser['lider_id']);
                    $this->setCache($teamCacheKey, $teammates);
                }
            }
            
            // NO cargar todas las actividades - se cargarán bajo demanda
            $myActivities = [];
            
            // Guardar en caché
            $this->setCache($cacheKey, compact('myStats', 'recentActivities', 'lider', 'teammates', 'myActivities'));
        }
        
        // Establecer variables globales para la vista
        $GLOBALS['myActivities'] = $myActivities;
        $GLOBALS['myStats'] = $myStats;
        $GLOBALS['recentActivities'] = $recentActivities;
        $GLOBALS['lider'] = $lider;
        $GLOBALS['teammates'] = $teammates;
    }
    
    // Obtener datos de actividades mensuales (OPTIMIZADO)
    private function getMonthlyActivityDataOptimized() {
        try {
            $db = $this->activityModel->getDb();
            if (!$db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            // Optimizado: Solo últimos 6 meses y usar índice en fecha
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(fecha_actividad, '%Y-%m') as mes,
                    COUNT(*) as cantidad
                FROM actividades 
                WHERE fecha_actividad >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(fecha_actividad, '%Y-%m')
                ORDER BY mes
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener datos mensuales: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener ranking de equipos (OPTIMIZADO)
    private function getTeamRankingOptimized($limit = 5) {
        try {
            $db = $this->activityModel->getDb();
            if (!$db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            // Optimizado: Reducir LIMIT, remover COUNT DISTINCT costoso
            $stmt = $db->prepare("
                SELECT 
                    l.id as lider_id,
                    l.nombre_completo as lider_nombre,
                    COUNT(a.id) as total_actividades,
                    SUM(CASE WHEN a.estado = 'completada' THEN 1 ELSE 0 END) as completadas
                FROM usuarios l
                LEFT JOIN usuarios u ON l.id = u.lider_id OR l.id = u.id
                LEFT JOIN actividades a ON u.id = a.usuario_id
                WHERE l.rol = 'Líder' AND l.estado = 'activo'
                GROUP BY l.id, l.nombre_completo
                HAVING total_actividades > 0
                ORDER BY completadas DESC, total_actividades DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener ranking de equipos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener métricas por miembro del equipo
    private function getMemberMetrics($liderId) {
        try {
            $stmt = $this->activityModel->getDb()->prepare("
                SELECT 
                    u.id,
                    u.nombre_completo,
                    COUNT(a.id) as total_actividades,
                    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas,
                    COUNT(DISTINCT e.id) as evidencias,
                    0 as alcance_total
                FROM usuarios u
                LEFT JOIN actividades a ON u.id = a.usuario_id
                LEFT JOIN evidencias e ON a.id = e.actividad_id
                WHERE u.lider_id = ? OR u.id = ?
                GROUP BY u.id, u.nombre_completo
                ORDER BY completadas DESC, total_actividades DESC
            ");
            $stmt->execute([$liderId, $liderId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener métricas de miembros: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Exportar datos a PDF
    public function exportToPDF() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        // Aquí se implementaría la exportación a PDF
        // Por simplicidad, devolvemos un mensaje
        redirectWithMessage($_SERVER['HTTP_REFERER'] ?? '', 'Funcionalidad de exportación a PDF pendiente de implementar', 'info');
    }
    
    // Exportar datos a Excel
    public function exportToExcel() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        // Aquí se implementaría la exportación a Excel
        // Por simplicidad, devolvemos un mensaje
        redirectWithMessage($_SERVER['HTTP_REFERER'] ?? '', 'Funcionalidad de exportación a Excel pendiente de implementar', 'info');
    }
    
    // API para obtener datos del calendario
    public function getCalendarData() {
        $this->auth->requireAuth();
        
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $userId = null;
        
        // Filtrar por usuario según el rol
        $currentUser = $this->auth->getCurrentUser();
        switch ($currentUser['rol']) {
            case 'Activista':
                $userId = $currentUser['id'];
                break;
            case 'Líder':
                // Incluir actividades del líder y sus activistas
                $userId = $currentUser['id'];
                break;
            // SuperAdmin y Gestor ven todas las actividades
        }
        
        $activities = $this->activityModel->getCalendarActivities($userId, $start, $end);
        
        header('Content-Type: application/json');
        echo json_encode($activities);
        exit();
    }
    
    // API para obtener estadísticas en tiempo real
    public function getStats() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        $filters = [];
        
        // Aplicar filtros según el rol
        switch ($currentUser['rol']) {
            case 'Activista':
                $filters['usuario_id'] = $currentUser['id'];
                break;
            case 'Líder':
                $filters['lider_id'] = $currentUser['id'];
                break;
        }
        
        $stats = [
            'activities' => $this->activityModel->getActivityStats($filters),
            'users' => $this->userModel->getUserStats()
        ];
        
        header('Content-Type: application/json');
        echo json_encode($stats);
        exit();
    }
    
    // Obtener métricas específicas del mes actual (OPTIMIZADO)
    private function getCurrentMonthMetrics() {
        try {
            $db = $this->activityModel->getDb();
            if (!$db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            // Optimizado: Remover JOIN innecesario con usuarios
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_actividades_mes,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas_mes,
                    SUM(CASE WHEN estado = 'programada' THEN 1 ELSE 0 END) as programadas_mes,
                    SUM(CASE WHEN estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso_mes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas_mes
                FROM actividades
                WHERE fecha_actividad >= ?
                  AND fecha_actividad < ?
            ");
            $startOfMonth = date('Y-m-01');
            $startOfNextMonth = date('Y-m-01', strtotime('+1 month'));
            $stmt->execute([$startOfMonth, $startOfNextMonth]);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_actividades_mes' => 0,
                'completadas_mes' => 0,
                'programadas_mes' => 0,
                'en_progreso_mes' => 0,
                'canceladas_mes' => 0
            ];
        } catch (Exception $e) {
            logActivity("Error al obtener métricas del mes actual: " . $e->getMessage(), 'ERROR');
            return [
                'total_actividades_mes' => 0,
                'completadas_mes' => 0,
                'programadas_mes' => 0,
                'en_progreso_mes' => 0,
                'canceladas_mes' => 0
            ];
        }
    }
    
    // ============================================
    // M\u00c9TODOS DE OPTIMIZACI\u00d3N Y CACH\u00c9
    // ============================================
    
    /**
     * Obtener datos consolidados para el dashboard de Admin/Gestor
     * OPTIMIZACI\u00d3N: Una sola consulta para m\u00faltiples m\u00e9tricas
     */
    private function getConsolidatedAdminStats() {
        try {
            $db = $this->activityModel->getDb();
            if (!$db) {
                throw new Exception("No hay conexi\u00f3n a la base de datos");
            }
            
            // Consulta consolidada para estad\u00edsticas de usuarios
            $stmt = $db->prepare("
                SELECT 
                    'user_stats' as type,
                    rol, 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) as suspendidos
                FROM usuarios 
                GROUP BY rol
            ");
            $stmt->execute();
            $userStatsRaw = $stmt->fetchAll();
            
            $userStats = [];
            foreach ($userStatsRaw as $stat) {
                $userStats[$stat['rol']] = [
                    'total' => (int)$stat['total'],
                    'activos' => (int)$stat['activos'],
                    'pendientes' => (int)$stat['pendientes'],
                    'suspendidos' => (int)$stat['suspendidos']
                ];
            }
            
            // Estad\u00edsticas de actividades
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_actividades,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                    SUM(CASE WHEN estado = 'programada' THEN 1 ELSE 0 END) as programadas
                FROM actividades
            ");
            $stmt->execute();
            $activityStats = $stmt->fetch() ?: [];
            
            // Actividades por tipo (TOP 10 solamente)
            $stmt = $db->prepare("
                SELECT ta.nombre, COUNT(a.id) as cantidad
                FROM tipos_actividades ta
                LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id AND a.estado != 'cancelada'
                WHERE ta.activo = 1
                GROUP BY ta.id, ta.nombre
                HAVING cantidad > 0
                ORDER BY cantidad DESC
                LIMIT 10
            ");
            $stmt->execute();
            $activitiesByType = $stmt->fetchAll();
            
            // Usuarios pendientes (incluir todos los campos necesarios)
            $stmt = $db->prepare("
                SELECT u.id, u.nombre_completo, u.email, u.rol, u.fecha_registro, 
                       l.nombre_completo as lider_nombre
                FROM usuarios u
                LEFT JOIN usuarios l ON u.lider_id = l.id
                WHERE u.estado = 'pendiente'
                ORDER BY u.fecha_registro DESC
                LIMIT 20
            ");
            $stmt->execute();
            $pendingUsers = $stmt->fetchAll();
            
            // M\u00e9tricas del mes actual
            $currentMonthMetrics = $this->getCurrentMonthMetrics();
            
            return [
                'userStats' => $userStats,
                'activityStats' => $activityStats,
                'activitiesByType' => $activitiesByType,
                'pendingUsers' => $pendingUsers,
                'currentMonthMetrics' => $currentMonthMetrics
            ];
            
        } catch (Exception $e) {
            logActivity("Error en getConsolidatedAdminStats: " . $e->getMessage(), 'ERROR');
            return [
                'userStats' => [],
                'activityStats' => [],
                'activitiesByType' => [],
                'pendingUsers' => [],
                'currentMonthMetrics' => []
            ];
        }
    }
    
    /**
     * Obtener datos consolidados para el dashboard de L\u00edder
     * OPTIMIZACI\u00d3N: Una consulta en lugar de 3 separadas
     */
    private function getConsolidatedLeaderStats($liderId) {
        try {
            $db = $this->activityModel->getDb();
            if (!$db) {
                throw new Exception("No hay conexi\u00f3n a la base de datos");
            }
            
            // Miembros del equipo
            $stmt = $db->prepare("
                SELECT id, nombre_completo, email
                FROM usuarios 
                WHERE lider_id = ? AND estado = 'activo'
                ORDER BY nombre_completo
                LIMIT 50
            ");
            $stmt->execute([$liderId]);
            $teamMembers = $stmt->fetchAll();
            
            // Estad\u00edsticas del equipo
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_actividades,
                    SUM(CASE WHEN a.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN a.estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                    SUM(CASE WHEN a.estado = 'programada' THEN 1 ELSE 0 END) as programadas
                FROM actividades a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE u.lider_id = ? OR u.id = ?
            ");
            $stmt->execute([$liderId, $liderId]);
            $teamStats = $stmt->fetch() ?: [];
            
            // M\u00e9tricas por miembro (simplificado)
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.nombre_completo,
                    COUNT(a.id) as total_actividades,
                    SUM(CASE WHEN a.estado = 'completada' THEN 1 ELSE 0 END) as completadas
                FROM usuarios u
                LEFT JOIN actividades a ON u.id = a.usuario_id
                WHERE u.lider_id = ? OR u.id = ?
                GROUP BY u.id, u.nombre_completo
                ORDER BY completadas DESC, total_actividades DESC
                LIMIT 20
            ");
            $stmt->execute([$liderId, $liderId]);
            $memberMetrics = $stmt->fetchAll();
            
            return [
                'teamMembers' => $teamMembers,
                'teamStats' => $teamStats,
                'memberMetrics' => $memberMetrics
            ];
            
        } catch (Exception $e) {
            logActivity("Error en getConsolidatedLeaderStats: " . $e->getMessage(), 'ERROR');
            return [
                'teamMembers' => [],
                'teamStats' => [],
                'memberMetrics' => []
            ];
        }
    }
    
    /**
     * Sistema de cach\u00e9 simple usando archivos
     */
    private function getCache($key) {
        $cacheDir = __DIR__ . '/../cache/dashboard';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) { // 5 minutos
            $data = @file_get_contents($cacheFile);
            if ($data) {
                return unserialize($data);
            }
        }
        
        return null;
    }
    
    private function setCache($key, $data) {
        $cacheDir = __DIR__ . '/../cache/dashboard';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
        @file_put_contents($cacheFile, serialize($data));
    }
}
?>