<?php
/**
 * Lista de Tareas Pendientes - Endpoint público
 * Solo para activistas que pueden ver sus tareas asignadas
 */

// Incluir el controlador de tareas
require_once __DIR__ . '/../../controllers/taskController.php';

try {
    // Crear instancia del controlador
    $taskController = new TaskController();
    
    // Llamar al método de listado de tareas pendientes
    $taskController->listPendingTasks();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en lista de tareas: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar las tareas: " . $e->getMessage();
    redirectWithMessage('dashboards/activista.php', $message, 'error');
}
?>