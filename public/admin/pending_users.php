<?php
/**
 * Usuarios Pendientes - Endpoint público
 * Wrapper que llama al UserController::pendingUsers()
 */

// Incluir el controlador de usuarios
require_once __DIR__ . '/../../controllers/userController.php';

try {
    // Crear instancia del controlador
    $userController = new UserController();
    
    // Verificar si se está procesando una acción
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userController->approveUser();
    } else {
        $userController->pendingUsers();
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en usuarios pendientes: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar usuarios pendientes: " . $e->getMessage();
    redirectWithMessage('dashboards/admin.php', $message, 'error');
}
?>