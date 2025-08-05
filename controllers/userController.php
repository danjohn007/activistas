<?php
/**
 * Controlador de Usuarios
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/user.php';

class UserController {
    private $auth;
    private $userModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->userModel = new User();
    }
    
    // Mostrar formulario de login
    public function showLogin() {
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        include __DIR__ . '/../views/login.php';
    }
    
    // Procesar login
    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('/public/login.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('/public/login.php', 'Token de seguridad inválido', 'error');
        }
        
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            redirectWithMessage('/public/login.php', 'Email y contraseña son obligatorios', 'error');
        }
        
        $result = $this->auth->login($email, $password);
        
        if ($result['success']) {
            $this->redirectToDashboard();
        } else {
            redirectWithMessage('/public/login.php', $result['error'], 'error');
        }
    }
    
    // Mostrar formulario de registro
    public function showRegister() {
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        $liders = $this->userModel->getActiveLiders();
        include __DIR__ . '/../views/register.php';
    }
    
    // Procesar registro
    public function processRegister() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('/public/register.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('/public/register.php', 'Token de seguridad inválido', 'error');
        }
        
        $userData = [
            'nombre_completo' => cleanInput($_POST['nombre_completo'] ?? ''),
            'telefono' => cleanInput($_POST['telefono'] ?? ''),
            'email' => cleanInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'direccion' => cleanInput($_POST['direccion'] ?? ''),
            'rol' => cleanInput($_POST['rol'] ?? ''),
            'lider_id' => !empty($_POST['lider_id']) ? intval($_POST['lider_id']) : null
        ];
        
        // Procesar foto de perfil si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['foto_perfil'], __DIR__ . '/../public/assets/uploads/profiles');
            if ($uploadResult['success']) {
                $userData['foto_perfil'] = $uploadResult['filename'];
            } else {
                redirectWithMessage('/public/register.php', $uploadResult['error'], 'error');
            }
        }
        
        $result = $this->auth->register($userData);
        
        if ($result['success']) {
            redirectWithMessage('/public/login.php', 'Registro exitoso. Su cuenta está pendiente de aprobación.', 'success');
        } else {
            $error = isset($result['errors']) ? implode(', ', $result['errors']) : $result['error'];
            redirectWithMessage('/public/register.php', $error, 'error');
        }
    }
    
    // Cerrar sesión
    public function logout() {
        $this->auth->logout();
        redirectWithMessage('/public/login.php', 'Sesión cerrada exitosamente', 'success');
    }
    
    // Mostrar lista de usuarios (para admin/gestor)
    public function listUsers() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $filters = [];
        if (!empty($_GET['rol'])) {
            $filters['rol'] = cleanInput($_GET['rol']);
        }
        if (!empty($_GET['estado'])) {
            $filters['estado'] = cleanInput($_GET['estado']);
        }
        
        $users = $this->userModel->getAllUsers($filters);
        $stats = $this->userModel->getUserStats();
        
        include __DIR__ . '/../views/admin/users.php';
    }
    
    // Mostrar usuarios pendientes
    public function pendingUsers() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $pendingUsers = $this->userModel->getPendingUsers();
        
        include __DIR__ . '/../views/admin/pending_users.php';
    }
    
    // Aprobar usuario
    public function approveUser() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('/public/admin/pending_users.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('/public/admin/pending_users.php', 'Token de seguridad inválido', 'error');
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        $action = cleanInput($_POST['action'] ?? '');
        
        if ($userId <= 0 || !in_array($action, ['approve', 'reject'])) {
            redirectWithMessage('/public/admin/pending_users.php', 'Datos inválidos', 'error');
        }
        
        $status = $action === 'approve' ? 'activo' : 'desactivado';
        $result = $this->userModel->updateUserStatus($userId, $status);
        
        if ($result) {
            $message = $action === 'approve' ? 'Usuario aprobado exitosamente' : 'Usuario rechazado';
            redirectWithMessage('/public/admin/pending_users.php', $message, 'success');
        } else {
            redirectWithMessage('/public/admin/pending_users.php', 'Error al procesar la solicitud', 'error');
        }
    }
    
    // Editar usuario
    public function editUser() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $userId = intval($_GET['id'] ?? 0);
        if ($userId <= 0) {
            redirectWithMessage('/public/admin/users.php', 'Usuario no encontrado', 'error');
        }
        
        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            redirectWithMessage('/public/admin/users.php', 'Usuario no encontrado', 'error');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processEditUser($userId);
            return;
        }
        
        $liders = $this->userModel->getActiveLiders();
        include __DIR__ . '/../views/admin/edit_user.php';
    }
    
    // Procesar edición de usuario
    private function processEditUser($userId) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage("/public/admin/edit_user.php?id=$userId", 'Token de seguridad inválido', 'error');
        }
        
        $updateData = [
            'nombre_completo' => cleanInput($_POST['nombre_completo'] ?? ''),
            'telefono' => cleanInput($_POST['telefono'] ?? ''),
            'direccion' => cleanInput($_POST['direccion'] ?? ''),
        ];
        
        if (!empty($_POST['lider_id'])) {
            $updateData['lider_id'] = intval($_POST['lider_id']);
        }
        
        // Procesar nueva foto de perfil si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['foto_perfil'], __DIR__ . '/../public/assets/uploads/profiles');
            if ($uploadResult['success']) {
                $updateData['foto_perfil'] = $uploadResult['filename'];
            }
        }
        
        $result = $this->userModel->updateUser($userId, $updateData);
        
        if ($result) {
            redirectWithMessage("/public/admin/edit_user.php?id=$userId", 'Usuario actualizado exitosamente', 'success');
        } else {
            redirectWithMessage("/public/admin/edit_user.php?id=$userId", 'Error al actualizar usuario', 'error');
        }
    }
    
    // Cambiar estado de usuario
    public function changeUserStatus() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('/public/admin/users.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('/public/admin/users.php', 'Token de seguridad inválido', 'error');
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        $status = cleanInput($_POST['status'] ?? '');
        
        if ($userId <= 0 || !in_array($status, ['activo', 'suspendido', 'desactivado'])) {
            redirectWithMessage('/public/admin/users.php', 'Datos inválidos', 'error');
        }
        
        $result = $this->userModel->updateUserStatus($userId, $status);
        
        if ($result) {
            redirectWithMessage('/public/admin/users.php', 'Estado de usuario actualizado', 'success');
        } else {
            redirectWithMessage('/public/admin/users.php', 'Error al actualizar estado', 'error');
        }
    }
    
    // Perfil de usuario
    public function profile() {
        $this->auth->requireAuth();
        
        $user = $this->auth->getCurrentUser();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processProfileUpdate();
            return;
        }
        
        include __DIR__ . '/../views/profile.php';
    }
    
    // Procesar actualización de perfil
    private function processProfileUpdate() {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('/public/profile.php', 'Token de seguridad inválido', 'error');
        }
        
        $userId = $_SESSION['user_id'];
        $updateData = [
            'nombre_completo' => cleanInput($_POST['nombre_completo'] ?? ''),
            'telefono' => cleanInput($_POST['telefono'] ?? ''),
            'direccion' => cleanInput($_POST['direccion'] ?? ''),
        ];
        
        // Procesar nueva foto de perfil si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['foto_perfil'], __DIR__ . '/../public/assets/uploads/profiles');
            if ($uploadResult['success']) {
                $updateData['foto_perfil'] = $uploadResult['filename'];
            }
        }
        
        $result = $this->userModel->updateUser($userId, $updateData);
        
        if ($result) {
            // Actualizar nombre en sesión
            $_SESSION['user_name'] = $updateData['nombre_completo'];
            redirectWithMessage('/public/profile.php', 'Perfil actualizado exitosamente', 'success');
        } else {
            redirectWithMessage('/public/profile.php', 'Error al actualizar perfil', 'error');
        }
    }
    
    // Redireccionar al dashboard según el rol
    private function redirectToDashboard() {
        $role = $_SESSION['user_role'] ?? '';
        
        switch ($role) {
            case 'SuperAdmin':
                header('Location: /public/dashboards/admin.php');
                break;
            case 'Gestor':
                header('Location: /public/dashboards/gestor.php');
                break;
            case 'Líder':
                header('Location: /public/dashboards/lider.php');
                break;
            case 'Activista':
                header('Location: /public/dashboards/activista.php');
                break;
            default:
                header('Location: /public/');
                break;
        }
        exit();
    }
}
?>