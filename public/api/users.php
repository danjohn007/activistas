<?php
/**
 * API endpoint para gestión de usuarios
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir dependencias
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../models/user.php';
    require_once __DIR__ . '/../../includes/functions.php';
    
    // Verificar autenticación
    $auth = getAuth();
    $auth->requireRole(['SuperAdmin', 'Gestor']);
    
    $currentUser = $auth->getCurrentUser();
    if (!$currentUser) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Inicializar modelo
    $userModel = new User();
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;
    $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
    
    if (!$action || !$userId) {
        throw new Exception('Acción y ID de usuario son requeridos');
    }
    
    $response = ['success' => false];
    
    switch ($action) {
        case 'approve':
            $vigenciaHasta = $input['vigencia_hasta'] ?? $_POST['vigencia_hasta'] ?? null;
            $vigenciaHasta = !empty($vigenciaHasta) ? $vigenciaHasta : null;
            
            $result = $userModel->approveUserWithVigencia($userId, $vigenciaHasta);
            if ($result) {
                $vigenciaText = $vigenciaHasta ? " con vigencia hasta $vigenciaHasta" : "";
                logActivity("Usuario ID $userId aprobado$vigenciaText por " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Usuario aprobado exitosamente'
                ];
            } else {
                throw new Exception('Error al aprobar usuario');
            }
            break;
            
        case 'reject':
            $result = $userModel->updateUserStatus($userId, 'desactivado');
            if ($result) {
                logActivity("Usuario ID $userId rechazado por " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Usuario rechazado exitosamente'
                ];
            } else {
                throw new Exception('Error al rechazar usuario');
            }
            break;
            
        case 'suspend':
            $result = $userModel->updateUserStatus($userId, 'suspendido');
            if ($result) {
                logActivity("Usuario ID $userId suspendido por " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Usuario suspendido exitosamente'
                ];
            } else {
                throw new Exception('Error al suspender usuario');
            }
            break;
            
        case 'delete':
            // Only SuperAdmin can delete users
            if ($currentUser['rol'] !== 'SuperAdmin') {
                throw new Exception('No tienes permisos para eliminar usuarios');
            }
            
            // Check if user has activities or is a leader with activists
            $userInfo = $userModel->getUserById($userId);
            if (!$userInfo) {
                throw new Exception('Usuario no encontrado');
            }
            
            // For safety, we'll use soft delete by setting status to 'eliminado'
            $result = $userModel->updateUserStatus($userId, 'eliminado');
            if ($result) {
                logActivity("Usuario ID $userId eliminado (soft delete) por SuperAdmin " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Usuario eliminado exitosamente'
                ];
            } else {
                throw new Exception('Error al eliminar usuario');
            }
            break;
            
        case 'change_password':
            // Only SuperAdmin can change passwords for other users
            if ($currentUser['rol'] !== 'SuperAdmin') {
                throw new Exception('No tienes permisos para cambiar contraseñas');
            }
            
            $newPassword = $input['new_password'] ?? $_POST['new_password'] ?? null;
            if (empty($newPassword) || strlen($newPassword) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            $result = $userModel->changePassword($userId, $newPassword);
            if ($result) {
                logActivity("Contraseña cambiada para usuario ID $userId por SuperAdmin " . $currentUser['nombre_completo']);
                $response = [
                    'success' => true,
                    'message' => 'Contraseña cambiada exitosamente'
                ];
            } else {
                throw new Exception('Error al cambiar la contraseña');
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