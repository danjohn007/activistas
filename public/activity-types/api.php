<?php
/**
 * API para obtener descripción de tipo de actividad
 * Usado para auto-completar descripción en formulario de actividades
 */

// Incluir el controlador de tipos de actividades
require_once __DIR__ . '/../../controllers/activityTypeController.php';

try {
    // Verificar que sea una petición AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Solo peticiones AJAX permitidas']);
        exit;
    }
    
    // Obtener ID del tipo de actividad
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'ID de tipo de actividad requerido']);
        exit;
    }
    
    // Crear instancia del controlador
    $activityTypeController = new ActivityTypeController();
    
    // Obtener descripción
    $activityTypeController->getActivityTypeDescription($id);
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en API de tipo de actividad: " . $e->getMessage(), 'ERROR');
    }
    
    // Respuesta de error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>