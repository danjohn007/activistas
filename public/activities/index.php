<?php
/**
 * Lista de Actividades - Endpoint público
 * Wrapper que llama al ActivityController::listActivities()
 */

// Incluir el controlador de actividades
require_once __DIR__ . '/../../controllers/activityController.php';

try {
    // Crear instancia del controlador
    $activityController = new ActivityController();
    
    // Llamar al método de listado de actividades
    $activityController->listActivities();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en lista de actividades: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar las actividades: " . $e->getMessage();
    redirectWithMessage('dashboards/activista.php', $message, 'error');
}
?>