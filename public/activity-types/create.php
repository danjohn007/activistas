<?php
/**
 * Crear Tipo de Actividad - Endpoint público
 * Solo para usuarios SuperAdmin
 */

// Incluir el controlador de tipos de actividades
require_once __DIR__ . '/../../controllers/activityTypeController.php';

try {
    // Crear instancia del controlador
    $activityTypeController = new ActivityTypeController();
    
    // Verificar si es un POST (crear) o GET (mostrar formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear tipo de actividad
        $activityTypeController->createActivityType();
    } else {
        // Mostrar formulario de creación
        $activityTypeController->showCreateForm();
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en crear tipo de actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al crear tipo de actividad: " . $e->getMessage();
    redirectWithMessage('activity-types/', $message, 'error');
}
?>