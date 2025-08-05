<?php
/**
 * App Configuration
 * Configuración general de la aplicación
 */

// Configuración del entorno
define('APP_ENV', 'production'); // development, testing, production

// Configuración de rutas base
define('BASE_PATH', '/ad'); // Base path para la aplicación (sin trailing slash)
define('BASE_URL', 'https://fix360.app/ad'); // URL base completa (sin trailing slash)

// Configuración de directorios
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', APP_ROOT . '/public');
define('VIEWS_ROOT', APP_ROOT . '/views');
define('UPLOADS_DIR', PUBLIC_ROOT . '/assets/uploads');

/**
 * Generar URLs absolutas con base path configurado
 * 
 * IMPORTANTE: Esta función debe usarse para todos los redirects y links
 * para garantizar compatibilidad con instalaciones en subdirectorios
 * 
 * @param string $path Ruta relativa (ej: 'login.php', 'admin/users.php')
 * @return string URL absoluta con base path (ej: 'https://fix360.app/ad/login.php')
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . ($path ? '/' . $path : '');
}

/**
 * Generar rutas del sistema con base path (sin dominio)
 * 
 * @param string $path Ruta relativa 
 * @return string Ruta con base path (ej: '/ad/login.php')
 */
function route($path = '') {
    $path = ltrim($path, '/');
    return BASE_PATH . ($path ? '/' . $path : '');
}

// Función para obtener la ruta actual sin el base path
function getCurrentPath() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // Remover el base path si existe
    if (BASE_PATH && strpos($path, BASE_PATH) === 0) {
        $path = substr($path, strlen(BASE_PATH));
    }
    
    return $path ?: '/';
}

// Configuración de errores basada en el entorno
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>