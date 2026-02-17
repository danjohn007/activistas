<?php
/**
 * Funciones auxiliares del sistema
 */

// Incluir configuración de la aplicación
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/optimization.php';

// Incluir helpers de evidencias (opcional para entornos donde no esté desplegado)
$evidenceHelpersPath = __DIR__ . '/evidence_helpers.php';
if (file_exists($evidenceHelpersPath)) {
    require_once $evidenceHelpersPath;
} else {
    if (defined('APP_ENV') && APP_ENV === 'development') {
        error_log('Activistas App - WARNING: Archivo no encontrado: ' . $evidenceHelpersPath);
    }
}

// Fallbacks para evitar errores fatales si los helpers no están disponibles
if (!function_exists('parseEvidenceLinks')) {
    function parseEvidenceLinks($input) {
        return is_string($input) ? trim($input) : '';
    }
}

if (!function_exists('normalizeEvidenceType')) {
    function normalizeEvidenceType($type) {
        return is_string($type) ? trim($type) : '';
    }
}

if (!function_exists('findEvidenceFile')) {
    function findEvidenceFile($dbFileName) {
        if (!is_string($dbFileName) || $dbFileName === '') {
            return null;
        }
        return basename($dbFileName);
    }
}

// Configurar zona horaria de México
if (defined('DEFAULT_TIMEZONE')) {
    date_default_timezone_set(DEFAULT_TIMEZONE);
} else {
    date_default_timezone_set('America/Mexico_City');
}

// Iniciar sesión si no está iniciada
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.lazy_write', '1');
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
    // Mínimo 6 caracteres
    return strlen($password) >= 6;
}

// Verificar formato de URL
function isValidUrl($url) {
    if (empty($url)) {
        return true; // URLs vacías son válidas (opcionales)
    }
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function getMunicipiosQueretaro() {
    return [
        'Amealco de Bonfil',
        'Arroyo Seco',
        'Cadereyta de Montes',
        'Colón',
        'Corregidora',
        'Ezequiel Montes',
        'Huimilpan',
        'Jalpan de Serra',
        'Landa de Matamoros',
        'El Marqués',
        'Pedro Escobedo',
        'Peñamiller',
        'Pinal de Amoles',
        'Querétaro',
        'San Joaquín',
        'San Juan del Río',
        'Tequisquiapan',
        'Tolimán'
    ];
}

function isValidMunicipio($municipio) {
    if (empty($municipio)) {
        return false;
    }
    return in_array(trim($municipio), getMunicipiosQueretaro(), true);
}

// Generar token aleatorio
function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Formatear fecha
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Redireccionar con mensaje flash
 * 
 * IMPORTANTE PARA DESARROLLADORES:
 * - Siempre usar rutas RELATIVAS como parámetro (ej: "login.php", "admin/users.php")  
 * - NO usar rutas absolutas como "/public/login.php" o rutas con BASE_PATH
 * - La función automáticamente aplicará el BASE_PATH configurado usando url()
 * - Esto garantiza compatibilidad con instalaciones en subdirectorios como /ad
 * 
 * Ejemplos CORRECTOS:
 *   redirectWithMessage('login.php', 'Sesión cerrada', 'success');
 *   redirectWithMessage('admin/users.php', 'Usuario actualizado', 'success');
 *   redirectWithMessage('dashboards/activista.php', 'Bienvenido', 'info');
 * 
 * Ejemplos INCORRECTOS:
 *   redirectWithMessage('/public/login.php', '...'); // NO - ruta absoluta
 *   redirectWithMessage('/ad/login.php', '...'); // NO - incluye base path
 * 
 * @param string $url Ruta relativa del destino (sin barra inicial)
 * @param string $message Mensaje flash a mostrar
 * @param string $type Tipo de mensaje: 'info', 'success', 'warning', 'error'
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    
    // Si la URL no empieza con http, agregar el base path usando url()
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = url($url);
    }
    
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
function uploadFile($file, $uploadDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $isProfile = false, $isVideo = false) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }
    
    // Verificar tamaño - 50MB para videos, 20MB para perfiles, 5MB para otros archivos
    $maxSize = 5242880; // 5MB por defecto
    if ($isVideo || in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])) {
        $maxSize = 52428800; // 50MB para videos
        $maxSizeText = '50MB';
    } elseif ($isProfile) {
        $maxSize = 20971520; // 20MB para perfiles
        $maxSizeText = '20MB';
    } else {
        $maxSizeText = '5MB';
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => "El archivo es demasiado grande. Máximo permitido: $maxSizeText"];
    }
    
    $fileName = uniqid() . '.' . $extension;
    $filePath = $uploadDir . '/' . $fileName;
    
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
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

