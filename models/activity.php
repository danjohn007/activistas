<?php
/**
 * Modelo de Actividades
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Activity {
    protected $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            // Verificar que la conexión sea válida
            if (!$this->db) {
                throw new Exception("No se pudo establecer conexión a la base de datos");
            }
        } catch (Exception $e) {
            error_log("Activity Model Error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    // Getter method for database connection
    public function getDb() {
        return $this->db;
    }
    
    // Crear nueva actividad
    public function createActivity($data) {
        try {
            // Determine if this should be a pending task
            $tarea_pendiente = 0;
            $solicitante_id = null;
            
            // If created by SuperAdmin, Gestor or Líder and has a solicitante_id, mark as pending task
            if (isset($data['user_role']) && in_array($data['user_role'], ['SuperAdmin', 'Gestor', 'Líder']) 
                && isset($data['solicitante_id'])) {
                $tarea_pendiente = 1;
                $solicitante_id = $data['solicitante_id'];
            }
            // If created by SuperAdmin, Gestor or Líder for themselves, also mark as pending task for others
            elseif (isset($data['user_role']) && in_array($data['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])) {
                $tarea_pendiente = 1;
                $solicitante_id = $data['usuario_id'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad, tarea_pendiente, solicitante_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['usuario_id'],
                $data['tipo_actividad_id'],
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['fecha_actividad'],
                $tarea_pendiente,
                $solicitante_id
            ]);
            
            if ($result) {
                $activityId = $this->db->lastInsertId();
                logActivity("Nueva actividad creada: ID $activityId por usuario {$data['usuario_id']}");
                return $activityId;
            }
            
            return false;
        } catch (Exception $e) {
            logActivity("Error al crear actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener actividades
    public function getActivities($filters = []) {
        try {
            // Verificar conexión antes de proceder
            if (!$this->db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            $sql = "SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre,
                           s.nombre_completo as solicitante_nombre, u.correo as usuario_correo, u.telefono as usuario_telefono
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
                    LEFT JOIN usuarios s ON a.solicitante_id = s.id
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            if (!empty($filters['lider_id'])) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['lider_id'];
                $params[] = $filters['lider_id'];
            }
            
            if (!empty($filters['tipo_actividad_id'])) {
                $sql .= " AND a.tipo_actividad_id = ?";
                $params[] = $filters['tipo_actividad_id'];
            }
            
            if (!empty($filters['estado'])) {
                $sql .= " AND a.estado = ?";
                $params[] = $filters['estado'];
            }
            
            if (!empty($filters['fecha_desde'])) {
                $sql .= " AND a.fecha_actividad >= ?";
                $params[] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $sql .= " AND a.fecha_actividad <= ?";
                $params[] = $filters['fecha_hasta'];
            }
            
            // Advanced search filters
            if (!empty($filters['search_title'])) {
                $sql .= " AND a.titulo LIKE ?";
                $params[] = '%' . $filters['search_title'] . '%';
            }
            
            if (!empty($filters['search_name'])) {
                $sql .= " AND u.nombre_completo LIKE ?";
                $params[] = '%' . $filters['search_name'] . '%';
            }
            
            if (!empty($filters['search_email'])) {
                $sql .= " AND u.correo LIKE ?";
                $params[] = '%' . $filters['search_email'] . '%';
            }
            
            if (!empty($filters['search_phone'])) {
                $sql .= " AND u.telefono LIKE ?";
                $params[] = '%' . $filters['search_phone'] . '%';
            }
            
            $sql .= " ORDER BY a.fecha_actividad DESC, a.fecha_creacion DESC";
            
            // Add pagination support
            if (!empty($filters['page']) && !empty($filters['per_page'])) {
                $page = intval($filters['page']);
                $perPage = intval($filters['per_page']);
                $offset = ($page - 1) * $perPage;
                $sql .= " LIMIT " . $perPage . " OFFSET " . $offset;
            } elseif (!empty($filters['limit'])) {
                $sql .= " LIMIT " . intval($filters['limit']);
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener actividades: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Count total activities for pagination
    public function countActivities($filters = []) {
        try {
            // Verificar conexión antes de proceder
            if (!$this->db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            $sql = "SELECT COUNT(*) as total
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
                    LEFT JOIN usuarios s ON a.solicitante_id = s.id
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            if (!empty($filters['lider_id'])) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['lider_id'];
                $params[] = $filters['lider_id'];
            }
            
            if (!empty($filters['tipo_actividad_id'])) {
                $sql .= " AND a.tipo_actividad_id = ?";
                $params[] = $filters['tipo_actividad_id'];
            }
            
            if (!empty($filters['estado'])) {
                $sql .= " AND a.estado = ?";
                $params[] = $filters['estado'];
            }
            
            if (!empty($filters['fecha_desde'])) {
                $sql .= " AND a.fecha_actividad >= ?";
                $params[] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $sql .= " AND a.fecha_actividad <= ?";
                $params[] = $filters['fecha_hasta'];
            }
            
            // Advanced search filters
            if (!empty($filters['search_title'])) {
                $sql .= " AND a.titulo LIKE ?";
                $params[] = '%' . $filters['search_title'] . '%';
            }
            
            if (!empty($filters['search_name'])) {
                $sql .= " AND u.nombre_completo LIKE ?";
                $params[] = '%' . $filters['search_name'] . '%';
            }
            
            if (!empty($filters['search_email'])) {
                $sql .= " AND u.correo LIKE ?";
                $params[] = '%' . $filters['search_email'] . '%';
            }
            
            if (!empty($filters['search_phone'])) {
                $sql .= " AND u.telefono LIKE ?";
                $params[] = '%' . $filters['search_phone'] . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            logActivity("Error al contar actividades: " . $e->getMessage(), 'ERROR');
            return 0;
        }
    }
    
    // Obtener actividad por ID
    public function getActivityById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre,
                       s.nombre_completo as solicitante_nombre
                FROM actividades a 
                JOIN usuarios u ON a.usuario_id = u.id 
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
                LEFT JOIN usuarios s ON a.solicitante_id = s.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener actividad por ID: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Actualizar actividad
    public function updateActivity($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['titulo'])) {
                $fields[] = "titulo = ?";
                $params[] = $data['titulo'];
            }
            
            if (isset($data['descripcion'])) {
                $fields[] = "descripcion = ?";
                $params[] = $data['descripcion'];
            }
            
            if (isset($data['fecha_actividad'])) {
                $fields[] = "fecha_actividad = ?";
                $params[] = $data['fecha_actividad'];
            }
            
            if (isset($data['lugar'])) {
                $fields[] = "lugar = ?";
                $params[] = $data['lugar'];
            }
            
            if (isset($data['estado'])) {
                $fields[] = "estado = ?";
                $params[] = $data['estado'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $id;
            $sql = "UPDATE actividades SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity("Actividad ID $id actualizada");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al actualizar actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener tipos de actividades
    public function getActivityTypes() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM tipos_actividades WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener tipos de actividades: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Agregar evidencia a actividad
    public function addEvidence($activityId, $type, $file = null, $content = null) {
        try {
            // Check if evidence is already blocked
            $stmt = $this->db->prepare("
                SELECT bloqueada FROM evidencias 
                WHERE actividad_id = ? AND bloqueada = 1 
                LIMIT 1
            ");
            $stmt->execute([$activityId]);
            $isBlocked = $stmt->fetch();
            
            if ($isBlocked) {
                return ['success' => false, 'error' => 'Las evidencias de esta actividad ya han sido bloqueadas y no pueden modificarse'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO evidencias (actividad_id, tipo_evidencia, archivo, contenido, fecha_subida, bloqueada)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 1)
            ");
            
            $result = $stmt->execute([$activityId, $type, $file, $content]);
            
            if ($result) {
                $evidenceId = $this->db->lastInsertId();
                
                // Update activity with evidence timestamp and mark as completed
                $this->updateActivityEvidenceTimestamp($activityId);
                
                // Update rankings after evidence is uploaded
                $this->updateUserRankings();
                
                logActivity("Evidencia agregada: ID $evidenceId para actividad $activityId");
                return ['success' => true, 'evidenceId' => $evidenceId];
            }
            
            return ['success' => false, 'error' => 'Error al guardar la evidencia'];
        } catch (Exception $e) {
            logActivity("Error al agregar evidencia: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }
    
    // Update activity with evidence timestamp
    private function updateActivityEvidenceTimestamp($activityId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE actividades 
                SET hora_evidencia = CURRENT_TIMESTAMP, estado = 'completada'
                WHERE id = ?
            ");
            $stmt->execute([$activityId]);
        } catch (Exception $e) {
            logActivity("Error al actualizar timestamp de evidencia: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Check if evidence can be modified
    public function canModifyEvidence($activityId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM evidencias 
                WHERE actividad_id = ? AND bloqueada = 1
            ");
            $stmt->execute([$activityId]);
            $result = $stmt->fetch();
            
            return $result['count'] == 0;
        } catch (Exception $e) {
            logActivity("Error al verificar modificación de evidencia: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener evidencias de actividad
    public function getActivityEvidence($activityId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM evidencias 
                WHERE actividad_id = ? 
                ORDER BY fecha_subida DESC
            ");
            $stmt->execute([$activityId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener evidencias: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener estadísticas de actividades
    public function getActivityStats($filters = []) {
        try {
            // Verificar conexión antes de proceder
            if (!$this->db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            $sql = "SELECT 
                        COUNT(*) as total_actividades,
                        COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas,
                        COUNT(CASE WHEN a.estado = 'en_progreso' THEN 1 END) as en_progreso,
                        COUNT(CASE WHEN a.estado = 'programada' THEN 1 END) as programadas
                    FROM actividades a
                    JOIN usuarios u ON a.usuario_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            if (!empty($filters['lider_id'])) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['lider_id'];
                $params[] = $filters['lider_id'];
            }
            
            if (!empty($filters['fecha_desde'])) {
                $sql .= " AND a.fecha_actividad >= ?";
                $params[] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $sql .= " AND a.fecha_actividad <= ?";
                $params[] = $filters['fecha_hasta'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener estadísticas de actividades: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener actividades por tipo
    public function getActivitiesByType($filters = []) {
        try {
            // Verificar conexión antes de proceder
            if (!$this->db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            $sql = "SELECT ta.nombre, COUNT(a.id) as cantidad
                    FROM tipos_actividades ta
                    LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id";
            
            // Agregar JOIN con usuarios si necesitamos filtrar por líder
            if (!empty($filters['lider_id'])) {
                $sql .= " LEFT JOIN usuarios u ON a.usuario_id = u.id";
            }
            
            $params = [];
            $where = [];
            
            if (!empty($filters['usuario_id'])) {
                $where[] = "a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            if (!empty($filters['lider_id'])) {
                $where[] = "(a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['lider_id'];
                $params[] = $filters['lider_id'];
            }
            
            if (!empty($filters['fecha_desde'])) {
                $where[] = "a.fecha_actividad >= ?";
                $params[] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $where[] = "a.fecha_actividad <= ?";
                $params[] = $filters['fecha_hasta'];
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            $sql .= " GROUP BY ta.id, ta.nombre ORDER BY cantidad DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener actividades por tipo: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener actividades para calendario
    public function getCalendarActivities($userId = null, $start = null, $end = null) {
        try {
            $sql = "SELECT a.id, a.titulo as title, a.fecha_actividad as start, a.estado, 
                           u.nombre_completo as usuario_nombre
                    FROM actividades a
                    JOIN usuarios u ON a.usuario_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($userId) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }
            
            if ($start) {
                $sql .= " AND a.fecha_actividad >= ?";
                $params[] = $start;
            }
            
            if ($end) {
                $sql .= " AND a.fecha_actividad <= ?";
                $params[] = $end;
            }
            
            $sql .= " ORDER BY a.fecha_actividad";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $activities = $stmt->fetchAll();
            
            // Formatear para FullCalendar
            foreach ($activities as &$activity) {
                $activity['color'] = $this->getActivityColor($activity['estado']);
                $activity['url'] = '/public/activity_detail.php?id=' . $activity['id'];
            }
            
            return $activities;
        } catch (Exception $e) {
            logActivity("Error al obtener actividades del calendario: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Calculate and update user rankings
    public function updateUserRankings() {
        try {
            // Get all users with completed activities
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    COUNT(a.id) as tareas_completadas,
                    MIN(TIMESTAMPDIFF(MINUTE, a.fecha_creacion, a.hora_evidencia)) as mejor_tiempo_minutos
                FROM usuarios u
                LEFT JOIN actividades a ON u.id = a.usuario_id 
                WHERE a.estado = 'completada' AND a.hora_evidencia IS NOT NULL
                GROUP BY u.id
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            if (empty($users)) {
                return;
            }
            
            // Find the best response time for comparison
            $bestTime = min(array_column($users, 'mejor_tiempo_minutos'));
            
            // Calculate rankings
            foreach ($users as $user) {
                $puntosTareas = $user['tareas_completadas'] * 200; // 200 points per completed task
                
                // Calculate time points (800 for best time, decreasing for others)
                $puntostiempo = 0;
                if ($user['mejor_tiempo_minutos'] == $bestTime) {
                    $puntostiempo = 800;
                } else {
                    // Each position away from best time reduces points
                    $positionPenalty = 0;
                    foreach ($users as $otherUser) {
                        if ($otherUser['mejor_tiempo_minutos'] < $user['mejor_tiempo_minutos']) {
                            $positionPenalty++;
                        }
                    }
                    $puntostiempo = 800 - $positionPenalty;
                    // Allow negative values as specified
                }
                
                $puntosTotal = $puntosTareas + $puntostiempo;
                
                // Update user ranking
                $updateStmt = $this->db->prepare("
                    UPDATE usuarios 
                    SET ranking_puntos = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$puntosTotal, $user['id']]);
            }
            
            logActivity("Rankings actualizados para " . count($users) . " usuarios");
        } catch (Exception $e) {
            logActivity("Error al actualizar rankings: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Get ranking data
    public function getUserRanking($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.nombre_completo,
                    u.ranking_puntos,
                    COUNT(a.id) as actividades_completadas,
                    MIN(TIMESTAMPDIFF(MINUTE, a.fecha_creacion, a.hora_evidencia)) as mejor_tiempo_minutos
                FROM usuarios u
                LEFT JOIN actividades a ON u.id = a.usuario_id AND a.estado = 'completada'
                WHERE u.estado = 'activo'
                GROUP BY u.id, u.nombre_completo, u.ranking_puntos
                ORDER BY u.ranking_puntos DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener ranking: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Get pending tasks for a user
    public function getPendingTasks($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    a.*,
                    s.nombre_completo as solicitante_nombre,
                    ta.nombre as tipo_nombre
                FROM actividades a
                JOIN usuarios s ON a.solicitante_id = s.id
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                WHERE a.tarea_pendiente = 1 
                AND a.usuario_id = ?
                AND a.usuario_id != a.solicitante_id
                AND a.estado != 'completada'
                ORDER BY a.fecha_creacion DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener tareas pendientes: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener color para el estado de actividad
    private function getActivityColor($estado) {
        switch ($estado) {
            case 'completada':
                return '#28a745'; // Verde
            case 'en_progreso':
                return '#ffc107'; // Amarillo
            case 'programada':
                return '#007bff'; // Azul
            case 'cancelada':
                return '#dc3545'; // Rojo
            default:
                return '#6c757d'; // Gris
        }
    }
}
?>