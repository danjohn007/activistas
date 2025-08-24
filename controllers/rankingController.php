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
        
        switch ($currentUser['rol']) {
            case 'SuperAdmin':
            case 'Gestor':
                // SuperAdmin y Gestor pueden ver ranking completo
                $rankings = $this->activityModel->getUserRanking(50); // Top 50
                $title = 'Ranking General de Activistas';
                $description = 'Ranking completo de todos los activistas del sistema basado en tareas completadas y tiempo de respuesta.';
                break;
                
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
    
    // Obtener ranking del equipo de un líder
    private function getTeamRanking($liderId) {
        try {
            $stmt = $this->activityModel->getDb()->prepare("
                SELECT 
                    u.nombre_completo,
                    u.ranking_puntos,
                    COUNT(a.id) as actividades_completadas,
                    MIN(TIMESTAMPDIFF(MINUTE, a.fecha_creacion, a.hora_evidencia)) as mejor_tiempo_minutos
                FROM usuarios u
                LEFT JOIN actividades a ON u.id = a.usuario_id AND a.estado = 'completada'
                WHERE u.estado = 'activo' AND u.lider_id = ?
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
                WHERE u1.estado = 'activo' 
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
                WHERE u.estado = 'activo' AND u.ranking_puntos > 0
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
}
?>