<?php
/**
 * Detalle de Actividad - Endpoint público
 * Wrapper que llama al ActivityController::showActivity()
 */

// Incluir el controlador de actividades
require_once __DIR__ . '/../../controllers/activityController.php';

try {
    // Crear instancia del controlador
    $activityController = new ActivityController();
    
    // Llamar al método de detalle de actividad
    $activityController->showActivity();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en detalle de actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar el detalle de la actividad: " . $e->getMessage();
    redirectWithMessage('activities/', $message, 'error');
}
?>