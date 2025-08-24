<?php
/**
 * Editar Tipo de Actividad - Endpoint público
 * Solo para usuarios SuperAdmin
 */

// Incluir el controlador de tipos de actividades
require_once __DIR__ . '/../../controllers/activityTypeController.php';

try {
    // Verificar que se proporcione el ID
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        redirectWithMessage('activity-types/', 'ID de tipo de actividad no válido', 'error');
    }
    
    // Crear instancia del controlador
    $activityTypeController = new ActivityTypeController();
    
    // Verificar si es un POST (actualizar) o GET (mostrar formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Actualizar tipo de actividad
        $activityTypeController->updateActivityType($id);
    } else {
        // Mostrar formulario de edición
        $activityTypeController->showEditForm($id);
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en editar tipo de actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al editar tipo de actividad: " . $e->getMessage();
    redirectWithMessage('activity-types/', $message, 'error');
}
?>