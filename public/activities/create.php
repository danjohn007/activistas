<?php
/**
 * Crear Nueva Actividad - Endpoint público
 * Wrapper que llama al ActivityController::showCreateForm() o createActivity()
 */

// Incluir el controlador de actividades
require_once __DIR__ . '/../../controllers/activityController.php';

try {
    // Crear instancia del controlador
    $activityController = new ActivityController();
    
    // Verificar si es un POST (crear) o GET (mostrar formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear actividad
        $activityController->createActivity();
    } else {
        // Mostrar formulario de creación
        $activityController->showCreateForm();
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en crear actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al crear actividad: " . $e->getMessage();
    redirectWithMessage('activities/', $message, 'error');
}
?>