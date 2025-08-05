<?php
/**
 * Funciones auxiliares del sistema
 */

// Iniciar sesión si no está iniciada
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Limpiar y validar entrada de datos
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Verificar formato de email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Verificar fortaleza de contraseña
function isStrongPassword($password) {
    // Mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un carácter especial
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

// Generar token aleatorio
function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Formatear fecha
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Redireccionar con mensaje
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

// Mostrar mensaje flash
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Subir archivo
function uploadFile($file, $uploadDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }
    
    // Verificar tamaño (5MB máximo)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande'];
    }
    
    $fileName = uniqid() . '.' . $extension;
    $filePath = $uploadDir . '/' . $fileName;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $filePath];
    }
    
    return ['success' => false, 'error' => 'Error al guardar el archivo'];
}

// Verificar permisos de usuario
function hasPermission($userRole, $requiredRoles) {
    return in_array($userRole, $requiredRoles);
}

// Obtener jerarquía de roles
function getRoleHierarchy() {
    return [
        'SuperAdmin' => 4,
        'Gestor' => 3,
        'Líder' => 2,
        'Activista' => 1
    ];
}

// Verificar si un rol tiene mayor o igual nivel que otro
function hasRoleLevel($userRole, $minimumRole) {
    $hierarchy = getRoleHierarchy();
    return $hierarchy[$userRole] >= $hierarchy[$minimumRole];
}

// Enviar email (función básica)
function sendEmail($to, $subject, $message, $from = 'noreply@activistas.com') {
    $headers = [
        'From' => $from,
        'Reply-To' => $from,
        'X-Mailer' => 'PHP/' . phpversion(),
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $message, implode("\r\n", array_map(
        function($k, $v) { return "$k: $v"; },
        array_keys($headers),
        $headers
    )));
}

// Logging básico
function logActivity($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/system.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>