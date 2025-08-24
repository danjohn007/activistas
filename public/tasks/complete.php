<?php
/**
 * Completar Tarea - Endpoint público
 * Solo para activistas que pueden completar tareas subiendo evidencias
 */

// Incluir el controlador de tareas
require_once __DIR__ . '/../../controllers/taskController.php';

try {
    $taskId = intval($_GET['id'] ?? 0);
    
    if ($taskId <= 0) {
        redirectWithMessage('tasks/', 'ID de tarea inválido', 'error');
    }
    
    // Crear instancia del controlador
    $taskController = new TaskController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Procesar completar tarea
        $taskController->completeTask($taskId);
    } else {
        // Mostrar formulario
        $taskController->showCompleteForm($taskId);
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error al completar tarea: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al procesar la tarea: " . $e->getMessage();
    redirectWithMessage('tasks/', $message, 'error');
}
?>