// Logging mejorado con manejo de errores
function logActivity($message, $level = 'INFO') {
    try {
        $logFile = __DIR__ . '/../logs/system.log';
        $logDir = dirname($logFile);
        
        // Verificar si el directorio existe y es escribible
        if (!is_dir($logDir)) {
            // Intentar crear el directorio, silenciando errores con @
            if (!@mkdir($logDir, 0755, true)) {
                // Si falla la creación, usar error_log del sistema
                error_log("Activistas App - $level: $message");
                return;
            }
        }
        
        // Verificar que el directorio sea escribible
        if (!is_writable($logDir)) {
            error_log("Activistas App - $level: $message (logs dir not writable)");
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Intentar escribir en el archivo, silenciando errores con @
        if (!@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX)) {
            error_log("Activistas App - $level: $message");
        }
        
        // Para errores críticos, también registrar en error_log
        if ($level === 'ERROR') {
            error_log("Activistas App - $level: $message");
        }
    } catch (Exception $e) {
        // Si falla el logging, usar error_log como respaldo
        error_log("Logging failed - Original: $message | Error: " . $e->getMessage());
    }
}

// Función para registrar errores de dashboard específicamente
function logDashboardError($dashboard, $userId, $error) {
    $message = "Dashboard Error - Type: $dashboard, User: $userId, Error: $error";
    logActivity($message, 'ERROR');
    
    // También registrar en un archivo específico para dashboards
    try {
        $logFile = __DIR__ . '/../logs/dashboard_errors.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Verificar que el directorio sea escribible
        if (!is_writable($logDir)) {
            error_log("Dashboard Error - $message (logs dir not writable)");
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Dashboard logging failed: " . $e->getMessage());
    }
}

// Map user role to dashboard filename (without accent issues)
function getDashboardUrl($role) {
    $dashboardMap = [
        'SuperAdmin' => 'admin.php',
        'Gestor' => 'gestor.php', 
        'Líder' => 'lider.php',
        'Activista' => 'activista.php'
    ];
    
    $filename = $dashboardMap[$role] ?? 'activista.php';
    return url('dashboards/' . $filename);
}

// Get user notifications
function getUserNotifications($userId) {
    if (!isset($_SESSION['user_notifications'][$userId])) {
        return [];
    }
    
    return $_SESSION['user_notifications'][$userId];
}

// Clear user notifications
function clearUserNotifications($userId) {
    if (isset($_SESSION['user_notifications'][$userId])) {
        unset($_SESSION['user_notifications'][$userId]);
    }
}

// Display notifications HTML
function displayNotifications($userId) {
    $notifications = getUserNotifications($userId);
    
    if (empty($notifications)) {
        return '';
    }
    
    $html = '';
    foreach ($notifications as $notification) {
        $html .= '<div class="alert alert-' . $notification['type'] . ' alert-dismissible fade show" role="alert">';
        $html .= htmlspecialchars($notification['message']);
        if (!empty($notification['url'])) {
            $html .= ' <a href="' . $notification['url'] . '" class="alert-link">Ver actividad</a>';
        }
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $html .= '</div>';
    }
    
    // Clear notifications after displaying
    clearUserNotifications($userId);
    
    return $html;
}

/**
 * Get Bootstrap progress bar class based on percentage
 * 
 * @param float $percentage Completion percentage (0-100)
 * @return string Bootstrap class (bg-success, bg-warning, or bg-danger)
 */
function getProgressBarClass($percentage) {
    if ($percentage >= 75) {
        return 'bg-success';
    } elseif ($percentage >= 50) {
        return 'bg-warning';
    } else {
        return 'bg-danger';
    }
}
?>