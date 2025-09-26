<?php
/**
 * API endpoint to get team members for a specific leader
 * Used for auto-selecting team members when leader is selected
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/user.php';

// Verificar autenticación y permisos
$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tienes permisos para esta acción']);
    exit;
}

// Verificar que se proporcione el ID del líder
$liderId = isset($_GET['lider_id']) ? intval($_GET['lider_id']) : 0;

if ($liderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de líder inválido']);
    exit;
}

try {
    $userModel = new User();
    
    // Obtener activistas del líder especificado
    $teamMembers = $userModel->getActivistsOfLeader($liderId);
    
    // Formatear la respuesta
    $members = [];
    foreach ($teamMembers as $member) {
        $members[] = [
            'id' => $member['id'],
            'name' => $member['nombre_completo'],
            'email' => $member['email']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'members' => $members,
        'count' => count($members)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>