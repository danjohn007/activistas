<?php
/**
 * Gestión de Usuarios - Endpoint público
 * Wrapper que llama al UserController::listUsers()
 */

// Incluir el controlador de usuarios
require_once __DIR__ . '/../../controllers/userController.php';

try {
    // Crear instancia del controlador
    $userController = new UserController();
    
    // Llamar al método de listado de usuarios
    $userController->listUsers();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en gestión de usuarios: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar la gestión de usuarios: " . $e->getMessage();
    redirectWithMessage('dashboards/admin.php', $message, 'error');
}
?>