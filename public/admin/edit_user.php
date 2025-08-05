<?php
/**
 * Editar Usuario - Endpoint público
 * Wrapper que llama al UserController::editUser()
 */

// Incluir el controlador de usuarios
require_once __DIR__ . '/../../controllers/userController.php';

try {
    // Crear instancia del controlador
    $userController = new UserController();
    
    // Verificar si se está procesando el cambio de estado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
        $userController->changeUserStatus();
    } else {
        // Llamar al método de edición de usuario
        $userController->editUser();
    }
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en edición de usuario: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar la edición de usuario: " . $e->getMessage();
    redirectWithMessage('admin/users.php', $message, 'error');
}
?>