<?php
/**
 * Toggle Status de Tipo de Actividad - Endpoint público
 * Solo para usuarios SuperAdmin
 */

// Incluir el controlador de tipos de actividades
require_once __DIR__ . '/../../controllers/activityTypeController.php';

try {
    // Solo permitir POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirectWithMessage('activity-types/', 'Método no permitido', 'error');
    }
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage('activity-types/', 'Token de seguridad inválido', 'error');
    }
    
    // Obtener datos
    $id = intval($_POST['id'] ?? 0);
    $action = cleanInput($_POST['action'] ?? '');
    
    if (!$id || !in_array($action, ['activar', 'desactivar'])) {
        redirectWithMessage('activity-types/', 'Datos inválidos', 'error');
    }
    
    // Crear instancia del controlador
    $activityTypeController = new ActivityTypeController();
    
    // Requerir permisos de SuperAdmin
    $activityTypeController->auth->requireRole(['SuperAdmin']);
    
    // Llamar al modelo directamente para el toggle
    require_once __DIR__ . '/../../models/activityType.php';
    $model = new ActivityType();
    
    if ($action === 'activar') {
        $result = $model->activateActivityType($id);
        $message = $result ? 'Tipo de actividad activado exitosamente' : 'Error al activar el tipo de actividad';
    } else {
        $result = $model->deactivateActivityType($id);
        $message = $result ? 'Tipo de actividad desactivado exitosamente' : 'Error al desactivar el tipo de actividad';
    }
    
    $type = $result ? 'success' : 'error';
    redirectWithMessage('activity-types/', $message, $type);
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en toggle de tipo de actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cambiar estado del tipo de actividad: " . $e->getMessage();
    redirectWithMessage('activity-types/', $message, 'error');
}
?>