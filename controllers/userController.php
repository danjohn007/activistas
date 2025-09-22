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
            redirectWithMessage('login.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('login.php', 'Token de seguridad inválido', 'error');
        }
        
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            redirectWithMessage('login.php', 'Email y contraseña son obligatorios', 'error');
        }
        
        $result = $this->auth->login($email, $password);
        
        if ($result['success']) {
            $this->redirectToDashboard();
        } else {
            redirectWithMessage('login.php', $result['error'], 'error');
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
            redirectWithMessage('register.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('register.php', 'Token de seguridad inválido', 'error');
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
            $uploadResult = uploadFile($_FILES['foto_perfil'], __DIR__ . '/../public/assets/uploads/profiles', ['jpg', 'jpeg', 'png', 'gif'], true);
            if ($uploadResult['success']) {
                $userData['foto_perfil'] = $uploadResult['filename'];
            } else {
                redirectWithMessage('register.php', $uploadResult['error'], 'error');
            }
        }
        
        $result = $this->auth->register($userData);
        
        if ($result['success']) {
            redirectWithMessage('login.php', 'Registro exitoso. Su cuenta está pendiente de aprobación.', 'success');
        } else {
            $error = isset($result['errors']) ? implode(', ', $result['errors']) : $result['error'];
            redirectWithMessage('register.php', $error, 'error');
        }
    }
    
    // Cerrar sesión
    public function logout() {
        $this->auth->logout();
        redirectWithMessage('login.php', 'Sesión cerrada exitosamente', 'success');
    }
    
    // Mostrar lista de usuarios (para admin/gestor)
    public function listUsers() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $currentUser = $this->auth->getCurrentUser();
        $filters = [];
        if (!empty($_GET['rol'])) {
            $filters['rol'] = cleanInput($_GET['rol']);
        }
        if (!empty($_GET['estado'])) {
            $filters['estado'] = cleanInput($_GET['estado']);
        }
        
        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20; // 20 usuarios por página
        
        // Si hay búsqueda, usar enhanced search for SuperAdmin or regular search for others
        $search = cleanInput($_GET['search'] ?? '');
        if (!empty($search)) {
            if ($currentUser['rol'] === 'SuperAdmin') {
                // SuperAdmin gets enhanced search including activity titles
                $users = $this->userModel->searchUsersWithActivities($search, $filters);
                $totalUsers = count($users); // For search, we count all results
                // Apply pagination to search results
                $users = array_slice($users, ($page - 1) * $perPage, $perPage);
            } else {
                // Other roles use standard search
                $users = $this->userModel->searchUsers($search, $filters);
                $totalUsers = count($users); // For search, we count all results
                // Apply pagination to search results
                $users = array_slice($users, ($page - 1) * $perPage, $perPage);
            }
        } else {
            // Use the new method with compliance data and pagination
            $users = $this->userModel->getAllUsersWithCompliance($filters, $page, $perPage);
            $totalUsers = $this->userModel->getTotalUsersWithFilters($filters);
        }
        
        // Calculate pagination info
        $totalPages = ceil($totalUsers / $perPage);
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => max(1, $page - 1),
            'next_page' => min($totalPages, $page + 1)
        ];
        
        $stats = $this->userModel->getUserStats();
        
        include __DIR__ . '/../views/admin/users.php';
    }
    
    // Mostrar usuarios pendientes
    public function pendingUsers() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $pendingUsers = $this->userModel->getPendingUsers();
        $liders = $this->userModel->getActiveLiders();
        
        include __DIR__ . '/../views/admin/pending_users.php';
    }
    
    // Aprobar usuario
    public function approveUser() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('admin/pending_users.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('admin/pending_users.php', 'Token de seguridad inválido', 'error');
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        $action = cleanInput($_POST['action'] ?? '');
        
        if ($userId <= 0 || !in_array($action, ['approve', 'reject'])) {
            redirectWithMessage('admin/pending_users.php', 'Datos inválidos', 'error');
        }
        
        if ($action === 'approve') {
            // Handle approval with vigencia, rol and leader assignment
            $vigenciaHasta = cleanInput($_POST['vigencia_hasta'] ?? '');
            $vigenciaHasta = !empty($vigenciaHasta) ? $vigenciaHasta : null;
            
            $rol = cleanInput($_POST['rol'] ?? '');
            $liderId = !empty($_POST['lider_id']) ? intval($_POST['lider_id']) : null;
            
            // Validate role
            $currentUser = $this->auth->getCurrentUser();
            $validRoles = ['Activista', 'Líder'];
            if ($currentUser['rol'] === 'SuperAdmin') {
                $validRoles[] = 'Gestor';
                $validRoles[] = 'SuperAdmin';
            }
            
            if (!in_array($rol, $validRoles)) {
                redirectWithMessage('admin/pending_users.php', 'Tipo de usuario inválido', 'error');
                return;
            }
            
            // If role is not Activista, clear leader assignment
            if ($rol !== 'Activista') {
                $liderId = null;
            }
            
            $result = $this->userModel->approveUserWithRoleAndLeader($userId, $vigenciaHasta, $rol, $liderId);
            $message = $result ? 'Usuario aprobado exitosamente' : 'Error al aprobar usuario';
        } else {
            // Handle rejection
            $result = $this->userModel->updateUserStatus($userId, 'desactivado');
            $message = $result ? 'Usuario rechazado' : 'Error al rechazar usuario';
        }
        
        if ($result) {
            redirectWithMessage('admin/pending_users.php', $message, 'success');
        } else {
            redirectWithMessage('admin/pending_users.php', 'Error al procesar la solicitud', 'error');
        }
    }
    
    // Editar usuario
    public function editUser() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $userId = intval($_GET['id'] ?? 0);
        if ($userId <= 0) {
            redirectWithMessage('admin/users.php', 'Usuario no encontrado', 'error');
        }
        
        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            redirectWithMessage('admin/users.php', 'Usuario no encontrado', 'error');
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
            redirectWithMessage("admin/edit_user.php?id=$userId", 'Token de seguridad inválido', 'error');
        }
        
        $updateData = [
            'nombre_completo' => cleanInput($_POST['nombre_completo'] ?? ''),
            'telefono' => cleanInput($_POST['telefono'] ?? ''),
            'direccion' => cleanInput($_POST['direccion'] ?? ''),
            'facebook' => cleanInput($_POST['facebook'] ?? ''),
            'instagram' => cleanInput($_POST['instagram'] ?? ''),
            'tiktok' => cleanInput($_POST['tiktok'] ?? ''),
            'x' => cleanInput($_POST['x'] ?? ''),
            'cuenta_pago' => cleanInput($_POST['cuenta_pago'] ?? ''),
        ];
        
        // Handle rol (user type) editing - only for SuperAdmin and Gestor
        $currentUser = $this->auth->getCurrentUser();
        if (in_array($currentUser['rol'], ['SuperAdmin', 'Gestor']) && !empty($_POST['rol'])) {
            $validRoles = ['Activista', 'Líder'];
            
            // SuperAdmin can assign any role
            if ($currentUser['rol'] === 'SuperAdmin') {
                $validRoles[] = 'Gestor';
                $validRoles[] = 'SuperAdmin';
            }
            
            $newRol = cleanInput($_POST['rol']);
            if (in_array($newRol, $validRoles)) {
                $updateData['rol'] = $newRol;
                
                // If changing to non-Activista role, clear leader assignment
                if ($newRol !== 'Activista') {
                    $updateData['lider_id'] = null;
                }
            }
        }
        
        // Validar URLs de redes sociales
        $socialMediaFields = ['facebook', 'instagram', 'tiktok', 'x'];
        foreach ($socialMediaFields as $field) {
            if (!empty($updateData[$field]) && !isValidUrl($updateData[$field])) {
                redirectWithMessage("admin/edit_user.php?id=$userId", "URL de $field no válida", 'error');
                return;
            }
        }
        
        if (!empty($_POST['lider_id'])) {
            $updateData['lider_id'] = intval($_POST['lider_id']);
        }
        
        // Handle vigencia (only for SuperAdmin and Gestor)
        $currentUser = $this->auth->getCurrentUser();
        if (in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            $vigenciaHasta = cleanInput($_POST['vigencia_hasta'] ?? '');
            
            // Validate vigencia date if provided
            if (!empty($vigenciaHasta)) {
                $date = DateTime::createFromFormat('Y-m-d', $vigenciaHasta);
                if (!$date || $date->format('Y-m-d') !== $vigenciaHasta) {
                    redirectWithMessage("admin/edit_user.php?id=$userId", 'Formato de fecha de vigencia inválido', 'error');
                    return;
                }
                
                // Verificar que la fecha no sea anterior a hoy
                if ($date < new DateTime()) {
                    redirectWithMessage("admin/edit_user.php?id=$userId", 'La fecha de vigencia no puede ser anterior al día actual', 'error');
                    return;
                }
            }
            
            $updateData['vigencia_hasta'] = !empty($vigenciaHasta) ? $vigenciaHasta : null;
        }
        
        // Procesar nueva foto de perfil si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['foto_perfil'], __DIR__ . '/../public/assets/uploads/profiles', ['jpg', 'jpeg', 'png', 'gif'], true);
            if ($uploadResult['success']) {
                $updateData['foto_perfil'] = $uploadResult['filename'];
            }
        }
        
        $result = $this->userModel->updateUser($userId, $updateData);
        
        if ($result) {
            redirectWithMessage("admin/edit_user.php?id=$userId", 'Usuario actualizado exitosamente', 'success');
        } else {
            redirectWithMessage("admin/edit_user.php?id=$userId", 'Error al actualizar usuario', 'error');
        }
    }
    
    // Cambiar estado de usuario
    public function changeUserStatus() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('admin/users.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('admin/users.php', 'Token de seguridad inválido', 'error');
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        $status = cleanInput($_POST['status'] ?? '');
        
        if ($userId <= 0 || !in_array($status, ['activo', 'suspendido', 'desactivado'])) {
            redirectWithMessage('admin/users.php', 'Datos inválidos', 'error');
        }
        
        $result = $this->userModel->updateUserStatus($userId, $status);
        
        if ($result) {
            redirectWithMessage('admin/users.php', 'Estado de usuario actualizado', 'success');
        } else {
            redirectWithMessage('admin/users.php', 'Error al actualizar estado', 'error');
        }
    }
    
    // Perfil de usuario
    public function profile() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        $userId = intval($_GET['user_id'] ?? 0);
        
        // Si no se especifica user_id o es 0, mostrar perfil propio
        if ($userId <= 0) {
            $user = $currentUser;
            $isOwnProfile = true;
        } else {
            // Verificar permisos para ver otros perfiles
            if ($currentUser['rol'] === 'Activista' && $userId != $currentUser['id']) {
                redirectWithMessage('profile.php', 'No tienes permisos para ver este perfil', 'error');
            }
            
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                redirectWithMessage('profile.php', 'Usuario no encontrado', 'error');
            }
            
            $isOwnProfile = ($userId == $currentUser['id']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$isOwnProfile) {
                redirectWithMessage('profile.php?user_id=' . $userId, 'No puedes editar este perfil', 'error');
            }
            $this->processProfileUpdate();
            return;
        }
        
        include __DIR__ . '/../views/profile.php';
    }
    
    // Procesar actualización de perfil
    private function processProfileUpdate() {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('profile.php', 'Token de seguridad inválido', 'error');
        }
        
        $userId = $_SESSION['user_id'];
        $updateData = [
            'nombre_completo' => cleanInput($_POST['nombre_completo'] ?? ''),
            'telefono' => cleanInput($_POST['telefono'] ?? ''),
            'direccion' => cleanInput($_POST['direccion'] ?? ''),
            'facebook' => cleanInput($_POST['facebook'] ?? ''),
            'instagram' => cleanInput($_POST['instagram'] ?? ''),
            'tiktok' => cleanInput($_POST['tiktok'] ?? ''),
            'x' => cleanInput($_POST['x'] ?? ''),
            'cuenta_pago' => cleanInput($_POST['cuenta_pago'] ?? ''),
        ];
        
        // Validar URLs de redes sociales
        $socialMediaFields = ['facebook', 'instagram', 'tiktok', 'x'];
        foreach ($socialMediaFields as $field) {
            if (!empty($updateData[$field]) && !isValidUrl($updateData[$field])) {
                redirectWithMessage('profile.php', "URL de $field no válida", 'error');
                return;
            }
        }
        
        // Procesar nueva foto de perfil si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['foto_perfil'], __DIR__ . '/../public/assets/uploads/profiles', ['jpg', 'jpeg', 'png', 'gif'], true);
            if ($uploadResult['success']) {
                $updateData['foto_perfil'] = $uploadResult['filename'];
            }
        }
        
        $result = $this->userModel->updateUser($userId, $updateData);
        
        if ($result) {
            // Actualizar nombre en sesión
            $_SESSION['user_name'] = $updateData['nombre_completo'];
            redirectWithMessage('profile.php', 'Perfil actualizado exitosamente', 'success');
        } else {
            redirectWithMessage('profile.php', 'Error al actualizar perfil', 'error');
        }
    }
    
    /**
     * Redireccionar al dashboard según el rol del usuario
     * Utiliza rutas relativas compatibles con subdirectorios
     */
    private function redirectToDashboard() {
        $role = $_SESSION['user_role'] ?? '';
        
        switch ($role) {
            case 'SuperAdmin':
                redirectWithMessage('dashboards/admin.php', '', 'info');
                break;
            case 'Gestor':
                redirectWithMessage('dashboards/gestor.php', '', 'info');
                break;
            case 'Líder':
                redirectWithMessage('tasks/', '', 'info');
                break;
            case 'Activista':
                redirectWithMessage('tasks/', '', 'info');
                break;
            default:
                redirectWithMessage('login.php', 'Rol no válido', 'error');
                break;
        }
    }
}
?>