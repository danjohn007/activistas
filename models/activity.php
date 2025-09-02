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
                // FIXED: Use the ID of who's creating the activity, not the recipient
                // This was causing activities to be incorrectly auto-completed for user ID 4
                $solicitante_id = isset($data['solicitante_id']) ? $data['solicitante_id'] : $data['usuario_id'];
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
                // Enhanced logging to track activity creation and task assignment
                $taskStatus = $tarea_pendiente ? 'pending task' : 'regular activity';
                $requesterInfo = $solicitante_id ? " (requested by user $solicitante_id)" : '';
                logActivity("Nueva actividad creada: ID $activityId por usuario {$data['usuario_id']} - $taskStatus$requesterInfo");
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
                           s.nombre_completo as solicitante_nombre, u.email as usuario_correo, u.telefono as usuario_telefono,
                           p.nombre_completo as propuesto_por_nombre, auth.nombre_completo as autorizado_por_nombre
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
                    LEFT JOIN usuarios s ON a.solicitante_id = s.id
                    LEFT JOIN usuarios p ON a.propuesto_por = p.id
                    LEFT JOIN usuarios auth ON a.autorizado_por = auth.id
                    WHERE 1=1";
            $params = [];
            
            // Only show authorized activities in general listings (unless viewing proposals)
            if (!isset($filters['include_unauthorized'])) {
                $sql .= " AND (a.autorizada = 1 OR a.propuesto_por IS NULL)";
            }
            
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
                $sql .= " AND u.email LIKE ?";
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
                $sql .= " AND u.email LIKE ?";
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
    public function addEvidence($activityId, $type, $file = null, $content = null, $blocked = 1) {
        try {
            // Check if evidence is already blocked (only for completion evidence, not initial attachments)
            if ($blocked == 1) {
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
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO evidencias (actividad_id, tipo_evidencia, archivo, contenido, fecha_subida, bloqueada)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?)
            ");
            
            $result = $stmt->execute([$activityId, $type, $file, $content, $blocked]);
            
            if ($result) {
                $evidenceId = $this->db->lastInsertId();
                
                // Only update activity timestamp and rankings for completion evidence (blocked=1)
                // Initial attachments (blocked=0) should not mark activity as completed
                if ($blocked == 1) {
                    // Update activity with evidence timestamp and mark as completed
                    $this->updateActivityEvidenceTimestamp($activityId);
                    
                    // Update rankings after evidence is uploaded
                    // This ensures all user roles (Activista, Líder, Admin) get proper ranking updates
                    $this->updateUserRankings();
                }
                
                logActivity("Evidencia agregada: ID $evidenceId para actividad $activityId" . ($blocked == 0 ? " (archivo inicial)" : " (evidencia de completado)"));
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
    
    // Calculate and update user rankings with new point system
    // Nuevo sistema de ranking implementado según requerimientos:
    // - Base: 100 puntos
    // - Primer respondedor: 100 + total de usuarios activos
    // - Siguientes: (100 + total usuarios) - posición (0-indexed)
    // Los puntos se acumulan por cada tarea completada
    public function updateUserRankings() {
        try {
            // Get total number of active users for point calculation
            require_once __DIR__ . '/user.php';
            $userModel = new User();
            $totalUsers = $userModel->getTotalActiveUsers();
            
            // Get all users with completed activities (excluding admin user id=1, only count authorized activities)
            // Group by task to calculate points per task completion
            $stmt = $this->db->prepare("
                SELECT 
                    a.id as actividad_id,
                    a.usuario_id,
                    a.titulo,
                    a.hora_evidencia,
                    TIMESTAMPDIFF(MINUTE, a.fecha_creacion, a.hora_evidencia) as tiempo_respuesta_minutos
                FROM actividades a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.estado = 'completada' 
                  AND a.hora_evidencia IS NOT NULL 
                  AND a.autorizada = 1 
                  AND a.tarea_pendiente = 1
                  AND u.id != 1
                ORDER BY a.id, a.hora_evidencia ASC
            ");
            $stmt->execute();
            $completedTasks = $stmt->fetchAll();
            
            if (empty($completedTasks)) {
                return;
            }
            
            // Reset all user rankings to 0
            $resetStmt = $this->db->prepare("UPDATE usuarios SET ranking_puntos = 0 WHERE id != 1");
            $resetStmt->execute();
            
            // Group tasks by activity ID to calculate position-based points
            $tasksByActivity = [];
            foreach ($completedTasks as $task) {
                $tasksByActivity[$task['actividad_id']][] = $task;
            }
            
            // Calculate points for each task completion
            foreach ($tasksByActivity as $activityId => $tasks) {
                // Sort by completion time (hora_evidencia) to determine order
                usort($tasks, function($a, $b) {
                    return strtotime($a['hora_evidencia']) - strtotime($b['hora_evidencia']);
                });
                
                // Assign points based on completion order
                foreach ($tasks as $position => $task) {
                    // New point system: Base 100 + total users, minus position (0-indexed)
                    // Ejemplo: Si hay 50 usuarios activos:
                    // - Primer lugar: 100 + 50 = 150 puntos
                    // - Segundo lugar: 150 - 1 = 149 puntos
                    // - Tercer lugar: 150 - 2 = 148 puntos, etc.
                    $basePoints = 100;
                    $maxPoints = $basePoints + $totalUsers;
                    $puntos = $maxPoints - $position; // First responder gets max points, subsequent get -1 each
                    
                    // Update user ranking points (accumulative)
                    $updateStmt = $this->db->prepare("
                        UPDATE usuarios 
                        SET ranking_puntos = ranking_puntos + ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$puntos, $task['usuario_id']]);
                    
                    logActivity("Puntos asignados: Usuario {$task['usuario_id']} recibió $puntos puntos por actividad $activityId (posición " . ($position + 1) . ")");
                }
            }
            
            logActivity("Rankings actualizados con nuevo sistema: Base 100 + $totalUsers usuarios totales. Actividades procesadas: " . count($tasksByActivity));
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
                LEFT JOIN actividades a ON u.id = a.usuario_id AND a.estado = 'completada' AND a.autorizada = 1
                WHERE u.estado = 'activo' AND u.id != 1
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
    
    // Get pending tasks for a user with initial attachments
    public function getPendingTasks($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    a.*,
                    s.nombre_completo as solicitante_nombre,
                    ta.nombre as tipo_nombre,
                    GROUP_CONCAT(
                        CONCAT(e.id, ':', e.tipo_evidencia, ':', IFNULL(e.archivo, ''), ':', IFNULL(e.contenido, ''))
                        SEPARATOR '|'
                    ) as archivos_iniciales
                FROM actividades a
                JOIN usuarios s ON a.solicitante_id = s.id
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                LEFT JOIN evidencias e ON a.id = e.actividad_id AND e.bloqueada = 0
                WHERE a.tarea_pendiente = 1 
                AND a.usuario_id = ?
                AND a.usuario_id != a.solicitante_id
                AND a.estado != 'completada'
                GROUP BY a.id
                ORDER BY a.fecha_creacion DESC
            ");
            $stmt->execute([$userId]);
            $tasks = $stmt->fetchAll();
            
            // Process the initial files for each task
            foreach ($tasks as &$task) {
                $task['initial_attachments'] = [];
                if (!empty($task['archivos_iniciales'])) {
                    $files = explode('|', $task['archivos_iniciales']);
                    foreach ($files as $file) {
                        if (!empty($file)) {
                            $parts = explode(':', $file, 4);
                            if (count($parts) == 4) {
                                $task['initial_attachments'][] = [
                                    'id' => $parts[0],
                                    'tipo_evidencia' => $parts[1],
                                    'archivo' => $parts[2],
                                    'contenido' => $parts[3]
                                ];
                            }
                        }
                    }
                }
                // Remove the raw string from the result
                unset($task['archivos_iniciales']);
            }
            
            return $tasks;
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
    
    // Crear propuesta de actividad por activista
    public function createProposal($data) {
        try {
            // Las propuestas se crean en estado 'programada' con propuesto_por y autorizada=0
            $stmt = $this->db->prepare("
                INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad, estado, tarea_pendiente, solicitante_id, propuesto_por, autorizada)
                VALUES (?, ?, ?, ?, ?, 'programada', 2, ?, ?, 0)
            ");
            
            $result = $stmt->execute([
                $data['usuario_id'], // El activista que propone
                $data['tipo_actividad_id'],
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['fecha_actividad'],
                $data['usuario_id'], // El mismo activista es el solicitante de su propuesta
                $data['usuario_id']  // Propuesto por el mismo activista
            ]);
            
            if ($result) {
                $activityId = $this->db->lastInsertId();
                logActivity("Propuesta de actividad creada: ID $activityId por usuario {$data['usuario_id']}");
                return $activityId;
            }
            
            return false;
        } catch (Exception $e) {
            logActivity("Error al crear propuesta: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener propuestas pendientes de aprobación
    public function getPendingProposals($filters = []) {
        try {
            $sql = "SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre,
                           u.email as usuario_email, u.telefono as usuario_telefono,
                           s.nombre_completo as solicitante_nombre
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
                    LEFT JOIN usuarios s ON a.solicitante_id = s.id
                    WHERE a.tarea_pendiente = 2 AND a.autorizada = 0"; // Propuestas pendientes
            $params = [];
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            // Líder solo ve propuestas de sus activistas
            if (!empty($filters['lider_id'])) {
                $sql .= " AND u.lider_id = ?";
                $params[] = $filters['lider_id'];
            }
            
            $sql .= " ORDER BY a.fecha_creacion DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener propuestas pendientes: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Aprobar o rechazar propuesta
    public function approveProposal($activityId, $approved, $approverId) {
        try {
            if ($approved) {
                // Aprobar: marcar como autorizada y establecer quien autorizó
                $stmt = $this->db->prepare("
                    UPDATE actividades 
                    SET autorizada = 1, autorizado_por = ?, tarea_pendiente = 1, estado = 'programada'
                    WHERE id = ? AND autorizada = 0
                ");
                $result = $stmt->execute([$approverId, $activityId]);
                
                if ($result) {
                    logActivity("Propuesta ID $activityId autorizada por usuario $approverId");
                    
                    // Obtener información de la actividad para dar puntos bonus
                    $stmt = $this->db->prepare("SELECT usuario_id, bonificacion_ranking FROM actividades WHERE id = ?");
                    $stmt->execute([$activityId]);
                    $activity = $stmt->fetch();
                    
                    if ($activity) {
                        // Dar puntos de bonus (usar bonificacion_ranking si está definido, sino 100 por defecto)
                        $bonusPoints = $activity['bonificacion_ranking'] > 0 ? $activity['bonificacion_ranking'] : 100;
                        $this->addProposalBonus($activity['usuario_id'], $bonusPoints);
                    }
                }
            } else {
                // Rechazar: cambiar estado a cancelada pero mantener registro de quien rechazó
                $stmt = $this->db->prepare("
                    UPDATE actividades 
                    SET estado = 'cancelada', autorizado_por = ?
                    WHERE id = ? AND autorizada = 0
                ");
                $result = $stmt->execute([$approverId, $activityId]);
                
                if ($result) {
                    logActivity("Propuesta ID $activityId rechazada por usuario $approverId");
                }
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al procesar propuesta: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Dar puntos bonus por propuesta aprobada
    private function addProposalBonus($userId, $bonusPoints = 100) {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET ranking_puntos = ranking_puntos + ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$bonusPoints, $userId]);
            
            if ($result) {
                logActivity("$bonusPoints puntos de bonus por propuesta agregados al usuario $userId");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al agregar bonus de propuesta: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Send notification for new activity
    public function notifyNewActivity($activityId, $userId, $titulo) {
        try {
            // Get user info
            $stmt = $this->db->prepare("SELECT nombre_completo, email FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return false;
            }
            
            // Create flash message for immediate notification
            $message = "Nueva actividad asignada: '$titulo'. Haz clic aquí para subir evidencia.";
            $evidenceUrl = url("activities/add_evidence.php?actividad_id=$activityId");
            
            // Store notification in session for the target user
            if (!isset($_SESSION['user_notifications'])) {
                $_SESSION['user_notifications'] = [];
            }
            
            if (!isset($_SESSION['user_notifications'][$userId])) {
                $_SESSION['user_notifications'][$userId] = [];
            }
            
            $_SESSION['user_notifications'][$userId][] = [
                'message' => $message,
                'url' => $evidenceUrl,
                'type' => 'info',
                'timestamp' => time()
            ];
            
            logActivity("Notificación enviada al usuario {$user['nombre_completo']} para actividad ID $activityId");
            
            return true;
        } catch (Exception $e) {
            logActivity("Error al enviar notificación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
?>