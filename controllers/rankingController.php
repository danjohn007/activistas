<?php
/**
 * Controlador de Ranking
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/activity.php';
require_once __DIR__ . '/../models/user.php';

class RankingController {
    private $auth;
    private $activityModel;
    private $userModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->activityModel = new Activity();
        $this->userModel = new User();
    }
    
    // Mostrar ranking según el rol del usuario
    public function showRanking() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        $rankings = [];
        $title = '';
        $description = '';
        $showMonthSelector = false;
        $availablePeriods = [];
        $currentYear = null;
        $currentMonth = null;
        
        // Handle month selection for admins
        if (in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            $currentYear = intval($_GET['year'] ?? date('Y'));
            $currentMonth = intval($_GET['month'] ?? date('n'));
            $showMonthSelector = true;
            $availablePeriods = $this->activityModel->getAvailableRankingPeriods();
            
            // Check if we're viewing a historical month
            $isHistorical = ($currentYear < date('Y')) || 
                           ($currentYear == date('Y') && $currentMonth < date('n'));
            
            if ($isHistorical && !empty($availablePeriods)) {
                // Show historical monthly ranking
                $rankings = $this->activityModel->getMonthlyRanking($currentYear, $currentMonth, 50);
                $title = 'Ranking Mensual - ' . $this->getMonthName($currentMonth) . ' ' . $currentYear;
                $description = 'Ranking histórico de activistas para el mes seleccionado.';
            } else {
                // Show current ranking
                $rankings = $this->activityModel->getUserRanking(50);
                $title = 'Ranking Actual de Activistas';
                $description = 'Ranking actual de todos los activistas del sistema basado en tareas completadas.';
            }
        } else {
            switch ($currentUser['rol']) {
                case 'Líder':
                    // Líder puede ver ranking de su equipo
                    $rankings = $this->getTeamRanking($currentUser['id']);
                    $title = 'Ranking de Mi Equipo';
                    $description = 'Ranking de los activistas de tu equipo basado en tareas completadas y tiempo de respuesta.';
                    break;
                    
                case 'Activista':
                    // Activista puede ver ranking general (limitado)
                    $rankings = $this->activityModel->getUserRanking(20); // Top 20
                    $title = 'Ranking de Activistas';
                    $description = 'Ranking de activistas basado en tareas completadas y tiempo de respuesta. ¡Sube en el ranking completando más tareas!';
                    break;
                    
                default:
                    redirectWithMessage('dashboards/' . strtolower($currentUser['rol']) . '.php', 
                        'No tienes permisos para ver el ranking', 'error');
            }
        }
        
        // Agregar posición de cada usuario
        foreach ($rankings as $index => &$user) {
            $user['posicion'] = $index + 1;
        }
        
        // Si es activista, encontrar su posición en el ranking
        $userPosition = null;
        if ($currentUser['rol'] === 'Activista') {
            $userPosition = $this->findUserPosition($currentUser['id']);
        }
        
        include __DIR__ . '/../views/ranking/index.php';
    }
    
    // Obtener ranking del equipo de un líder con información detallada
    private function getTeamRanking($liderId) {
        try {
            $stmt = $this->activityModel->getDb()->prepare("
                SELECT 
                    u.id,
                    u.nombre_completo,
                    u.ranking_puntos,
                    COUNT(a.id) as actividades_completadas,
                    COUNT(at.id) as tareas_asignadas,
                    ROUND(
                        CASE 
                            WHEN COUNT(at.id) > 0 THEN (COUNT(a.id) * 100.0 / COUNT(at.id))
                            ELSE 0 
                        END, 2
                    ) as porcentaje_cumplimiento,
                    MIN(TIMESTAMPDIFF(MINUTE, a.fecha_creacion, a.hora_evidencia)) as mejor_tiempo_minutos,
                    AVG(TIMESTAMPDIFF(MINUTE, a.fecha_creacion, a.hora_evidencia)) as tiempo_promedio_minutos
                FROM usuarios u
                LEFT JOIN actividades a ON u.id = a.usuario_id AND a.estado = 'completada' AND a.autorizada = 1
                LEFT JOIN actividades at ON u.id = at.usuario_id AND at.tarea_pendiente = 1
                WHERE u.estado = 'activo' AND u.lider_id = ? AND u.id != 1
                GROUP BY u.id, u.nombre_completo, u.ranking_puntos
                ORDER BY u.ranking_puntos DESC
            ");
            $stmt->execute([$liderId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener ranking del equipo: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Encontrar la posición de un usuario específico en el ranking general
    private function findUserPosition($userId) {
        try {
            $stmt = $this->activityModel->getDb()->prepare("
                SELECT 
                    COUNT(*) + 1 as posicion
                FROM usuarios u1
                JOIN usuarios u2 ON u2.id = ? 
                WHERE u1.estado = 'activo' AND u1.id != 1
                AND u1.ranking_puntos > u2.ranking_puntos
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result['posicion'] ?? null;
        } catch (Exception $e) {
            logActivity("Error al encontrar posición del usuario: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Obtener estadísticas del ranking para el dashboard
    public function getRankingStats($filters = []) {
        try {
            $sql = "
                SELECT 
                    AVG(u.ranking_puntos) as promedio_puntos,
                    MAX(u.ranking_puntos) as max_puntos,
                    MIN(u.ranking_puntos) as min_puntos,
                    COUNT(DISTINCT u.id) as total_usuarios
                FROM usuarios u
                WHERE u.estado = 'activo' AND u.ranking_puntos > 0 AND u.id != 1
            ";
            
            $params = [];
            
            // Filtrar por líder si se especifica
            if (!empty($filters['lider_id'])) {
                $sql .= " AND u.lider_id = ?";
                $params[] = $filters['lider_id'];
            }
            
            $stmt = $this->activityModel->getDb()->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener estadísticas de ranking: " . $e->getMessage(), 'ERROR');
            return [
                'promedio_puntos' => 0,
                'max_puntos' => 0,
                'min_puntos' => 0,
                'total_usuarios' => 0
            ];
        }
    }
    
    // Helper method to get month name in Spanish
    private function getMonthName($month) {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $months[$month] ?? 'Mes Desconocido';
    }
}
?>