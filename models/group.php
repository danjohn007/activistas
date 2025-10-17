<?php
/**
 * Modelo de Grupos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Group {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            // Verificar que la conexión sea válida
            if (!$this->db) {
                throw new Exception("No se pudo establecer conexión a la base de datos");
            }
        } catch (Exception $e) {
            error_log("Group Model Error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    // Obtener todos los grupos
    public function getAllGroups() {
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, 
                       l.nombre_completo as lider_nombre,
                       COUNT(u.id) as miembros_count
                FROM grupos g
                LEFT JOIN usuarios l ON g.lider_id = l.id
                LEFT JOIN usuarios u ON u.grupo_id = g.id AND u.estado = 'activo'
                GROUP BY g.id, g.nombre, g.descripcion, g.lider_id, g.activo, g.fecha_creacion, g.fecha_actualizacion, l.nombre_completo
                ORDER BY g.nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener grupos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener grupo por ID
    public function getGroupById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, 
                       l.nombre_completo as lider_nombre,
                       COUNT(u.id) as miembros_count
                FROM grupos g
                LEFT JOIN usuarios l ON g.lider_id = l.id
                LEFT JOIN usuarios u ON u.grupo_id = g.id AND u.estado = 'activo'
                WHERE g.id = ?
                GROUP BY g.id
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener grupo por ID: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Crear nuevo grupo
    public function createGroup($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO grupos (nombre, descripcion, lider_id, activo) 
                VALUES (?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                !empty($data['lider_id']) ? $data['lider_id'] : null,
                isset($data['activo']) ? 1 : 0
            ]);
            
            if ($result) {
                $groupId = $this->db->lastInsertId();
                logActivity("Nuevo grupo creado: ID $groupId - {$data['nombre']}");
                return $groupId;
            }
            
            return false;
        } catch (Exception $e) {
            logActivity("Error al crear grupo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Actualizar grupo
    public function updateGroup($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE grupos 
                SET nombre = ?, descripcion = ?, lider_id = ?, activo = ? 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                !empty($data['lider_id']) ? $data['lider_id'] : null,
                isset($data['activo']) ? 1 : 0,
                $id
            ]);
            
            if ($result) {
                logActivity("Grupo actualizado: ID $id - {$data['nombre']}");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al actualizar grupo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Eliminar grupo
    public function deleteGroup($id) {
        try {
            // Primero verificar si hay usuarios asignados al grupo
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE grupo_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                // Si hay usuarios asignados, no eliminar pero desactivar
                $stmt = $this->db->prepare("UPDATE grupos SET activo = 0 WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    logActivity("Grupo desactivado (tiene usuarios asignados): ID $id");
                }
                
                return $result;
            } else {
                // Si no hay usuarios asignados, eliminar completamente
                $stmt = $this->db->prepare("DELETE FROM grupos WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    logActivity("Grupo eliminado: ID $id");
                }
                
                return $result;
            }
        } catch (Exception $e) {
            logActivity("Error al eliminar grupo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener grupos activos para selección
    public function getActiveGroups() {
        try {
            $stmt = $this->db->prepare("
                SELECT g.id, g.nombre, g.descripcion,
                       l.nombre_completo as lider_nombre,
                       COUNT(u.id) as miembros_count
                FROM grupos g
                LEFT JOIN usuarios l ON g.lider_id = l.id
                LEFT JOIN usuarios u ON u.grupo_id = g.id AND u.estado = 'activo'
                WHERE g.activo = 1 
                GROUP BY g.id, g.nombre, g.descripcion, l.nombre_completo
                ORDER BY g.nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener grupos activos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener miembros de un grupo
    public function getGroupMembers($groupId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, l.nombre_completo as lider_nombre
                FROM usuarios u
                LEFT JOIN usuarios l ON u.lider_id = l.id
                WHERE u.grupo_id = ? AND u.estado = 'activo'
                ORDER BY u.rol, u.nombre_completo
            ");
            $stmt->execute([$groupId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener miembros del grupo: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Asignar usuario a grupo
    public function assignUserToGroup($userId, $groupId) {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET grupo_id = ? WHERE id = ?");
            $result = $stmt->execute([$groupId, $userId]);
            
            if ($result) {
                logActivity("Usuario ID $userId asignado al grupo ID $groupId");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al asignar usuario a grupo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener estadísticas de grupos
    public function getGroupStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_grupos,
                    COUNT(CASE WHEN activo = 1 THEN 1 END) as grupos_activos,
                    COUNT(CASE WHEN lider_id IS NOT NULL THEN 1 END) as grupos_con_lider
                FROM grupos
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener estadísticas de grupos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Get best performers by group (Líderes and Activistas with highest compliance percentage)
     * Returns the top performers for each group
     * 
     * @param array $filters Optional filters: fecha_desde, fecha_hasta
     * @param int $page Page number for pagination
     * @param int $perPage Items per page (groups per page)
     * @return array Array with groups and their best performers
     */
    public function getBestPerformersByGroup($filters = [], $page = 1, $perPage = 20) {
        try {
            $params = [];
            $dateFilter = '';
            
            // Date filters
            if (!empty($filters['fecha_desde'])) {
                $dateFilter .= " AND a.fecha_creacion >= ?";
                $params[] = $filters['fecha_desde'] . ' 00:00:00';
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $dateFilter .= " AND a.fecha_creacion <= ?";
                $params[] = $filters['fecha_hasta'] . ' 23:59:59';
            }
            
            // Get all active groups with pagination
            $offset = ($page - 1) * $perPage;
            $groupsQuery = "
                SELECT g.id, g.nombre, g.descripcion
                FROM grupos g
                WHERE g.activo = 1
                ORDER BY g.nombre
                LIMIT ? OFFSET ?
            ";
            
            $groupStmt = $this->db->prepare($groupsQuery);
            $groupStmt->execute([$perPage, $offset]);
            $groups = $groupStmt->fetchAll();
            
            // Get total count for pagination
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM grupos WHERE activo = 1");
            $countStmt->execute();
            $totalGroups = $countStmt->fetch()['total'];
            
            // For each group, get best performers (leaders and activists)
            foreach ($groups as &$group) {
                // Build the query to get best performers in this group
                $performersQuery = "
                    SELECT 
                        u.id,
                        u.nombre_completo,
                        u.email,
                        u.rol,
                        COALESCE(completed.total, 0) as tareas_completadas,
                        COALESCE(pending.total, 0) as tareas_asignadas,
                        ROUND(
                            CASE 
                                WHEN COALESCE(pending.total, 0) > 0 THEN (COALESCE(completed.total, 0) * 100.0 / pending.total)
                                ELSE 0 
                            END, 2
                        ) as porcentaje_cumplimiento,
                        u.ranking_puntos
                    FROM usuarios u
                    LEFT JOIN (
                        SELECT 
                            usuario_id,
                            COUNT(*) as total
                        FROM actividades
                        WHERE estado = 'completada' AND autorizada = 1 $dateFilter
                        GROUP BY usuario_id
                    ) completed ON u.id = completed.usuario_id
                    LEFT JOIN (
                        SELECT 
                            usuario_id,
                            COUNT(*) as total
                        FROM actividades
                        WHERE tarea_pendiente = 1 $dateFilter
                        GROUP BY usuario_id
                    ) pending ON u.id = pending.usuario_id
                    WHERE u.grupo_id = ? AND u.estado = 'activo' AND u.id != 1
                    ORDER BY porcentaje_cumplimiento DESC, u.ranking_puntos DESC
                    LIMIT 5
                ";
                
                $performersStmt = $this->db->prepare($performersQuery);
                $performersParams = array_merge($params, $params, [$group['id']]);
                $performersStmt->execute($performersParams);
                $group['best_performers'] = $performersStmt->fetchAll();
                
                // Get leader separately if exists
                $leaderQuery = "
                    SELECT 
                        u.id,
                        u.nombre_completo,
                        u.email,
                        u.rol,
                        COALESCE(completed.total, 0) as tareas_completadas,
                        COALESCE(pending.total, 0) as tareas_asignadas,
                        ROUND(
                            CASE 
                                WHEN COALESCE(pending.total, 0) > 0 THEN (COALESCE(completed.total, 0) * 100.0 / pending.total)
                                ELSE 0 
                            END, 2
                        ) as porcentaje_cumplimiento,
                        u.ranking_puntos
                    FROM usuarios u
                    LEFT JOIN (
                        SELECT 
                            usuario_id,
                            COUNT(*) as total
                        FROM actividades
                        WHERE estado = 'completada' AND autorizada = 1 $dateFilter
                        GROUP BY usuario_id
                    ) completed ON u.id = completed.usuario_id
                    LEFT JOIN (
                        SELECT 
                            usuario_id,
                            COUNT(*) as total
                        FROM actividades
                        WHERE tarea_pendiente = 1 $dateFilter
                        GROUP BY usuario_id
                    ) pending ON u.id = pending.usuario_id
                    WHERE u.grupo_id = ? AND u.rol = 'Líder' AND u.estado = 'activo'
                    LIMIT 1
                ";
                
                $leaderStmt = $this->db->prepare($leaderQuery);
                $leaderParams = array_merge($params, $params, [$group['id']]);
                $leaderStmt->execute($leaderParams);
                $group['leader'] = $leaderStmt->fetch();
                
                // Calculate group statistics
                $group['total_members'] = count($group['best_performers']);
                $group['avg_compliance'] = 0;
                if (!empty($group['best_performers'])) {
                    $totalCompliance = array_sum(array_column($group['best_performers'], 'porcentaje_cumplimiento'));
                    $group['avg_compliance'] = round($totalCompliance / count($group['best_performers']), 2);
                }
            }
            
            return [
                'groups' => $groups,
                'total_groups' => $totalGroups,
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalGroups / $perPage)
            ];
        } catch (Exception $e) {
            logActivity("Error al obtener mejores por grupo: " . $e->getMessage(), 'ERROR');
            return [
                'groups' => [],
                'total_groups' => 0,
                'current_page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }
}
?>