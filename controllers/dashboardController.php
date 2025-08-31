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
    
    // Dashboard SuperAdmin
    public function adminDashboard() {
        try {
            $this->auth->requireRole(['SuperAdmin']);
            
            // Log de inicio para debugging
            if (function_exists('logActivity')) {
                logActivity("Iniciando carga de dashboard SuperAdmin", 'DEBUG');
            }
            
            // Inicializar variables con valores por defecto
            $userStats = [];
            $activityStats = [];
            $recentActivities = [];
            $activitiesByType = [];
            $pendingUsers = [];
            $monthlyActivities = [];
            $teamRanking = [];
            
            // Métricas globales con manejo de errores
            try {
                $userStats = $this->userModel->getUserStats();
                if (function_exists('logActivity')) {
                    logActivity("Estadísticas de usuarios obtenidas: " . count($userStats) . " roles", 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener estadísticas de usuarios: " . $e->getMessage(), 'ERROR');
                $userStats = [];
            }
            
            try {
                $activityStats = $this->activityModel->getActivityStats();
                if (function_exists('logActivity')) {
                    logActivity("Estadísticas de actividades obtenidas", 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener estadísticas de actividades: " . $e->getMessage(), 'ERROR');
                $activityStats = [];
            }
            
            try {
                $recentActivities = $this->activityModel->getActivities(['limit' => 10]);
                if (function_exists('logActivity')) {
                    logActivity("Actividades recientes obtenidas: " . count($recentActivities), 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener actividades recientes: " . $e->getMessage(), 'ERROR');
                $recentActivities = [];
            }
            
            try {
                $activitiesByType = $this->activityModel->getActivitiesByType();
                if (function_exists('logActivity')) {
                    logActivity("Actividades por tipo obtenidas: " . count($activitiesByType) . " tipos", 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener actividades por tipo: " . $e->getMessage(), 'ERROR');
                $activitiesByType = [];
                
                // Si es error de base de datos, registrar específicamente
                if (strpos($e->getMessage(), 'No such file or directory') !== false) {
                    logActivity("Error de conexión a base de datos detectado", 'ERROR');
                }
            }
            
            try {
                $pendingUsers = $this->userModel->getPendingUsers();
                if (function_exists('logActivity')) {
                    logActivity("Usuarios pendientes obtenidos: " . count($pendingUsers), 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener usuarios pendientes: " . $e->getMessage(), 'ERROR');
                $pendingUsers = [];
            }
            
            // Datos para gráficas con manejo de errores
            try {
                $monthlyActivities = $this->getMonthlyActivityData();
                if (function_exists('logActivity')) {
                    logActivity("Datos mensuales obtenidos: " . count($monthlyActivities) . " meses", 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener datos mensuales: " . $e->getMessage(), 'ERROR');
                $monthlyActivities = [];
            }
            
            try {
                $teamRanking = $this->getTeamRanking();
                if (function_exists('logActivity')) {
                    logActivity("Ranking de equipos obtenido: " . count($teamRanking) . " equipos", 'DEBUG');
                }
            } catch (Exception $e) {
                logActivity("Error al obtener ranking de equipos: " . $e->getMessage(), 'ERROR');
                $teamRanking = [];
            }
            
            // Establecer variables globales para la vista
            $GLOBALS['userStats'] = $userStats;
            $GLOBALS['activityStats'] = $activityStats;
            $GLOBALS['recentActivities'] = $recentActivities;
            $GLOBALS['activitiesByType'] = $activitiesByType;
            $GLOBALS['pendingUsers'] = $pendingUsers;
            $GLOBALS['monthlyActivities'] = $monthlyActivities;
            $GLOBALS['teamRanking'] = $teamRanking;
            
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
        
        // Similar al SuperAdmin pero sin configuración crítica
        $userStats = $this->userModel->getUserStats();
        $activityStats = $this->activityModel->getActivityStats();
        $recentActivities = $this->activityModel->getActivities(['limit' => 10]);
        $pendingUsers = $this->userModel->getPendingUsers();
        $teamRanking = $this->getTeamRanking();
        
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
            
            // Actividades propias y de sus activistas con manejo de errores
            try {
                $teamActivities = $this->activityModel->getActivities(['lider_id' => $liderId]);
            } catch (Exception $e) {
                logActivity("Error al obtener actividades del equipo del líder $liderId: " . $e->getMessage(), 'ERROR');
                $teamActivities = [];
            }
            
            try {
                $teamStats = $this->activityModel->getActivityStats(['lider_id' => $liderId]);
            } catch (Exception $e) {
                logActivity("Error al obtener estadísticas del equipo del líder $liderId: " . $e->getMessage(), 'ERROR');
                $teamStats = [];
            }
            
            try {
                $teamMembers = $this->userModel->getActivistsOfLeader($liderId);
            } catch (Exception $e) {
                logActivity("Error al obtener miembros del equipo del líder $liderId: " . $e->getMessage(), 'ERROR');
                $teamMembers = [];
            }
            
            // Actividades recientes del equipo
            try {
                $recentActivities = $this->activityModel->getActivities([
                    'lider_id' => $liderId,
                    'limit' => 10
                ]);
            } catch (Exception $e) {
                logActivity("Error al obtener actividades recientes del líder $liderId: " . $e->getMessage(), 'ERROR');
                $recentActivities = [];
            }
            
            // Métricas por miembro del equipo
            try {
                $memberMetrics = $this->getMemberMetrics($liderId);
            } catch (Exception $e) {
                logActivity("Error al obtener métricas de miembros del líder $liderId: " . $e->getMessage(), 'ERROR');
                $memberMetrics = [];
            }
            
            // Tareas pendientes del equipo
            try {
                $teamPendingTasks = $this->getTeamPendingTasks($liderId);
            } catch (Exception $e) {
                logActivity("Error al obtener tareas pendientes del equipo del líder $liderId: " . $e->getMessage(), 'ERROR');
                $teamPendingTasks = [];
            }
            
            // Establecer variables globales para la vista
            $GLOBALS['teamActivities'] = $teamActivities;
            $GLOBALS['teamStats'] = $teamStats;
            $GLOBALS['teamMembers'] = $teamMembers;
            $GLOBALS['recentActivities'] = $recentActivities;
            $GLOBALS['memberMetrics'] = $memberMetrics;
            $GLOBALS['teamPendingTasks'] = $teamPendingTasks;
            
        } catch (Exception $e) {
            logActivity("Error crítico en liderDashboard: " . $e->getMessage(), 'ERROR');
            
            // Inicializar variables vacías para evitar errores en la vista
            $GLOBALS['teamActivities'] = [];
            $GLOBALS['teamStats'] = [];
            $GLOBALS['teamMembers'] = [];
            $GLOBALS['recentActivities'] = [];
            $GLOBALS['memberMetrics'] = [];
            $GLOBALS['teamPendingTasks'] = [];
            
            // Re-lanzar la excepción para que sea capturada por el archivo lider.php
            throw $e;
        }
    }
    
    // Dashboard Activista
    public function activistaDashboard() {
        $this->auth->requireRole(['Activista']);
        
        $currentUser = $this->auth->getCurrentUser();
        $userId = $currentUser['id'];
        
        // Actividades del activista
        $myActivities = $this->activityModel->getActivities(['usuario_id' => $userId]);
        $myStats = $this->activityModel->getActivityStats(['usuario_id' => $userId]);
        
        // Actividades recientes
        $recentActivities = $this->activityModel->getActivities([
            'usuario_id' => $userId,
            'limit' => 10
        ]);
        
        // Tareas pendientes del activista
        $pendingTasks = $this->activityModel->getPendingTasks($userId);
        
        // Información del líder
        $lider = null;
        if ($currentUser['lider_id']) {
            $lider = $this->userModel->getUserById($currentUser['lider_id']);
        }
        
        // Compañeros de equipo
        $teammates = [];
        if ($currentUser['lider_id']) {
            $teammates = $this->userModel->getActivistsOfLeader($currentUser['lider_id']);
        }
        
        // Establecer variables globales para la vista
        $GLOBALS['myActivities'] = $myActivities;
        $GLOBALS['myStats'] = $myStats;
        $GLOBALS['recentActivities'] = $recentActivities;
        $GLOBALS['pendingTasks'] = $pendingTasks;
        $GLOBALS['lider'] = $lider;
        $GLOBALS['teammates'] = $teammates;
    }
    
    // Obtener datos de actividades mensuales
    private function getMonthlyActivityData() {
        try {
            $stmt = $this->activityModel->getDb()->prepare("
                SELECT 
                    DATE_FORMAT(fecha_actividad, '%Y-%m') as mes,
                    COUNT(*) as cantidad
                FROM actividades 
                WHERE fecha_actividad >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
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
    
    // Obtener ranking de equipos
    private function getTeamRanking() {
        try {
            $stmt = $this->activityModel->getDb()->prepare("
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
                    COUNT(e.id) as evidencias,
                    COALESCE(SUM(a.alcance_estimado), 0) as alcance_total
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
    
    // Obtener tareas pendientes del equipo para el líder
    private function getTeamPendingTasks($liderId) {
        return $this->activityModel->getTeamPendingTasks($liderId);
    }
}
?>