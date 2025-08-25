<?php
/**
 * Modelo de Usuario
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class User {
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
            error_log("User Model Error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    // Obtener todos los usuarios
    public function getAllUsers($filters = []) {
        try {
            $sql = "SELECT u.*, l.nombre_completo as lider_nombre FROM usuarios u 
                    LEFT JOIN usuarios l ON u.lider_id = l.id 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['rol'])) {
                $sql .= " AND u.rol = ?";
                $params[] = $filters['rol'];
            }
            
            if (!empty($filters['estado'])) {
                $sql .= " AND u.estado = ?";
                $params[] = $filters['estado'];
            }
            
            if (!empty($filters['lider_id'])) {
                $sql .= " AND u.lider_id = ?";
                $params[] = $filters['lider_id'];
            }
            
            $sql .= " ORDER BY u.fecha_registro DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener usuarios: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener usuario por ID
    public function getUserById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, l.nombre_completo as lider_nombre 
                FROM usuarios u 
                LEFT JOIN usuarios l ON u.lider_id = l.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener usuario por ID: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Obtener líderes activos
    public function getActiveLiders() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre_completo 
                FROM usuarios 
                WHERE rol = 'Líder' AND estado = 'activo' 
                ORDER BY nombre_completo
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener líderes: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener usuarios pendientes de aprobación
    public function getPendingUsers() {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, l.nombre_completo as lider_nombre 
                FROM usuarios u 
                LEFT JOIN usuarios l ON u.lider_id = l.id 
                WHERE u.estado = 'pendiente' 
                ORDER BY u.fecha_registro DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener usuarios pendientes: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Actualizar estado de usuario
    public function updateUserStatus($userId, $status) {
        try {
            $validStatuses = ['pendiente', 'activo', 'suspendido', 'desactivado'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $stmt = $this->db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $result = $stmt->execute([$status, $userId]);
            
            if ($result) {
                logActivity("Estado de usuario ID $userId cambiado a: $status");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al actualizar estado de usuario: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Actualizar información de usuario
    public function updateUser($userId, $data) {
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['nombre_completo'])) {
                $fields[] = "nombre_completo = ?";
                $params[] = $data['nombre_completo'];
            }
            
            if (isset($data['telefono'])) {
                $fields[] = "telefono = ?";
                $params[] = $data['telefono'];
            }
            
            if (isset($data['direccion'])) {
                $fields[] = "direccion = ?";
                $params[] = $data['direccion'];
            }
            
            if (isset($data['foto_perfil'])) {
                $fields[] = "foto_perfil = ?";
                $params[] = $data['foto_perfil'];
            }
            
            if (isset($data['lider_id'])) {
                $fields[] = "lider_id = ?";
                $params[] = $data['lider_id'];
            }
            
            // Social media fields
            if (isset($data['facebook'])) {
                $fields[] = "facebook = ?";
                $params[] = $data['facebook'];
            }
            
            if (isset($data['instagram'])) {
                $fields[] = "instagram = ?";
                $params[] = $data['instagram'];
            }
            
            if (isset($data['tiktok'])) {
                $fields[] = "tiktok = ?";
                $params[] = $data['tiktok'];
            }
            
            if (isset($data['x'])) {
                $fields[] = "x = ?";
                $params[] = $data['x'];
            }
            
            if (isset($data['cuenta_pago'])) {
                $fields[] = "cuenta_pago = ?";
                $params[] = $data['cuenta_pago'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $userId;
            $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity("Usuario ID $userId actualizado");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al actualizar usuario: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener estadísticas de usuarios
    public function getUserStats() {
        try {
            // Verificar conexión antes de proceder
            if (!$this->db) {
                throw new Exception("No hay conexión a la base de datos disponible");
            }
            
            $stmt = $this->db->prepare("SELECT * FROM vista_estadisticas_usuarios");
            $stmt->execute();
            $stats = $stmt->fetchAll();
            
            // Convertir a formato más útil
            $result = [];
            foreach ($stats as $stat) {
                $result[$stat['rol']] = [
                    'total' => $stat['total'],
                    'activos' => $stat['activos'],
                    'pendientes' => $stat['pendientes'],
                    'suspendidos' => $stat['suspendidos']
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al obtener estadísticas de usuarios: " . $e->getMessage(), 'ERROR');
            
            // Retornar datos vacíos pero válidos en caso de error
            return [];
        }
    }
    
    // Obtener activistas de un líder
    public function getActivistsOfLeader($liderId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM usuarios 
                WHERE rol = 'Activista' AND lider_id = ? AND estado = 'activo'
                ORDER BY nombre_completo
            ");
            $stmt->execute([$liderId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener activistas del líder: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Buscar usuarios
    public function searchUsers($query, $filters = []) {
        try {
            $sql = "SELECT u.*, l.nombre_completo as lider_nombre FROM usuarios u 
                    LEFT JOIN usuarios l ON u.lider_id = l.id 
                    WHERE (u.nombre_completo LIKE ? OR u.email LIKE ? OR u.telefono LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%"];
            
            if (!empty($filters['rol'])) {
                $sql .= " AND u.rol = ?";
                $params[] = $filters['rol'];
            }
            
            if (!empty($filters['estado'])) {
                $sql .= " AND u.estado = ?";
                $params[] = $filters['estado'];
            }
            
            $sql .= " ORDER BY u.nombre_completo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error en búsqueda de usuarios: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Enhanced search for SuperAdmin - includes activity titles
    public function searchUsersWithActivities($query, $filters = []) {
        try {
            $sql = "SELECT DISTINCT u.*, l.nombre_completo as lider_nombre 
                    FROM usuarios u 
                    LEFT JOIN usuarios l ON u.lider_id = l.id 
                    LEFT JOIN actividades a ON u.id = a.usuario_id
                    WHERE (u.nombre_completo LIKE ? OR u.email LIKE ? OR u.telefono LIKE ? OR a.titulo LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%", "%$query%"];
            
            if (!empty($filters['rol'])) {
                $sql .= " AND u.rol = ?";
                $params[] = $filters['rol'];
            }
            
            if (!empty($filters['estado'])) {
                $sql .= " AND u.estado = ?";
                $params[] = $filters['estado'];
            }
            
            $sql .= " ORDER BY u.nombre_completo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error en búsqueda avanzada de usuarios: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
        // Cambiar contraseña
    public function changePassword($userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
            $result = $stmt->execute([$passwordHash, $userId]);
            
            if ($result) {
                logActivity("Contraseña cambiada para usuario ID: $userId");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al cambiar contraseña: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener todos los usuarios activos para asignación de tareas
    public function getAllActiveUsers() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre_completo, rol, email
                FROM usuarios 
                WHERE estado = 'activo' 
                ORDER BY rol, nombre_completo
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener usuarios activos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
}
?>