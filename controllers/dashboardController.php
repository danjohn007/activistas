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
        $this->auth->requireRole(['SuperAdmin']);
        
        // Métricas globales
        $userStats = $this->userModel->getUserStats();
        $activityStats = $this->activityModel->getActivityStats();
        $recentActivities = $this->activityModel->getActivities(['limit' => 10]);
        $activitiesByType = $this->activityModel->getActivitiesByType();
        $pendingUsers = $this->userModel->getPendingUsers();
        
        // Datos para gráficas
        $monthlyActivities = $this->getMonthlyActivityData();
        $teamRanking = $this->getTeamRanking();
        
        include __DIR__ . '/../views/dashboards/admin.php';
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
        
        include __DIR__ . '/../views/dashboards/gestor.php';
    }
    
    // Dashboard Líder
    public function liderDashboard() {
        $this->auth->requireRole(['Líder']);
        
        $currentUser = $this->auth->getCurrentUser();
        $liderId = $currentUser['id'];
        
        // Actividades propias y de sus activistas
        $teamActivities = $this->activityModel->getActivities(['lider_id' => $liderId]);
        $teamStats = $this->activityModel->getActivityStats(['lider_id' => $liderId]);
        $teamMembers = $this->userModel->getActivistsOfLeader($liderId);
        
        // Actividades recientes del equipo
        $recentActivities = $this->activityModel->getActivities([
            'lider_id' => $liderId,
            'limit' => 10
        ]);
        
        // Métricas por miembro del equipo
        $memberMetrics = $this->getMemberMetrics($liderId);
        
        include __DIR__ . '/../views/dashboards/lider.php';
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
        
        include __DIR__ . '/../views/dashboards/activista.php';
    }
    
    // Obtener datos de actividades mensuales
    private function getMonthlyActivityData() {
        try {
            $stmt = $this->activityModel->db->prepare("
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
            $stmt = $this->activityModel->db->prepare("
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
            $stmt = $this->activityModel->db->prepare("
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
}
?>