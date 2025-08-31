<?php
/**
 * Endpoint para actualizar vigencia de usuarios (AJAX)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/user.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación
    $auth = getAuth();
    $auth->requireAuth();
    
    $currentUser = $auth->getCurrentUser();
    
    // Verificar permisos (solo SuperAdmin y Gestor pueden editar vigencia)
    if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'No tienes permisos para editar la vigencia de usuarios'
        ]);
        exit;
    }
    
    // Verificar que es una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
        exit;
    }
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos inválidos'
        ]);
        exit;
    }
    
    // Validar CSRF token
    if (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Token de seguridad inválido'
        ]);
        exit;
    }
    
    $userId = intval($input['user_id'] ?? 0);
    $vigenciaHasta = $input['vigencia_hasta'] ?? null;
    
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de usuario inválido'
        ]);
        exit;
    }
    
    // Validar fecha si se proporciona
    if ($vigenciaHasta && !empty($vigenciaHasta)) {
        $date = DateTime::createFromFormat('Y-m-d', $vigenciaHasta);
        if (!$date || $date->format('Y-m-d') !== $vigenciaHasta) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Formato de fecha inválido'
            ]);
            exit;
        }
        
        // Verificar que la fecha no sea anterior a hoy
        if ($date < new DateTime()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'La fecha de vigencia no puede ser anterior al día actual'
            ]);
            exit;
        }
    }
    
    // Actualizar vigencia
    $userModel = new User();
    
    // Verificar que el modelo de usuario tenga conexión válida a la base de datos
    if (!$userModel->hasValidConnection()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error de conexión a la base de datos. Por favor, inténtelo de nuevo más tarde.'
        ]);
        exit;
    }
    
    $result = $userModel->updateUserVigencia($userId, $vigenciaHasta);
    
    if ($result) {
        logActivity("Vigencia actualizada para usuario ID $userId: " . ($vigenciaHasta ?: 'Sin vigencia'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Vigencia actualizada correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar la vigencia en la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error updating vigencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>