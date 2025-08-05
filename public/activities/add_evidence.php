<?php
/**
 * Agregar Evidencia - Endpoint público
 * Wrapper que llama al ActivityController::addEvidence()
 */

// Incluir el controlador de actividades
require_once __DIR__ . '/../../controllers/activityController.php';

try {
    // Crear instancia del controlador
    $activityController = new ActivityController();
    
    // Llamar al método de agregar evidencia
    $activityController->addEvidence();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error al agregar evidencia: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al agregar evidencia: " . $e->getMessage();
    redirectWithMessage('activities/', $message, 'error');
}
?>