<?php
/**
 * Controlador de Usuarios
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/group.php';

class UserController {
    private $auth;
    private $userModel;
    private $groupModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->userModel = new User();
        $this->groupModel = new Group();
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
            'municipio' => cleanInput($_POST['municipio'] ?? ''),
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
        if (!empty($_GET['municipio'])) {
            $filters['municipio'] = cleanInput($_GET['municipio']);
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
        $municipioStats = [];
        if (($currentUser['rol'] ?? '') === 'SuperAdmin') {
            $municipioStats = $this->userModel->getMunicipioStats();
        }
        
        include __DIR__ . '/../views/admin/users.php';
    }
    
    // Mostrar usuarios pendientes
    public function pendingUsers() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor']);
        
        $pendingUsers = $this->userModel->getPendingUsers();
        $liders = $this->userModel->getActiveLiders();
        $groups = $this->groupModel->getAllGroups();
        
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
            $grupoId = !empty($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null;
            
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
            
            $result = $this->userModel->approveUserWithRoleLeaderAndGroup($userId, $vigenciaHasta, $rol, $liderId, $grupoId);
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
        
        // Load groups for SuperAdmin and Gestor
        $groups = [];
        if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor'])) {
            require_once __DIR__ . '/../models/group.php';
            $groupModel = new Group();
            $groups = $groupModel->getActiveGroups();
        }
        
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
            'municipio' => cleanInput($_POST['municipio'] ?? ''),
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

        if (!empty($updateData['municipio']) && !isValidMunicipio($updateData['municipio'])) {
            redirectWithMessage("admin/edit_user.php?id=$userId", 'Municipio no válido', 'error');
            return;
        }
        
        if (!empty($_POST['lider_id'])) {
            $updateData['lider_id'] = intval($_POST['lider_id']);
        }
        
        // Handle group assignment (for SuperAdmin and Gestor)
        if (in_array($currentUser['rol'], ['SuperAdmin', 'Gestor']) && isset($_POST['grupo_id'])) {
            $groupId = !empty($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null;
            $updateData['grupo_id'] = $groupId;
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
            'municipio' => cleanInput($_POST['municipio'] ?? ''),
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

        $currentRole = $_SESSION['user_role'] ?? '';
        if (in_array($currentRole, ['Líder', 'Activista'])) {
            if (empty($updateData['municipio'])) {
                redirectWithMessage('profile.php', 'El municipio es obligatorio', 'error');
                return;
            }
            if (!isValidMunicipio($updateData['municipio'])) {
                redirectWithMessage('profile.php', 'Municipio no válido', 'error');
                return;
            }
        } elseif (!empty($updateData['municipio']) && !isValidMunicipio($updateData['municipio'])) {
            redirectWithMessage('profile.php', 'Municipio no válido', 'error');
            return;
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
            $_SESSION['user_municipio'] = $updateData['municipio'] ?? null;
            redirectWithMessage('profile.php', 'Perfil actualizado exitosamente', 'success');
        } else {
            redirectWithMessage('profile.php', 'Error al actualizar perfil', 'error');
        }
    }
    
    /**
     * Mostrar formulario de recuperación de contraseña
     */
    public function showForgotPassword() {
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        include __DIR__ . '/../views/forgot-password.php';
    }
    
    /**
     * Procesar solicitud de recuperación de contraseña
     */
    public function processForgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('forgot-password.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('forgot-password.php', 'Token de seguridad inválido', 'error');
        }
        
        $email = cleanInput($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('forgot-password.php', 'Por favor ingresa un correo válido', 'error');
        }
        
        // Verificar si el usuario existe
        $user = $this->userModel->getUserByEmail($email);
        
        if (!$user) {
            // Usuario no existe - mostrar error claro
            redirectWithMessage('forgot-password.php', 'El correo ingresado no está registrado en el sistema', 'error');
            return;
        }
        
        // Usuario existe - proceder con recuperación
        // Generar token de recuperación
        $token = bin2hex(random_bytes(32));
        
        // Calcular expiración compensando el desfase del servidor MySQL
        // El servidor guarda con +6 horas, así que restamos 6 horas al calcular
        $expiresDate = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $expiresDate->modify('+2 hours'); // Agregar 2 horas de validez
        $expiresDate->modify('-6 hours'); // Compensar desfase del servidor MySQL
        $expires = $expiresDate->format('Y-m-d H:i:s');
        
        // Guardar token en base de datos
        if ($this->userModel->createPasswordResetToken($user['id'], $token, $expires)) {
            // Construir enlace de recuperación
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['PHP_SELF']);
            $resetLink = $protocol . $host . rtrim($path, '/') . '/reset-password.php?token=' . $token;
            
            // Enviar correo
            $emailSent = $this->sendPasswordResetEmail($email, $user['nombre_completo'], $resetLink);
            
            if ($emailSent) {
                redirectWithMessage('forgot-password.php', 'Se ha enviado un enlace de recuperación a tu correo. El enlace expirará en 2 horas.', 'success');
            } else {
                logActivity("Error al enviar correo de recuperación a: $email", 'ERROR');
                redirectWithMessage('forgot-password.php', 'Error al enviar el correo. Por favor intenta nuevamente o contacta al administrador.', 'error');
                return;
            }
        } else {
            redirectWithMessage('forgot-password.php', 'Error al procesar la solicitud. Por favor intenta nuevamente.', 'error');
            return;
        }
    }
    
    /**
     * Mostrar formulario de restablecer contraseña
     */
    public function showResetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            redirectWithMessage('login.php', 'Token inválido', 'error');
        }
        
        // Verificar si el token es válido
        $validToken = $this->userModel->validatePasswordResetToken($token);
        
        include __DIR__ . '/../views/reset-password.php';
    }
    
    /**
     * Procesar restablecimiento de contraseña
     */
    public function processResetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('login.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('login.php', 'Token de seguridad inválido', 'error');
        }
        
        $token = cleanInput($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            redirectWithMessage("reset-password.php?token=$token", 'Todos los campos son obligatorios', 'error');
        }
        
        if ($password !== $passwordConfirm) {
            redirectWithMessage("reset-password.php?token=$token", 'Las contraseñas no coinciden', 'error');
        }
        
        if (strlen($password) < 8) {
            redirectWithMessage("reset-password.php?token=$token", 'La contraseña debe tener al menos 8 caracteres', 'error');
        }
        
        // Validar token y obtener usuario
        $tokenData = $this->userModel->validatePasswordResetToken($token);
        
        if (!$tokenData) {
            redirectWithMessage('login.php', 'El enlace de recuperación es inválido o ha expirado', 'error');
        }
        
        // Actualizar contraseña
        if ($this->userModel->updatePassword($tokenData['user_id'], $password)) {
            // Marcar token como usado
            $this->userModel->markTokenAsUsed($token);
            
            redirectWithMessage('login.php', 'Contraseña actualizada exitosamente. Ya puedes iniciar sesión', 'success');
        } else {
            redirectWithMessage("reset-password.php?token=$token", 'Error al actualizar la contraseña', 'error');
        }
    }
    
    /**
     * Enviar correo de recuperación de contraseña
     */
    private function sendPasswordResetEmail($to, $name, $resetLink) {
        // Primero intentar con PHPMailer (SMTP) - más confiable y menos spam
        $phpmailerPath = __DIR__ . '/../includes/phpmailer/PHPMailerAutoload.php';
        if (file_exists($phpmailerPath)) {
            logActivity("Enviando correo con PHPMailer (SMTP) a: $to", 'INFO');
            $result = $this->sendEmailWithPHPMailer($to, $name, $resetLink);
            if ($result) {
                return true;
            }
            logActivity("PHPMailer falló, intentando con mail() como respaldo", 'WARNING');
        }
        
        // Si PHPMailer falla o no está disponible, usar mail() como respaldo
        $result = $this->sendEmailWithMailFunction($to, $name, $resetLink);
        
        if ($result) {
            return true;
        }
        
        logActivity("Error: No se pudo enviar correo a: $to con ningún método", 'ERROR');
        return false;
    }
    
    /**
     * Enviar correo usando PHPMailer (recomendado)
     */
    private function sendEmailWithPHPMailer($to, $name, $resetLink) {
        try {
            // Intentar cargar PHPMailer - usa autoload que ya crea los alias
            $autoloadPath = __DIR__ . '/../includes/phpmailer/PHPMailerAutoload.php';
            if (!file_exists($autoloadPath)) {
                logActivity("PHPMailer no encontrado, usando mail() como respaldo", 'WARNING');
                return $this->sendEmailWithMailFunction($to, $name, $resetLink);
            }
            
            require_once $autoloadPath;
            
            // Verificar que la clase esté disponible
            if (!class_exists('PHPMailer')) {
                logActivity("Clase PHPMailer no disponible después de cargar autoload", 'ERROR');
                return $this->sendEmailWithMailFunction($to, $name, $resetLink);
            }
            
            $mail = new PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'ejercitodigital.com.mx';
            $mail->SMTPAuth = true;
            $mail->Username = 'resetpassword@ejercitodigital.com.mx';
            $mail->Password = 'Danjohn007';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            
            // Configuración adicional
            $mail->SMTPDebug = 0;
            $mail->Timeout = 30;
            $mail->SMTPKeepAlive = false;
            
            // Remitente y destinatario
            $mail->setFrom('resetpassword@ejercitodigital.com.mx', 'Activistas Digitales');
            $mail->addAddress($to, $name);
            
            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de Contraseña - Activistas Digitales';
            $mail->Body = $this->getPasswordResetEmailHTML($name, $resetLink);
            $mail->AltBody = $this->getPasswordResetEmailText($name, $resetLink);
            
            $mail->send();
            logActivity("Correo de recuperación enviado a: $to (PHPMailer)", 'INFO');
            return true;
            
        } catch (Exception $e) {
            $errorMsg = isset($mail) && property_exists($mail, 'ErrorInfo') ? $mail->ErrorInfo : $e->getMessage();
            logActivity("Error al enviar correo de recuperación: $errorMsg", 'ERROR');
            // Intentar con mail() como respaldo
            return $this->sendEmailWithMailFunction($to, $name, $resetLink);
        }
    }
    
    /**
     * Enviar correo usando función mail() de PHP (alternativa)
     */
    private function sendEmailWithMailFunction($to, $name, $resetLink) {
        try {
            $subject = 'Recuperación de Contraseña - Activistas Digitales';
            
            // Headers optimizados para evitar spam
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Activistas Digitales <resetpassword@ejercitodigital.com.mx>\r\n";
            $headers .= "Reply-To: resetpassword@ejercitodigital.com.mx\r\n";
            $headers .= "Return-Path: resetpassword@ejercitodigital.com.mx\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 3\r\n";
            $headers .= "Importance: Normal\r\n";
            $headers .= "Message-ID: <" . time() . "." . md5($to . $resetLink) . "@ejercitodigital.com.mx>\r\n";
            
            $message = $this->getPasswordResetEmailHTML($name, $resetLink);
            
            $result = mail($to, $subject, $message, $headers);
            
            if ($result) {
                logActivity("Correo de recuperación enviado a: $to", 'INFO');
            } else {
                logActivity("Error al enviar correo de recuperación a: $to", 'ERROR');
            }
            
            return $result;
            
        } catch (Exception $e) {
            logActivity("Error al enviar correo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Obtener HTML del correo de recuperación
     */
    private function getPasswordResetEmailHTML($name, $resetLink) {
        return "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Recuperación de Contraseña</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background-color: white; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                    .content { padding: 30px; }
                    .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; background-color: #f9f9f9; }
                    .link-box { background: #f0f0f0; padding: 10px; border-radius: 5px; word-break: break-all; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1 style='margin: 0;'>Recuperación de Contraseña</h1>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>" . htmlspecialchars($name) . "</strong>,</p>
                        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Activistas Digitales</strong>.</p>
                        <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                        <div style='text-align: center;'>
                            <a href='" . htmlspecialchars($resetLink) . "' class='button' style='color: white;'>Restablecer Contraseña</a>
                        </div>
                        <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                        <div class='link-box'>
                            <a href='" . htmlspecialchars($resetLink) . "' style='color: #667eea; word-break: break-all;'>" . htmlspecialchars($resetLink) . "</a>
                        </div>
                        <p><strong>⏰ Este enlace expirará en 2 horas por seguridad.</strong></p>
                        <p style='color: #666; font-size: 14px;'>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo de forma segura. Tu contraseña no será cambiada.</p>
                    </div>
                    <div class='footer'>
                        <p style='margin: 5px 0;'><strong>Activistas Digitales</strong></p>
                        <p style='margin: 5px 0;'>© " . date('Y') . " Todos los derechos reservados.</p>
                        <p style='margin: 5px 0; font-size: 11px;'>Este es un correo automático, por favor no responder.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Obtener texto plano del correo de recuperación
     */
    private function getPasswordResetEmailText($name, $resetLink) {
        return "Hola $name,\n\nRecibimos una solicitud para restablecer la contraseña de tu cuenta en Activistas Digitales.\n\nVisita este enlace para crear una nueva contraseña:\n$resetLink\n\nEste enlace expirará en 2 horas.\n\nSi no solicitaste restablecer tu contraseña, puedes ignorar este correo de forma segura.\n\n© " . date('Y') . " Activistas Digitales. Todos los derechos reservados.";
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