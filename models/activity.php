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
            $stmt = $this->db->prepare("
                INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad, lugar, alcance_estimado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['usuario_id'],
                $data['tipo_actividad_id'],
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['fecha_actividad'],
                $data['lugar'] ?? null,
                $data['alcance_estimado'] ?? 0
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
            
            $sql = "SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre 
                    FROM actividades a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
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
            
            $sql .= " ORDER BY a.fecha_actividad DESC, a.fecha_creacion DESC";
            
            if (!empty($filters['limit'])) {
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
    
    // Obtener actividad por ID
    public function getActivityById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre 
                FROM actividades a 
                JOIN usuarios u ON a.usuario_id = u.id 
                JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
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
            
            if (isset($data['alcance_estimado'])) {
                $fields[] = "alcance_estimado = ?";
                $params[] = $data['alcance_estimado'];
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
            $stmt = $this->db->prepare("
                INSERT INTO evidencias (actividad_id, tipo_evidencia, archivo, contenido)
                VALUES (?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$activityId, $type, $file, $content]);
            
            if ($result) {
                $evidenceId = $this->db->lastInsertId();
                logActivity("Evidencia agregada: ID $evidenceId para actividad $activityId");
                return $evidenceId;
            }
            
            return false;
        } catch (Exception $e) {
            logActivity("Error al agregar evidencia: " . $e->getMessage(), 'ERROR');
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
                        COUNT(CASE WHEN a.estado = 'programada' THEN 1 END) as programadas,
                        SUM(a.alcance_estimado) as alcance_total
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