<?php
/**
 * Editar Actividad - Endpoint público
 * Wrapper que llama al ActivityController::showEditForm() o updateActivity()
 */

// Incluir el controlador de actividades
require_once __DIR__ . '/../../controllers/activityController.php';

try {
    // Crear instancia del controlador
    $activityController = new ActivityController();
    
    // Verificar si es un POST (actualizar) o GET (mostrar formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Actualizar actividad
        $activityController->updateActivity();
    } else {
        // Mostrar formulario de edición
        $activityController->showEditForm();
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en editar actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al editar actividad: " . $e->getMessage();
    redirectWithMessage('activities/', $message, 'error');
}
?>