<?php
/**
 * Punto de entrada principal del sistema
 * Sistema de Activistas Digitales
 */

// Incluir configuración de la aplicación
require_once __DIR__ . '/../config/app.php';

// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Iniciar sesión
startSession();

// Obtener la ruta actual sin el base path
$path = getCurrentPath();

// Instanciar Auth
$auth = getAuth();

// Rutas del sistema
switch ($path) {
    case '/':
    case '/index.php':
        // Página principal - redireccionar según estado de autenticación
        if ($auth->isLoggedIn()) {
            redirectToDashboard();
        } else {
            require __DIR__ . '/login.php';
        }
        break;
        
    case '/login.php':
        require __DIR__ . '/login.php';
        break;
        
    case '/register.php':
        require __DIR__ . '/register.php';
        break;
        
    case '/logout.php':
        require __DIR__ . '/logout.php';
        break;
        
    case '/profile.php':
        require __DIR__ . '/profile.php';
        break;
        
    // Dashboards
    case '/dashboards/admin.php':
        require __DIR__ . '/dashboards/admin.php';
        break;
        
    case '/dashboards/gestor.php':
        require __DIR__ . '/dashboards/gestor.php';
        break;
        
    case '/dashboards/lider.php':
        require __DIR__ . '/dashboards/lider.php';
        break;
        
    case '/dashboards/activista.php':
        require __DIR__ . '/dashboards/activista.php';
        break;
        
    // Actividades
    case '/activities/':
    case '/activities/index.php':
        require __DIR__ . '/activities/index.php';
        break;
        
    case '/activities/create.php':
        require __DIR__ . '/activities/create.php';
        break;
        
    case '/activities/detail.php':
        require __DIR__ . '/activities/detail.php';
        break;
        
    case '/activities/edit.php':
        require __DIR__ . '/activities/edit.php';
        break;
        
    case '/activities/add_evidence.php':
        require __DIR__ . '/activities/add_evidence.php';
        break;
        
    // Administración
    case '/admin/users.php':
        require __DIR__ . '/admin/users.php';
        break;
        
    case '/admin/pending_users.php':
        require __DIR__ . '/admin/pending_users.php';
        break;
        
    case '/admin/edit_user.php':
        require __DIR__ . '/admin/edit_user.php';
        break;
        
    // API endpoints
    case '/api/calendar.php':
        require __DIR__ . '/api/calendar.php';
        break;
        
    case '/api/stats.php':
        require __DIR__ . '/api/stats.php';
        break;
        
    // Verificación de email
    case '/verify.php':
        require __DIR__ . '/verify.php';
        break;
        
    default:
        // Página no encontrada
        http_response_code(404);
        require __DIR__ . '/404.php';
        break;
}

/**
 * Función para redireccionar al dashboard según el rol del usuario
 * Utiliza rutas relativas compatibles con subdirectorios
 */
function redirectToDashboard() {
    $role = $_SESSION['user_role'] ?? '';
    
    switch ($role) {
        case 'SuperAdmin':
            header('Location: ' . url('dashboards/admin.php'));
            break;
        case 'Gestor':
            header('Location: ' . url('dashboards/gestor.php'));
            break;
        case 'Líder':
            header('Location: ' . url('dashboards/lider.php'));
            break;
        case 'Activista':
            header('Location: ' . url('dashboards/activista.php'));
            break;
        default:
            header('Location: ' . url('login.php'));
            break;
    }
    exit();
}
?>