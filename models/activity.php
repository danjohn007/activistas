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
    
    /**
     * Verificar si ya existe una actividad duplicada
     * @param int $usuario_id ID del usuario
     * @param string $titulo Título de la actividad
     * @param int $tipo_actividad_id Tipo de actividad
     * @param string $fecha_actividad Fecha de la actividad
     * @return bool True si existe duplicado, False si no existe
     */
    public function activityExists($usuario_id, $titulo, $tipo_actividad_id, $fecha_actividad) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM actividades
                WHERE usuario_id = ?
                AND titulo = ?
                AND tipo_actividad_id = ?
                AND fecha_actividad = ?
            ");
            
            $stmt->execute([$usuario_id, $titulo, $tipo_actividad_id, $fecha_actividad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['total'] > 0);
        } catch (Exception $e) {
            error_log("Error verificando duplicados: " . $e->getMessage());
            return false; // En caso de error, permitir inserción (por seguridad)
        }
    }
    
    // Crear nueva actividad
    public function createActivity($data) {
        try {
            // VALIDACIÓN ANTI-DUPLICADOS: Verificar si ya existe esta actividad para este usuario
            $exists = $this->activityExists(
                $data['usuario_id'],
                $data['titulo'],
                $data['tipo_actividad_id'],
                $data['fecha_actividad']
            );
            
            if ($exists) {
                error_log("⚠️ DUPLICADO PREVENIDO: Actividad ya existe para usuario {$data['usuario_id']}: {$data['titulo']}");
                // Retornar el ID de una actividad existente para no romper el flujo
                // pero sin crear duplicado
                $stmt = $this->db->prepare("
                    SELECT id FROM actividades
                    WHERE usuario_id = ? AND titulo = ? AND tipo_actividad_id = ? AND fecha_actividad = ?
                    LIMIT 1
                ");
                $stmt->execute([$data['usuario_id'], $data['titulo'], $data['tipo_actividad_id'], $data['fecha_actividad']]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                return $existing ? $existing['id'] : false;
            }
            
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
            
            // Determine authorization status based on user role
            $autorizada = 0;
            $autorizado_por = null;
            
            // Auto-authorize activities created by privileged roles (SuperAdmin, Gestor, Líder)
            if (isset($data['user_role']) && in_array($data['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])) {
                $autorizada = 1;
                $autorizado_por = $data['created_by_id'] ?? $data['usuario_id']; // Who authorized it (the creator)
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, enlace_1, enlace_2, enlace_3, enlace_4, fecha_actividad, fecha_publicacion, hora_publicacion, fecha_cierre, hora_cierre, grupo, tarea_pendiente, solicitante_id, autorizada, autorizado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['usuario_id'],
                $data['tipo_actividad_id'],
                $data['titulo'],
                $data['descripcion'] ?? null,
                !empty($data['enlace_1']) ? $data['enlace_1'] : null,
                !empty($data['enlace_2']) ? $data['enlace_2'] : null,
                !empty($data['enlace_3']) ? $data['enlace_3'] : null,
                !empty($data['enlace_4']) ? $data['enlace_4'] : null,
                $data['fecha_actividad'],
                !empty($data['fecha_publicacion']) ? $data['fecha_publicacion'] : null,
                !empty($data['hora_publicacion']) ? $data['hora_publicacion'] : null,
                !empty($data['fecha_cierre']) ? $data['fecha_cierre'] : null,
                !empty($data['hora_cierre']) ? $data['hora_cierre'] : null,
                !empty($data['grupo']) ? $data['grupo'] : null,
                $tarea_pendiente,
                $solicitante_id,
                $autorizada,
                $autorizado_por
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
            
            // OPTIMIZACIÓN MEJORADA: Conteo de evidencias con LEFT JOIN directo
            // En lugar de subconsulta que procesa 91k evidencias, hacemos GROUP BY solo de las actividades filtradas
            // IMPORTANTE: Solo contar evidencias reales (bloqueada=1), no archivos de referencia (bloqueada=0)
            $evidenceCountSQL = "";
            $evidenceJoinSQL = "";
            if (!empty($filters['include_evidence_count'])) {
                $evidenceCountSQL = ", COUNT(DISTINCT e.id) as evidence_count";
                $evidenceJoinSQL = "\n                    LEFT JOIN evidencias e ON a.id = e.actividad_id AND e.bloqueada = 1";
            }
            
            $sql = "SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre,
                           s.nombre_completo as solicitante_nombre, u.email as usuario_correo, u.telefono as usuario_telefono,
                           p.nombre_completo as propuesto_por_nombre, auth.nombre_completo as autorizado_por_nombre
                           $evidenceCountSQL
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
                    LEFT JOIN usuarios s ON a.solicitante_id = s.id
                    LEFT JOIN usuarios p ON a.propuesto_por = p.id
                    LEFT JOIN usuarios auth ON a.autorizado_por = auth.id
                    $evidenceJoinSQL";
            
            $sql .= "\n                    WHERE 1=1";
            $params = [];
            
            // Only show authorized activities in general listings (unless viewing proposals)
            if (!isset($filters['include_unauthorized'])) {
                $sql .= " AND (a.autorizada = 1 OR a.propuesto_por IS NULL)";
            }
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
                
                // Si el filtro incluye excluir vencidas (para vista de Activista)
                if (!empty($filters['exclude_expired'])) {
                    // Excluir tareas vencidas
                    $sql .= " AND (a.fecha_cierre IS NULL OR a.fecha_cierre > CURDATE() 
                                OR (a.fecha_cierre = CURDATE() AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))";
                    
                    // Excluir tareas completadas
                    $sql .= " AND a.estado != 'completada'";
                    
                    // Filtro de fecha de publicación para Activistas (solo mostrar tareas ya publicadas)
                    // CRÍTICO: Combinar fecha+hora en un DATETIME para evitar desincronización de timezones
                    $sql .= " AND (a.fecha_publicacion IS NULL
                                OR a.fecha_publicacion < CURDATE()
                                OR (a.fecha_publicacion = CURDATE() AND COALESCE(a.hora_publicacion, '00:00:00') <= CURTIME()))";
                }
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
            
            // Filter activities by leader - includes leader's own activities and their team's activities
            if (!empty($filters['filter_lider_id'])) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['filter_lider_id'];
                $params[] = $filters['filter_lider_id'];
            }
            
            // Filter activities by group - shows activities from users in the specified group
            if (!empty($filters['grupo_id'])) {
                $sql .= " AND u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
            }
            
            // GROUP BY necesario cuando usamos COUNT(DISTINCT e.id) para evidencias
            if (!empty($filters['include_evidence_count'])) {
                $sql .= " GROUP BY a.id";
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
                    WHERE 1=1";
            $params = [];
            
            // Only show authorized activities in general listings (unless viewing proposals)
            if (!isset($filters['include_unauthorized'])) {
                $sql .= " AND (a.autorizada = 1 OR a.propuesto_por IS NULL)";
            }
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
                
                // Si el filtro incluye excluir vencidas (para vista de Activista)
                if (!empty($filters['exclude_expired'])) {
                    // Excluir tareas vencidas
                    $sql .= " AND (a.fecha_cierre IS NULL OR a.fecha_cierre > CURDATE() 
                                OR (a.fecha_cierre = CURDATE() AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))";
                    
                    // Excluir tareas completadas
                    $sql .= " AND a.estado != 'completada'";
                    
                    // Filtro de fecha de publicación para Activistas (solo mostrar tareas ya publicadas)
                    // CRÍTICO: Combinar fecha+hora en un DATETIME para evitar desincronización de timezones
                    $sql .= " AND (a.fecha_publicacion IS NULL
                                OR a.fecha_publicacion < CURDATE()
                                OR (a.fecha_publicacion = CURDATE() AND COALESCE(a.hora_publicacion, '00:00:00') <= CURTIME()))";
                }
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
            
            // Filter activities by leader - includes leader's own activities and their team's activities
            if (!empty($filters['filter_lider_id'])) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['filter_lider_id'];
                $params[] = $filters['filter_lider_id'];
            }
            
            // Filter activities by group - shows activities from users in the specified group
            if (!empty($filters['grupo_id'])) {
                $sql .= " AND u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
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
    
    /**
     * Obtener actividades recientes (VERSI\u00d3N LIGERA OPTIMIZADA)
     * Solo devuelve los campos esenciales para mostrar en el dashboard
     * Reduce significativamente el tama\u00f1o de los datos y el tiempo de respuesta
     */
    public function getRecentActivitiesLight($limit = 10, $filters = []) {
        try {
            if (!$this->db) {
                throw new Exception("No hay conexi\u00f3n a la base de datos disponible");
            }
            
            // Solo seleccionar campos necesarios
            $sql = "SELECT a.id, a.titulo, a.fecha_actividad, a.estado,
                           u.nombre_completo as usuario_nombre,
                           ta.nombre as tipo_nombre,
                           s.nombre_completo as solicitante_nombre
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                    LEFT JOIN usuarios s ON a.solicitante_id = s.id
                    WHERE a.autorizada = 1";
            $params = [];
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
                
                // Para activistas: excluir vencidas y completadas
                $sql .= " AND a.estado != 'completada'";
                $sql .= " AND (a.fecha_cierre IS NULL 
                            OR a.fecha_cierre > CURDATE()
                            OR (a.fecha_cierre = CURDATE() AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))";
                $sql .= " AND (a.fecha_publicacion IS NULL
                            OR a.fecha_publicacion < CURDATE()
                            OR (a.fecha_publicacion = CURDATE() AND COALESCE(a.hora_publicacion, '00:00:00') <= CURTIME()))";
            }
            
            if (!empty($filters['lider_id'])) {
                $sql .= " AND (a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['lider_id'];
                $params[] = $filters['lider_id'];
            }
            
            $sql .= " ORDER BY a.fecha_actividad DESC, a.fecha_creacion DESC
                     LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener actividades recientes: " . $e->getMessage(), 'ERROR');
            return [];
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
            
            if (isset($data['grupo'])) {
                $fields[] = "grupo = ?";
                $params[] = $data['grupo'];
            }
            
            if (isset($data['enlace_1'])) {
                $fields[] = "enlace_1 = ?";
                $params[] = $data['enlace_1'];
            }
            
            if (isset($data['enlace_2'])) {
                $fields[] = "enlace_2 = ?";
                $params[] = $data['enlace_2'];
            }
            
            if (isset($data['enlace_3'])) {
                $fields[] = "enlace_3 = ?";
                $params[] = $data['enlace_3'];
            }
            
            if (isset($data['enlace_4'])) {
                $fields[] = "enlace_4 = ?";
                $params[] = $data['enlace_4'];
            }
            
            if (isset($data['fecha_publicacion'])) {
                $fields[] = "fecha_publicacion = ?";
                $params[] = !empty($data['fecha_publicacion']) ? $data['fecha_publicacion'] : null;
            }
            
            if (isset($data['hora_publicacion'])) {
                $fields[] = "hora_publicacion = ?";
                $params[] = !empty($data['hora_publicacion']) ? $data['hora_publicacion'] : null;
            }
            
            if (isset($data['fecha_cierre'])) {
                $fields[] = "fecha_cierre = ?";
                $params[] = !empty($data['fecha_cierre']) ? $data['fecha_cierre'] : null;
            }
            
            if (isset($data['hora_cierre'])) {
                $fields[] = "hora_cierre = ?";
                $params[] = !empty($data['hora_cierre']) ? $data['hora_cierre'] : null;
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
    
    public function deleteActivity($id) {
        try {
            // Primero eliminar evidencias relacionadas (foreign key)
            $stmt = $this->db->prepare("DELETE FROM evidencias WHERE actividad_id = ?");
            $stmt->execute([$id]);
            
            // Eliminar la actividad
            $stmt = $this->db->prepare("DELETE FROM actividades WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity("Actividad ID $id eliminada exitosamente");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al eliminar actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener tipos de actividades
    public function getActivityTypes() {
        try {
            // Use DISTINCT to avoid duplicates and add proper ordering
            $stmt = $this->db->prepare("
                SELECT DISTINCT id, nombre, descripcion, activo
                FROM tipos_actividades 
                WHERE activo = 1 
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener tipos de actividades: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Agregar evidencia a actividad
    /**
     * Add evidence to an activity
     * 
     * @param int $activityId The activity ID
     * @param string $type Evidence type (foto, video, audio, comentario, live)
     * @param string|null $file File path if applicable
     * @param string|null $content Text content if applicable  
     * @param int $blocked Whether evidence is blocked (0=initial attachment, 1=completion evidence)
     * @return array Success status and details
     * 
     * REQUIREMENT IMPLEMENTATION:
     * - blocked=0: Initial attachments uploaded during activity creation (displayed in pending tasks)
     * - blocked=1: Completion evidence uploaded when finishing task (triggers completion and rankings)
     */
    public function addEvidence($activityId, $type, $file = null, $content = null, $blocked = 1) {
        try {
            // REQUISITO CRÍTICO: Para completar una tarea (blocked=1), DEBE haber un archivo
            // No se permite marcar como completada una tarea sin evidencia fotográfica/archivo
            if ($blocked == 1 && empty($file)) {
                logActivity("Intento de completar tarea $activityId sin archivo de evidencia - RECHAZADO", 'WARNING');
                return ['success' => false, 'error' => 'No se puede completar la tarea sin subir un archivo de evidencia (foto/video/audio)'];
            }
            
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
            // Solo obtener evidencias del usuario (bloqueada = 1)
            // No incluir archivos de referencia del admin (bloqueada = 0)
            $stmt = $this->db->prepare("
                SELECT * FROM evidencias 
                WHERE actividad_id = ? AND bloqueada = 1
                ORDER BY fecha_subida DESC
            ");
            $stmt->execute([$activityId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener evidencias: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Obtener solo archivos de referencia (subidos por el admin al crear la tarea)
     * bloqueada = 0 indica que son archivos de referencia, no evidencias de completado
     */
    public function getReferenceFiles($activityId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM evidencias 
                WHERE actividad_id = ? AND bloqueada = 0
                ORDER BY fecha_subida ASC
            ");
            $stmt->execute([$activityId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener archivos de referencia: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Contar evidencias de una actividad (optimizado - solo el número)
     * Solo cuenta evidencias del usuario (bloqueada = 1)
     * No cuenta archivos de referencia del admin (bloqueada = 0)
     */
    public function countActivityEvidence($activityId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM evidencias 
                WHERE actividad_id = ? AND bloqueada = 1
            ");
            $stmt->execute([$activityId]);
            $result = $stmt->fetch();
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            logActivity("Error al contar evidencias: " . $e->getMessage(), 'ERROR');
            return 0;
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
            
            if (!empty($filters['grupo_id'])) {
                $sql .= " AND u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
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
                    LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id AND a.estado != 'cancelada'";
            
            // Agregar JOIN con usuarios si necesitamos filtrar por líder o grupo
            if (!empty($filters['lider_id']) || !empty($filters['grupo_id'])) {
                $sql .= " LEFT JOIN usuarios u ON a.usuario_id = u.id";
            }
            
            $params = [];
            $where = ["ta.activo = 1"]; // Solo tipos activos
            
            if (!empty($filters['usuario_id'])) {
                $where[] = "a.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            if (!empty($filters['lider_id'])) {
                $where[] = "(a.usuario_id = ? OR u.lider_id = ?)";
                $params[] = $filters['lider_id'];
                $params[] = $filters['lider_id'];
            }
            
            if (!empty($filters['grupo_id'])) {
                $where[] = "u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
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
            
            $sql .= " GROUP BY ta.id, ta.nombre 
                     HAVING cantidad > 0
                     ORDER BY cantidad DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll();
            
            // Si no hay resultados, devolver array vacío con mensaje informativo
            if (empty($results)) {
                logActivity("No se encontraron actividades para los filtros proporcionados", 'INFO');
            }
            
            return $results;
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
    
    /**
     * Calculate and update user rankings with new point system
     * 
     * RANKING SYSTEM WITH TIE-BREAKING RULES (as per requirements):
     * - Base points: 1000
     * - First responder: 1000 + total active users in the system
     * - Second responder: 1000 + total users - 1
     * - Third responder: 1000 + total users - 2
     * - And so on... until the last to respond
     * 
     * Points are accumulated for each completed task.
     * The system ensures proper tie-breaking by considering response order.
     * 
     * This method is called after each task completion to update rankings.
     */
    public function updateUserRankings() {
        try {
            // Get total number of active users for point calculation
            require_once __DIR__ . '/user.php';
            $userModel = new User();
            $totalUsers = $userModel->getTotalActiveUsers();
            
            // Get the last ranking reset date to only count activities from that point forward
            $lastResetDate = $this->getLastRankingResetDate();
            
            // Get all users with completed activities (excluding admin user id=1, only count authorized activities)
            // Group by task to calculate points per task completion
            // Only count activities completed AFTER the last ranking reset
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
                  AND a.hora_evidencia > ?
                ORDER BY a.id, a.hora_evidencia ASC
            ");
            $stmt->execute([$lastResetDate]);
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
                    // Updated point system: Base 1000 + total users, minus position (0-indexed)
                    // Ejemplo: Si hay 50 usuarios activos:
                    // - Primer lugar: 1000 + 50 = 1050 puntos
                    // - Segundo lugar: 1050 - 1 = 1049 puntos
                    // - Tercer lugar: 1050 - 2 = 1048 puntos, etc.
                    $basePoints = 1000;
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
            
            logActivity("Rankings actualizados con nuevo sistema: Base 1000 + $totalUsers usuarios totales. Actividades desde {$lastResetDate}. Actividades procesadas: " . count($tasksByActivity));
        } catch (Exception $e) {
            logActivity("Error al actualizar rankings: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Get the last ranking reset date to only count activities from that point forward
     */
    private function getLastRankingResetDate() {
        try {
            // Try to get the most recent reset date from ranking resets log
            $stmt = $this->db->prepare("
                SELECT valor FROM configuraciones 
                WHERE clave = 'ultimo_reset_ranking' 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && !empty($result['valor'])) {
                return $result['valor'];
            }
            
            // Fallback: If no reset date is found, use a very old date to include all activities
            return '2020-01-01 00:00:00';
        } catch (Exception $e) {
            logActivity("Error al obtener fecha último reset: " . $e->getMessage(), 'ERROR');
            return '2020-01-01 00:00:00';
        }
    }
    
    // Get ranking data with detailed task completion information
    public function getUserRanking($limit = 10, $grupo = null) {
        try {
            $params = [];
            $groupFilterCompleted = '';
            $groupFilterPending = '';
            
            if (!empty($grupo)) {
                $groupFilterCompleted = 'AND grupo = ?';
                $groupFilterPending = 'AND grupo = ?';
                $params[] = $grupo;
                $params[] = $grupo;
            }
            
            $sql = "
                SELECT 
                    u.id,
                    u.nombre_completo,
                    u.ranking_puntos,
                    COALESCE(completed.total, 0) as actividades_completadas,
                    COALESCE(pending.total, 0) as tareas_asignadas,
                    ROUND(
                        CASE 
                            WHEN COALESCE(pending.total, 0) > 0 THEN (COALESCE(completed.total, 0) * 100.0 / pending.total)
                            ELSE 0 
                        END, 2
                    ) as porcentaje_cumplimiento,
                    completed.mejor_tiempo_minutos,
                    completed.tiempo_promedio_minutos
                FROM usuarios u
                LEFT JOIN (
                    SELECT 
                        usuario_id,
                        COUNT(*) as total,
                        MIN(TIMESTAMPDIFF(MINUTE, fecha_creacion, hora_evidencia)) as mejor_tiempo_minutos,
                        AVG(TIMESTAMPDIFF(MINUTE, fecha_creacion, hora_evidencia)) as tiempo_promedio_minutos
                    FROM actividades
                    WHERE estado = 'completada' AND autorizada = 1 $groupFilterCompleted
                    GROUP BY usuario_id
                ) completed ON u.id = completed.usuario_id
                LEFT JOIN (
                    SELECT 
                        usuario_id,
                        COUNT(*) as total
                    FROM actividades
                    WHERE tarea_pendiente = 1 $groupFilterPending
                    GROUP BY usuario_id
                ) pending ON u.id = pending.usuario_id
                WHERE u.estado = 'activo' AND u.id != 1
                ORDER BY u.ranking_puntos DESC
                LIMIT ?
            ";
            
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener ranking: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Get available groups from activities
    public function getAvailableGroups() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT grupo 
                FROM actividades 
                WHERE grupo IS NOT NULL AND grupo != '' 
                ORDER BY grupo
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result ?: [];
        } catch (Exception $e) {
            logActivity("Error al obtener grupos disponibles: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Get pending tasks for a user with initial attachments
     * 
     * REQUIREMENT IMPLEMENTATION: 
     * "En la vista de tareas pendientes, mostrar los archivos adjuntos que se agregaron 
     * cuando la tarea fue creada, esto aplica para todos los niveles de usuario."
     * 
     * This method retrieves pending tasks and includes any initial attachments (bloqueada=0)
     * that were uploaded during task creation, distinguishing them from completion evidence.
     * 
     * @param int $userId The user ID to get pending tasks for
     * @return array Array of pending tasks with initial_attachments field
     */
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
                AND a.autorizada = 1
                AND a.estado != 'completada'
                AND (a.fecha_cierre IS NULL 
                     OR a.fecha_cierre > CURDATE()
                     OR (a.fecha_cierre = CURDATE() 
                         AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))
                AND (a.fecha_publicacion IS NULL 
                     OR CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) <= NOW())
                GROUP BY a.id
                ORDER BY 
                    -- Tareas con fecha de cierre van primero, ordenadas por urgencia (más próximas a vencer)
                    CASE WHEN a.fecha_cierre IS NOT NULL THEN 0 ELSE 1 END,
                    a.fecha_cierre ASC,
                    a.hora_cierre ASC,
                    a.fecha_creacion DESC
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
                INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad, grupo, estado, tarea_pendiente, solicitante_id, propuesto_por, autorizada)
                VALUES (?, ?, ?, ?, ?, ?, 'programada', 2, ?, ?, 0)
            ");
            
            $result = $stmt->execute([
                $data['usuario_id'], // El activista que propone
                $data['tipo_actividad_id'],
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['fecha_actividad'],
                !empty($data['grupo']) ? $data['grupo'] : null,
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
    
    /**
     * Save current month rankings and reset for new month
     * 
     * REQUIREMENT IMPLEMENTATION: Monthly Ranking Reset System
     * 
     * This method implements the monthly ranking reset functionality as requested:
     * 1. Saves a historical record of the TOP 10 places at the moment of reset
     * 2. Resets all user ranking points to ZERO to start fresh
     * 3. Maintains the tie-breaking rules for future ranking calculations:
     *    - First responder: 1000 + total active users in the system
     *    - Second responder: 1000 + total users - 1
     *    - Third responder: 1000 + total users - 2
     *    - And so on... until the last to respond
     * 
     * The generated SQL operations:
     * 1. SELECT to get current rankings with detailed metrics
     * 2. INSERT/UPDATE to save top 10 positions in rankings_mensuales table
     * 3. UPDATE to reset all ranking_puntos to 0 for fresh start
     * 
     * @return bool Success status of the operation
     */
    public function saveMonthlyRankingsAndReset() {
        try {
            $year = date('Y');
            $month = date('n'); // 1-12 format
            
            // Get all active users with their current ranking points
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.ranking_puntos,
                    COALESCE(completed.total, 0) as actividades_completadas,
                    ROUND(
                        CASE 
                            WHEN COALESCE(pending.total, 0) > 0 THEN (COALESCE(completed.total, 0) * 100.0 / pending.total)
                            ELSE 0 
                        END, 2
                    ) as porcentaje_cumplimiento
                FROM usuarios u
                LEFT JOIN (
                    SELECT 
                        usuario_id,
                        COUNT(*) as total
                    FROM actividades
                    WHERE estado = 'completada' AND autorizada = 1
                        AND YEAR(fecha_creacion) = ? 
                        AND MONTH(fecha_creacion) = ?
                    GROUP BY usuario_id
                ) completed ON u.id = completed.usuario_id
                LEFT JOIN (
                    SELECT 
                        usuario_id,
                        COUNT(*) as total
                    FROM actividades
                    WHERE tarea_pendiente = 1
                        AND YEAR(fecha_creacion) = ? 
                        AND MONTH(fecha_creacion) = ?
                    GROUP BY usuario_id
                ) pending ON u.id = pending.usuario_id
                WHERE u.estado = 'activo' AND u.id != 1
            ");
            $stmt->execute([$year, $month, $year, $month]);
            $users = $stmt->fetchAll();
            
            // Sort by ranking points to assign positions
            usort($users, function($a, $b) {
                return $b['ranking_puntos'] - $a['ranking_puntos'];
            });
            
            // Save monthly rankings (only top 10 positions as per new requirements)
            $position = 1;
            foreach (array_slice($users, 0, 10) as $user) {
                $insertStmt = $this->db->prepare("
                    INSERT INTO rankings_mensuales 
                    (usuario_id, anio, mes, puntos, posicion, actividades_completadas, porcentaje_cumplimiento)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    puntos = VALUES(puntos),
                    posicion = VALUES(posicion),
                    actividades_completadas = VALUES(actividades_completadas),
                    porcentaje_cumplimiento = VALUES(porcentaje_cumplimiento)
                ");
                
                $insertStmt->execute([
                    $user['id'],
                    $year,
                    $month,
                    $user['ranking_puntos'],
                    $position,
                    $user['actividades_completadas'],
                    $user['porcentaje_cumplimiento']
                ]);
                
                $position++;
            }
            
            // Reset current ranking points for all users (starting from zero as required)
            $resetStmt = $this->db->prepare("UPDATE usuarios SET ranking_puntos = 0 WHERE id != 1");
            $resetStmt->execute();
            
            // Save the reset timestamp for future ranking calculations
            $currentTimestamp = date('Y-m-d H:i:s');
            $configStmt = $this->db->prepare("
                INSERT INTO configuraciones (clave, valor, descripcion) 
                VALUES ('ultimo_reset_ranking', ?, 'Fecha y hora del último reset de ranking mensual')
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor)
            ");
            $configStmt->execute([$currentTimestamp]);
            
            logActivity("Ranking mensual guardado para $month/$year (top 10 posiciones) y puntos reiniciados a cero. Reset timestamp: $currentTimestamp. Usuarios procesados: " . count($users));
            
            return true;
        } catch (Exception $e) {
            logActivity("Error al guardar ranking mensual: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Get monthly rankings for a specific month and year
     */
    public function getMonthlyRanking($year = null, $month = null, $limit = 50) {
        try {
            if (!$year) $year = date('Y');
            if (!$month) $month = date('n');
            
            $stmt = $this->db->prepare("
                SELECT 
                    rm.*,
                    u.nombre_completo,
                    u.rol
                FROM rankings_mensuales rm
                JOIN usuarios u ON rm.usuario_id = u.id
                WHERE rm.anio = ? AND rm.mes = ?
                ORDER BY rm.posicion ASC
                LIMIT ?
            ");
            $stmt->execute([$year, $month, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener ranking mensual: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Get available months and years for rankings
     */
    public function getAvailableRankingPeriods() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT anio, mes, COUNT(*) as usuarios,
                       MAX(fecha_creacion) as fecha_corte
                FROM rankings_mensuales 
                GROUP BY anio, mes
                ORDER BY anio DESC, mes DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener períodos de ranking: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Get ranking cuts history with top 3 for each period
     */
    public function getRankingCutsHistory() {
        try {
            // First get all periods
            $stmt = $this->db->prepare("
                SELECT 
                    rm.anio, 
                    rm.mes,
                    MAX(rm.fecha_creacion) as fecha_corte,
                    COUNT(rm.id) as total_usuarios
                FROM rankings_mensuales rm
                GROUP BY rm.anio, rm.mes
                ORDER BY rm.anio DESC, rm.mes DESC
            ");
            $stmt->execute();
            $periods = $stmt->fetchAll();
            
            // For each period, get the top 3
            foreach ($periods as &$period) {
                $topStmt = $this->db->prepare("
                    SELECT rm.posicion, u.nombre_completo, rm.puntos, rm.actividades_completadas
                    FROM rankings_mensuales rm
                    JOIN usuarios u ON rm.usuario_id = u.id
                    WHERE rm.anio = ? AND rm.mes = ?
                    ORDER BY rm.posicion ASC
                    LIMIT 3
                ");
                $topStmt->execute([$period['anio'], $period['mes']]);
                $period['top_3'] = $topStmt->fetchAll();
            }
            
            return $periods;
        } catch (Exception $e) {
            logActivity("Error al obtener historial de cortes de ranking: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Get activist performance report
     * For admins and leaders to view task completion statistics
     */
    public function getActivistReport($filters = []) {
        try {
            // Build subquery filters for activities
            $activityWhere = ["1=1"];
            $activityParams = [];
            
            // Filter by date range
            if (!empty($filters['fecha_desde'])) {
                $activityWhere[] = "a.fecha_actividad >= ?";
                $activityParams[] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $activityWhere[] = "a.fecha_actividad <= ?";
                $activityParams[] = $filters['fecha_hasta'];
            }
            
            // Filter by activity type
            if (!empty($filters['tipo_actividad_id'])) {
                $activityWhere[] = "a.tipo_actividad_id = ?";
                $activityParams[] = $filters['tipo_actividad_id'];
            }
            
            // Filter by state
            if (!empty($filters['estado'])) {
                $activityWhere[] = "a.estado = ?";
                $activityParams[] = $filters['estado'];
            }
            
            // Filter by title
            if (!empty($filters['search_titulo'])) {
                $activityWhere[] = "a.titulo LIKE ?";
                $activityParams[] = '%' . $filters['search_titulo'] . '%';
            }
            
            $activityWhereClause = implode(' AND ', $activityWhere);
            
            $sql = "
                SELECT 
                    u.id,
                    u.nombre_completo,
                    u.email,
                    u.telefono,
                    u.rol,
                    l.nombre_completo as lider_nombre,
                    COALESCE(act.total_tareas_asignadas, 0) as total_tareas_asignadas,
                    COALESCE(act.tareas_completadas, 0) as tareas_completadas,
                    ROUND(
                        CASE 
                            WHEN COALESCE(act.total_tareas_asignadas, 0) > 0 
                            THEN (COALESCE(act.tareas_completadas, 0) * 100.0) / act.total_tareas_asignadas
                            ELSE 0 
                        END, 2
                    ) as porcentaje_cumplimiento,
                    u.ranking_puntos as puntos_actuales
                FROM usuarios u
                LEFT JOIN usuarios l ON u.lider_id = l.id
                LEFT JOIN (
                    SELECT 
                        usuario_id,
                        COUNT(CASE WHEN tarea_pendiente = 1 THEN 1 END) as total_tareas_asignadas,
                        COUNT(CASE WHEN estado = 'completada' AND tarea_pendiente = 1 THEN 1 END) as tareas_completadas
                    FROM actividades a
                    WHERE " . $activityWhereClause . "
                    GROUP BY usuario_id
                ) act ON u.id = act.usuario_id
            ";
            
            $params = $activityParams;
            $where = ["u.estado = 'activo'", "u.id != 1"];
            
            // Filter by leader (for leader dashboard)
            if (!empty($filters['lider_id'])) {
                $where[] = "u.lider_id = ?";
                $params[] = $filters['lider_id'];
            }
            
            // Filter by specific leader (SuperAdmin/Gestor selecting a leader)
            if (!empty($filters['filter_lider_id'])) {
                $where[] = "(u.lider_id = ? OR u.id = ?)";
                $params[] = $filters['filter_lider_id'];
                $params[] = $filters['filter_lider_id'];
            }
            
            // Filter by group (SuperAdmin only)
            if (!empty($filters['grupo_id'])) {
                $where[] = "u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
            }
            
            // Search filters
            if (!empty($filters['search_name'])) {
                $where[] = "u.nombre_completo LIKE ?";
                $params[] = '%' . $filters['search_name'] . '%';
            }
            
            if (!empty($filters['search_email'])) {
                $where[] = "u.email LIKE ?";
                $params[] = '%' . $filters['search_email'] . '%';
            }
            
            if (!empty($filters['search_phone'])) {
                $where[] = "u.telefono LIKE ?";
                $params[] = '%' . $filters['search_phone'] . '%';
            }
            
            $sql .= " WHERE " . implode(' AND ', $where);
            $sql .= " ORDER BY porcentaje_cumplimiento DESC, tareas_completadas DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener reporte de activistas: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Get global activity report - statistics grouped by activity type
     * Shows total tasks assigned, completed, and compliance percentage per activity type
     * 
     * @param array $filters Optional filters: fecha_desde, fecha_hasta, search
     * @param int $page Page number for pagination
     * @param int $perPage Items per page
     * @return array Array with activities data
     */
    public function getGlobalActivityReport($filters = [], $page = 1, $perPage = 20) {
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
            
            // Search filter for activity title or description
            $searchFilter = '';
            if (!empty($filters['search'])) {
                $searchFilter = " AND (a.titulo LIKE ? OR a.descripcion LIKE ? OR ta.nombre LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql = "
                SELECT 
                    ta.id as tipo_actividad_id,
                    ta.nombre as tipo_actividad,
                    COUNT(a.id) as total_tareas,
                    COUNT(CASE WHEN a.estado = 'completada' AND a.autorizada = 1 THEN 1 END) as tareas_completadas,
                    COUNT(CASE WHEN a.estado = 'pendiente' THEN 1 END) as tareas_pendientes,
                    ROUND(
                        CASE 
                            WHEN COUNT(a.id) > 0 THEN (COUNT(CASE WHEN a.estado = 'completada' AND a.autorizada = 1 THEN 1 END) * 100.0 / COUNT(a.id))
                            ELSE 0 
                        END, 2
                    ) as porcentaje_cumplimiento,
                    MAX(a.fecha_creacion) as ultima_actividad
                FROM tipos_actividades ta
                LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id $dateFilter $searchFilter
                WHERE ta.activo = 1
                GROUP BY ta.id, ta.nombre
                HAVING total_tareas > 0
                ORDER BY ultima_actividad DESC
            ";
            
            // Get total count for pagination
            $countStmt = $this->db->prepare($sql);
            $countStmt->execute($params);
            $allResults = $countStmt->fetchAll();
            $totalItems = count($allResults);
            
            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $activities = $stmt->fetchAll();
            
            return [
                'activities' => $activities,
                'total_items' => $totalItems,
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalItems / $perPage)
            ];
        } catch (Exception $e) {
            logActivity("Error al obtener reporte global de actividades: " . $e->getMessage(), 'ERROR');
            return [
                'activities' => [],
                'total_items' => 0,
                'current_page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }
    
    /**
     * Get detailed tasks for a specific activity type
     * Used in the global activity report detail view
     * 
     * @param int $tipoActividadId Activity type ID
     * @param array $filters Optional filters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Tasks for the activity type
     */
    public function getTasksByActivityType($tipoActividadId, $filters = [], $page = 1, $perPage = 20) {
        try {
            $params = [$tipoActividadId];
            $dateFilter = '';
            
            if (!empty($filters['fecha_desde'])) {
                $dateFilter .= " AND a.fecha_creacion >= ?";
                $params[] = $filters['fecha_desde'] . ' 00:00:00';
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $dateFilter .= " AND a.fecha_creacion <= ?";
                $params[] = $filters['fecha_hasta'] . ' 23:59:59';
            }
            
            $offset = ($page - 1) * $perPage;
            
            $sql = "
                SELECT 
                    a.id,
                    a.titulo,
                    a.descripcion,
                    a.estado,
                    a.autorizada,
                    a.fecha_creacion,
                    u.nombre_completo as usuario_nombre,
                    ta.nombre as tipo_actividad
                FROM actividades a
                JOIN usuarios u ON a.usuario_id = u.id
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                WHERE a.tipo_actividad_id = ? $dateFilter
                ORDER BY a.fecha_creacion DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener tareas por tipo de actividad: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Obtener informe global de tareas con porcentaje de cumplimiento
     * Agrupa por título de tarea y muestra estadísticas
     */
    public function getGlobalTaskReport($filters = []) {
        try {
            $params = [];
            $whereConditions = [];
            
            // Filtros de fecha
            if (!empty($filters['fecha_desde'])) {
                $whereConditions[] = "a.fecha_creacion >= ?";
                $params[] = $filters['fecha_desde'] . ' 00:00:00';
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $whereConditions[] = "a.fecha_creacion <= ?";
                $params[] = $filters['fecha_hasta'] . ' 23:59:59';
            }
            
            // Filtro por nombre de actividad (título)
            if (!empty($filters['nombre_actividad'])) {
                $whereConditions[] = "a.titulo LIKE ?";
                $params[] = '%' . $filters['nombre_actividad'] . '%';
            }
            
            // Filtro por nombre de activista
            if (!empty($filters['nombre_activista'])) {
                $whereConditions[] = "u.nombre_completo LIKE ?";
                $params[] = '%' . $filters['nombre_activista'] . '%';
            }
            
            // Filtro por grupo
            if (!empty($filters['grupo_id'])) {
                $whereConditions[] = "u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
            }
            
            // Filtro por líder
            if (!empty($filters['lider_id'])) {
                $whereConditions[] = "u.lider_id = ?";
                $params[] = $filters['lider_id'];
            }
            
            $whereClause = !empty($whereConditions) ? implode(' AND ', $whereConditions) : '1=1';
            
            $sql = "
                SELECT 
                    a.titulo,
                    a.tipo_actividad_id,
                    ta.nombre as tipo_actividad,
                    COUNT(DISTINCT a.id) as total_asignadas,
                    COUNT(DISTINCT CASE WHEN a.estado = 'completada' THEN a.id END) as total_completadas,
                    ROUND(
                        (COUNT(DISTINCT CASE WHEN a.estado = 'completada' THEN a.id END) * 100.0) / 
                        NULLIF(COUNT(DISTINCT a.id), 0), 
                        2
                    ) as porcentaje_cumplimiento,
                    MIN(a.fecha_creacion) as primera_asignacion,
                    MAX(a.fecha_creacion) as ultima_asignacion
                FROM actividades a
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE $whereClause
                GROUP BY a.titulo, a.tipo_actividad_id, ta.nombre
                ORDER BY total_asignadas DESC, porcentaje_cumplimiento DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // Obtener información adicional para cada tarea (descripcion y fechas) con consulta separada
            foreach ($results as &$task) {
                $infoSql = "SELECT descripcion, fecha_actividad, fecha_publicacion, hora_publicacion, fecha_cierre, hora_cierre 
                           FROM actividades 
                           WHERE titulo = ? AND tipo_actividad_id = ?
                           LIMIT 1";
                $infoStmt = $this->db->prepare($infoSql);
                $infoStmt->execute([$task['titulo'], $task['tipo_actividad_id']]);
                $info = $infoStmt->fetch();
                
                if ($info) {
                    $task['descripcion'] = $info['descripcion'];
                    $task['fecha_actividad'] = $info['fecha_actividad'];
                    $task['fecha_publicacion'] = $info['fecha_publicacion'];
                    $task['hora_publicacion'] = $info['hora_publicacion'];
                    $task['fecha_cierre'] = $info['fecha_cierre'];
                    $task['hora_cierre'] = $info['hora_cierre'];
                } else {
                    $task['descripcion'] = '';
                    $task['fecha_actividad'] = null;
                    $task['fecha_publicacion'] = null;
                    $task['hora_publicacion'] = null;
                    $task['fecha_cierre'] = null;
                    $task['hora_cierre'] = null;
                }
            }
            unset($task);
            
            return $results;
        } catch (Exception $e) {
            error_log("getGlobalTaskReport - Error: " . $e->getMessage());
            logActivity("Error al obtener informe global de tareas: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Obtener detalle de una tarea específica con usuarios que la completaron
     * Ordenados por fecha de completado (quién entregó primero)
     */
    public function getTaskDetailReport($titulo, $tipoActividadId, $filters = []) {
        try {
            // Limpiar el título de espacios extra
            $titulo = trim($titulo);
            
            // Log para debug detallado
            error_log("getTaskDetailReport - Titulo: '" . $titulo . "' (length: " . strlen($titulo) . "), TipoID: " . $tipoActividadId);
            
            $params = [$titulo, $tipoActividadId];
            $whereConditions = [];
            
            // Filtros de fecha
            if (!empty($filters['fecha_desde'])) {
                $whereConditions[] = "a.fecha_creacion >= ?";
                $params[] = $filters['fecha_desde'] . ' 00:00:00';
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $whereConditions[] = "a.fecha_creacion <= ?";
                $params[] = $filters['fecha_hasta'] . ' 23:59:59';
            }
            
            // Filtro por nombre de activista
            if (!empty($filters['nombre_activista'])) {
                $whereConditions[] = "u.nombre_completo LIKE ?";
                $params[] = '%' . $filters['nombre_activista'] . '%';
            }
            
            // Filtro por grupo
            if (!empty($filters['grupo_id'])) {
                $whereConditions[] = "u.grupo_id = ?";
                $params[] = $filters['grupo_id'];
            }
            
            // Filtro por líder
            if (!empty($filters['lider_id'])) {
                $whereConditions[] = "u.lider_id = ?";
                $params[] = $filters['lider_id'];
            }
            
            $dateFilter = !empty($whereConditions) ? ' AND ' . implode(' AND ', $whereConditions) : '';
            
            // Log de parámetros completos antes de ejecutar
            error_log("getTaskDetailReport - Params antes de ejecutar: " . json_encode($params));
            
            $sql = "
                SELECT 
                    a.id,
                    a.titulo,
                    a.descripcion,
                    a.estado,
                    a.fecha_creacion as fecha_asignacion,
                    a.fecha_actualizacion,
                    u.id as usuario_id,
                    u.nombre_completo as usuario_nombre,
                    u.email as usuario_email,
                    u.telefono as usuario_telefono,
                    g.nombre as grupo_nombre,
                    lider.nombre_completo as lider_nombre,
                    ta.nombre as tipo_actividad,
                    solicitante.nombre_completo as asignado_por,
                    COUNT(e.id) as total_evidencias,
                    CASE 
                        WHEN a.estado = 'completada' THEN 
                            TIMESTAMPDIFF(HOUR, a.fecha_creacion, a.fecha_actualizacion)
                        ELSE NULL
                    END as horas_para_completar
                FROM actividades a
                JOIN usuarios u ON a.usuario_id = u.id
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                LEFT JOIN grupos g ON u.grupo_id = g.id
                LEFT JOIN usuarios lider ON u.lider_id = lider.id
                LEFT JOIN usuarios solicitante ON a.solicitante_id = solicitante.id
                LEFT JOIN evidencias e ON a.id = e.actividad_id
                WHERE a.titulo = ? AND a.tipo_actividad_id = ? AND (a.tarea_pendiente = 1 OR a.tarea_pendiente IS NULL) $dateFilter
                GROUP BY a.id, u.id, g.nombre, lider.nombre_completo, ta.nombre, solicitante.nombre_completo
                ORDER BY 
                    CASE WHEN a.estado = 'completada' THEN 0 ELSE 1 END,
                    a.fecha_actualizacion ASC,
                    a.fecha_creacion DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll();
            
            // Log para debug - incluir información del error si hay
            error_log("getTaskDetailReport - Resultados encontrados: " . count($results));
            if (count($results) === 0) {
                error_log("getTaskDetailReport - SQL ejecutado: " . $sql);
                error_log("getTaskDetailReport - Params enviados: " . json_encode($params));
                error_log("getTaskDetailReport - PDO ErrorInfo: " . json_encode($stmt->errorInfo()));
            }
            
            return $results;
        } catch (Exception $e) {
            logActivity("Error al obtener detalle de tarea: " . $e->getMessage(), 'ERROR');
            error_log("getTaskDetailReport - Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Eliminar una actividad global (todas las asignaciones de la misma actividad)
     * @param string $titulo Título de la actividad
     * @param int $tipoActividadId ID del tipo de actividad
     * @return array Resultado de la operación con 'success' y 'deleted_count'
     */
    public function deleteGlobalTask($titulo, $tipoActividadId) {
        try {
            $this->db->beginTransaction();
            
            // Primero obtener todos los IDs de actividades que coincidan
            $sql = "SELECT id FROM actividades 
                    WHERE titulo = ? 
                    AND tipo_actividad_id = ? 
                    AND (tarea_pendiente = 1 OR tarea_pendiente IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$titulo, $tipoActividadId]);
            $activityIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($activityIds)) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No se encontraron actividades para eliminar',
                    'deleted_count' => 0
                ];
            }
            
            $count = count($activityIds);
            $placeholders = implode(',', array_fill(0, $count, '?'));
            
            // Eliminar evidencias asociadas
            $sqlEvidence = "DELETE FROM evidencias WHERE actividad_id IN ($placeholders)";
            $stmtEvidence = $this->db->prepare($sqlEvidence);
            $stmtEvidence->execute($activityIds);
            
            // Eliminar las actividades
            $sqlActivities = "DELETE FROM actividades WHERE id IN ($placeholders)";
            $stmtActivities = $this->db->prepare($sqlActivities);
            $stmtActivities->execute($activityIds);
            
            $this->db->commit();
            
            logActivity("Actividad global eliminada: '$titulo' (Tipo: $tipoActividadId, $count asignaciones)", 'DELETE');
            
            return [
                'success' => true,
                'deleted_count' => $count,
                'message' => 'Actividad eliminada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al eliminar actividad global: " . $e->getMessage());
            logActivity("Error al eliminar actividad global: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'deleted_count' => 0
            ];
        }
    }
    
    /**
     * Actualizar una actividad global (todas las asignaciones de la misma actividad)
     * @param string $tituloOriginal Título original de la actividad
     * @param int $tipoActividadId ID del tipo de actividad
     * @param array $updateData Datos a actualizar (titulo, descripcion, fecha_inicio, fecha_limite)
     * @return array Resultado de la operación con 'success' y 'updated_count'
     */
    public function updateGlobalTask($tituloOriginal, $tipoActividadId, $updateData) {
        try {
            $this->db->beginTransaction();
            
            // Construir la consulta de actualización dinámicamente
            $setClause = [];
            $params = [];
            
            if (isset($updateData['titulo'])) {
                $setClause[] = "titulo = ?";
                $params[] = $updateData['titulo'];
            }
            
            if (isset($updateData['descripcion'])) {
                $setClause[] = "descripcion = ?";
                $params[] = $updateData['descripcion'];
            }
            
            if (isset($updateData['fecha_actividad'])) {
                $setClause[] = "fecha_actividad = ?";
                $params[] = $updateData['fecha_actividad'];
            }
            
            if (isset($updateData['fecha_publicacion'])) {
                $setClause[] = "fecha_publicacion = ?";
                $params[] = $updateData['fecha_publicacion'];
            }
            
            if (isset($updateData['hora_publicacion'])) {
                $setClause[] = "hora_publicacion = ?";
                $params[] = $updateData['hora_publicacion'];
            }
            
            if (isset($updateData['fecha_cierre'])) {
                $setClause[] = "fecha_cierre = ?";
                $params[] = $updateData['fecha_cierre'];
            }
            
            if (isset($updateData['hora_cierre'])) {
                $setClause[] = "hora_cierre = ?";
                $params[] = $updateData['hora_cierre'];
            }
            
            // Siempre actualizar fecha_actualizacion
            $setClause[] = "fecha_actualizacion = NOW()";
            
            if (empty($setClause)) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No hay datos para actualizar',
                    'updated_count' => 0
                ];
            }
            
            // Agregar parámetros del WHERE
            $params[] = $tituloOriginal;
            $params[] = $tipoActividadId;
            
            $sql = "UPDATE actividades 
                    SET " . implode(', ', $setClause) . "
                    WHERE titulo = ? 
                    AND tipo_actividad_id = ? 
                    AND (tarea_pendiente = 1 OR tarea_pendiente IS NULL)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $updatedCount = $stmt->rowCount();
            
            if ($updatedCount === 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No se encontraron actividades para actualizar',
                    'updated_count' => 0
                ];
            }
            
            $this->db->commit();
            
            $newTitle = $updateData['titulo'] ?? $tituloOriginal;
            logActivity("Actividad global actualizada: '$tituloOriginal' -> '$newTitle' (Tipo: $tipoActividadId, $updatedCount asignaciones)", 'UPDATE');
            
            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => 'Actividad actualizada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al actualizar actividad global: " . $e->getMessage());
            logActivity("Error al actualizar actividad global: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'updated_count' => 0
            ];
        }
    }
    
    /**
     * Obtener información de una actividad por título y tipo
     * @param string $titulo Título de la actividad
     * @param int $tipoActividadId ID del tipo de actividad
     * @return array|null Información de la actividad o null si no se encuentra
     */
    public function getTaskInfoByTitleAndType($titulo, $tipoActividadId) {
        try {
            $sql = "SELECT titulo, descripcion, fecha_inicio, fecha_limite, tipo_actividad_id
                    FROM actividades 
                    WHERE titulo = ? 
                    AND tipo_actividad_id = ? 
                    AND (tarea_pendiente = 1 OR tarea_pendiente IS NULL)
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$titulo, $tipoActividadId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
            
        } catch (Exception $e) {
            error_log("Error al obtener información de actividad: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Eliminar múltiples actividades globales a la vez
     * @param array $actividades Array de arrays con 'titulo' y 'tipo_actividad_id'
     * @return array Resultado de la operación con 'success', 'total_activities' y 'total_deleted'
     */
    public function deleteMultipleGlobalTasks($actividades) {
        try {
            $this->db->beginTransaction();
            
            $totalDeleted = 0;
            $totalActivities = 0;
            $errors = [];
            
            foreach ($actividades as $actividad) {
                $titulo = $actividad['titulo'] ?? '';
                $tipoActividadId = $actividad['tipo_actividad_id'] ?? '';
                
                if (empty($titulo) || empty($tipoActividadId)) {
                    $errors[] = "Datos incompletos para una actividad";
                    continue;
                }
                
                // Obtener todos los IDs de actividades que coincidan
                $sql = "SELECT id FROM actividades 
                        WHERE titulo = ? 
                        AND tipo_actividad_id = ? 
                        AND (tarea_pendiente = 1 OR tarea_pendiente IS NULL)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$titulo, $tipoActividadId]);
                $activityIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($activityIds)) {
                    $errors[] = "No se encontraron actividades para '$titulo'";
                    continue;
                }
                
                $count = count($activityIds);
                $placeholders = implode(',', array_fill(0, $count, '?'));
                
                // Eliminar evidencias asociadas
                $sqlEvidence = "DELETE FROM evidencias WHERE actividad_id IN ($placeholders)";
                $stmtEvidence = $this->db->prepare($sqlEvidence);
                $stmtEvidence->execute($activityIds);
                
                // Eliminar las actividades
                $sqlActivities = "DELETE FROM actividades WHERE id IN ($placeholders)";
                $stmtActivities = $this->db->prepare($sqlActivities);
                $stmtActivities->execute($activityIds);
                
                $totalDeleted += $count;
                $totalActivities++;
                
                logActivity("Actividad eliminada (lote): '$titulo' (Tipo: $tipoActividadId, $count asignaciones)", 'DELETE');
            }
            
            if ($totalActivities === 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No se pudieron eliminar actividades: ' . implode(', ', $errors),
                    'total_activities' => 0,
                    'total_deleted' => 0
                ];
            }
            
            $this->db->commit();
            
            logActivity("Eliminación múltiple completada: $totalActivities actividades ($totalDeleted asignaciones)", 'DELETE');
            
            return [
                'success' => true,
                'total_activities' => $totalActivities,
                'total_deleted' => $totalDeleted,
                'message' => 'Actividades eliminadas exitosamente',
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al eliminar múltiples actividades globales: " . $e->getMessage());
            logActivity("Error al eliminar múltiples actividades globales: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'total_activities' => 0,
                'total_deleted' => 0
            ];
        }
    }
}
