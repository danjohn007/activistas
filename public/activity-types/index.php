<?php
/**
 * Lista de Tipos de Actividades - Endpoint público
 * Solo para usuarios SuperAdmin
 */

// Incluir el controlador de tipos de actividades
require_once __DIR__ . '/../../controllers/activityTypeController.php';

try {
    // Crear instancia del controlador
    $activityTypeController = new ActivityTypeController();
    
    // Mostrar lista de tipos de actividades
    $activityTypeController->listActivityTypes();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en lista de tipos de actividades: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar los tipos de actividades: " . $e->getMessage();
    redirectWithMessage('dashboards/admin.php', $message, 'error');
}
?>