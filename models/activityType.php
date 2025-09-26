<?php
/**
 * Modelo de Tipos de Actividades
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class ActivityType {
    protected $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            if (!$this->db) {
                throw new Exception("No se pudo establecer conexión a la base de datos");
            }
        } catch (Exception $e) {
            error_log("ActivityType Model Error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    // Obtener todos los tipos de actividades
    public function getAllActivityTypes($includeInactive = false) {
        try {
            $sql = "SELECT * FROM tipos_actividades";
            if (!$includeInactive) {
                $sql .= " WHERE activo = 1";
            }
            $sql .= " ORDER BY nombre";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener tipos de actividades: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener tipo de actividad por ID
    public function getActivityTypeById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM tipos_actividades WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener tipo de actividad por ID: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Obtener tipo de actividad por nombre
    public function getActivityTypeByName($name) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM tipos_actividades WHERE nombre = ?");
            $stmt->execute([$name]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener tipo de actividad por nombre: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Crear nuevo tipo de actividad
    public function createActivityType($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tipos_actividades (nombre, descripcion, activo)
                VALUES (?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['activo']
            ]);
            
            if ($result) {
                $id = $this->db->lastInsertId();
                logActivity("Nuevo tipo de actividad creado: {$data['nombre']} (ID: $id)");
                return $id;
            }
            
            return false;
        } catch (Exception $e) {
            logActivity("Error al crear tipo de actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Actualizar tipo de actividad
    public function updateActivityType($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tipos_actividades 
                SET nombre = ?, descripcion = ?, activo = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['activo'],
                $id
            ]);
            
            if ($result) {
                logActivity("Tipo de actividad actualizado: ID $id");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al actualizar tipo de actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Desactivar tipo de actividad
    public function deactivateActivityType($id) {
        try {
            $stmt = $this->db->prepare("UPDATE tipos_actividades SET activo = 0 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity("Tipo de actividad desactivado: ID $id");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al desactivar tipo de actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Activar tipo de actividad
    public function activateActivityType($id) {
        try {
            $stmt = $this->db->prepare("UPDATE tipos_actividades SET activo = 1 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity("Tipo de actividad activado: ID $id");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al activar tipo de actividad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Verificar si un tipo de actividad está siendo usado
    public function isActivityTypeInUse($id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM actividades WHERE tipo_actividad_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            logActivity("Error al verificar uso de tipo de actividad: " . $e->getMessage(), 'ERROR');
            return true; // Asumimos que está en uso para evitar eliminaciones accidentales
        }
    }
    
    // Obtener estadísticas de uso de tipos de actividades
    public function getActivityTypeStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ta.id,
                    ta.nombre,
                    ta.descripcion,
                    ta.activo,
                    COUNT(a.id) as total_actividades,
                    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas
                FROM tipos_actividades ta
                LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id
                GROUP BY ta.id, ta.nombre, ta.descripcion, ta.activo
                ORDER BY total_actividades DESC, ta.nombre
            ");
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener estadísticas de tipos de actividades: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Eliminar tipo de actividad (solo si no está siendo usado)
    public function deleteActivityType($id) {
        try {
            // Verificar si está siendo usado
            if ($this->isActivityTypeInUse($id)) {
                throw new Exception("No se puede eliminar el tipo de actividad porque está siendo usado en actividades existentes");
            }
            
            $stmt = $this->db->prepare("DELETE FROM tipos_actividades WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity("Tipo de actividad eliminado: ID $id");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al eliminar tipo de actividad: " . $e->getMessage(), 'ERROR');
            throw $e; // Re-throw to let the caller handle the specific error message
        }
    }
}
?>