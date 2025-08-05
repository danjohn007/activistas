<?php
/**
 * Sistema de autenticación
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        startSession();
    }
    
    // Iniciar sesión
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                
                // Actualizar último acceso
                $this->updateLastAccess($user['id']);
                
                logActivity("Usuario {$user['email']} inició sesión");
                return ['success' => true, 'user' => $user];
            } else {
                logActivity("Intento de login fallido para: $email", 'WARNING');
                return ['success' => false, 'error' => 'Credenciales incorrectas'];
            }
        } catch (Exception $e) {
            logActivity("Error en login: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => 'Error del sistema'];
        }
    }
    
    // Cerrar sesión
    public function logout() {
        if (isset($_SESSION['user_email'])) {
            logActivity("Usuario {$_SESSION['user_email']} cerró sesión");
        }
        
        session_destroy();
        return true;
    }
    
    // Verificar si el usuario está autenticado
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Obtener usuario actual
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener usuario actual: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    // Verificar permisos
    public function checkPermission($requiredRoles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return hasPermission($_SESSION['user_role'], $requiredRoles);
    }
    
    // Registrar nuevo usuario
    public function register($userData) {
        try {
            // Validar datos
            $errors = $this->validateUserData($userData);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Verificar si el email ya existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'El email ya está registrado'];
            }
            
            // Hash de la contraseña
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Generar token de verificación
            $verificationToken = generateRandomToken();
            
            // Insertar usuario
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (nombre_completo, telefono, email, password_hash, direccion, rol, lider_id, token_verificacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userData['nombre_completo'],
                $userData['telefono'],
                $userData['email'],
                $passwordHash,
                $userData['direccion'],
                $userData['rol'],
                $userData['lider_id'] ?? null,
                $verificationToken
            ]);
            
            $userId = $this->db->lastInsertId();
            
            logActivity("Nuevo usuario registrado: {$userData['email']} (ID: $userId)");
            
            return ['success' => true, 'user_id' => $userId, 'token' => $verificationToken];
            
        } catch (Exception $e) {
            logActivity("Error en registro: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => 'Error del sistema'];
        }
    }
    
    // Validar datos de usuario
    private function validateUserData($data) {
        $errors = [];
        
        if (empty($data['nombre_completo'])) {
            $errors[] = 'El nombre completo es obligatorio';
        }
        
        if (empty($data['telefono'])) {
            $errors[] = 'El teléfono es obligatorio';
        }
        
        if (empty($data['email']) || !isValidEmail($data['email'])) {
            $errors[] = 'Email válido es obligatorio';
        }
        
        if (empty($data['password']) || !isStrongPassword($data['password'])) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial';
        }
        
        if (empty($data['direccion'])) {
            $errors[] = 'La dirección es obligatoria';
        }
        
        if (!in_array($data['rol'], ['Líder', 'Activista'])) {
            $errors[] = 'Rol no válido';
        }
        
        if ($data['rol'] === 'Activista' && empty($data['lider_id'])) {
            $errors[] = 'Los activistas deben seleccionar un líder';
        }
        
        return $errors;
    }
    
    // Actualizar último acceso
    private function updateLastAccess($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET fecha_actualizacion = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            logActivity("Error al actualizar último acceso: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Verificar email
    public function verifyEmail($token) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE token_verificacion = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $this->db->prepare("UPDATE usuarios SET email_verificado = 1, token_verificacion = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                logActivity("Email verificado para usuario ID: {$user['id']}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            logActivity("Error en verificación de email: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Requerir autenticación - redirige a login si no está autenticado
     * 
     * IMPORTANTE: Usar rutas RELATIVAS solamente
     * 
     * @param string $redirectUrl Ruta relativa de redirección (ej: 'login.php')
     */
    public function requireAuth($redirectUrl = 'login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: " . url($redirectUrl));
            exit();
        }
    }
    
    /**
     * Requerir rol específico - verifica autenticación y permisos
     * 
     * IMPORTANTE: Usar rutas RELATIVAS solamente
     * 
     * @param array $roles Roles permitidos (ej: ['SuperAdmin', 'Gestor'])
     * @param string $redirectUrl Ruta relativa de redirección en caso de no tener permisos
     */
    public function requireRole($roles, $redirectUrl = '') {
        $this->requireAuth();
        
        if (!$this->checkPermission($roles)) {
            redirectWithMessage($redirectUrl ?: '', 'No tiene permisos para acceder a esta página', 'error');
        }
    }
}

// Función global para obtener instancia de Auth
function getAuth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}
?>