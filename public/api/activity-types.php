<?php
/**
 * API endpoint para gestión de tipos de actividades
 * Solo accesible para SuperAdmin
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir dependencias
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../models/activityType.php';
    require_once __DIR__ . '/../../includes/functions.php';
    
    // Verificar autenticación y permisos
    $auth = getAuth();
    $auth->requireRole(['SuperAdmin']);
    
    $currentUser = $auth->getCurrentUser();
    if (!$currentUser) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Inicializar modelo
    $activityTypeModel = new ActivityType();
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;
    $typeId = $input['type_id'] ?? $_GET['type_id'] ?? null;
    
    if (!$action) {
        throw new Exception('Acción requerida');
    }
    
    if (!$typeId && in_array($action, ['delete', 'suspend', 'activate'])) {
        throw new Exception('ID de tipo de actividad requerido');
    }
    
    $response = ['success' => false];
    
    switch ($action) {
        case 'delete':
            try {
                $result = $activityTypeModel->deleteActivityType($typeId);
                if ($result) {
                    logActivity("Tipo de actividad ID $typeId eliminado por SuperAdmin " . $currentUser['nombre_completo']);
                    $response = [
                        'success' => true,
                        'message' => 'Tipo de actividad eliminado exitosamente'
                    ];
                } else {
                    throw new Exception('Error al eliminar tipo de actividad');
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
            break;
            
        case 'suspend':
            $result = $activityTypeModel->deactivateActivityType($typeId);
            if ($result) {
                logActivity("Tipo de actividad ID $typeId suspendido por SuperAdmin " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Tipo de actividad suspendido exitosamente'
                ];
            } else {
                throw new Exception('Error al suspender tipo de actividad');
            }
            break;
            
        case 'activate':
            $result = $activityTypeModel->activateActivityType($typeId);
            if ($result) {
                logActivity("Tipo de actividad ID $typeId activado por SuperAdmin " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Tipo de actividad activado exitosamente'
                ];
            } else {
                throw new Exception('Error al activar tipo de actividad');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>