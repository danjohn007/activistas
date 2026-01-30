<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';

// Logging para debugging
error_log("=== get-task-info.php called ===");
error_log("GET params: " . json_encode($_GET));

$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();

// Verificar permisos
if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
    error_log("get-task-info.php - Permiso denegado para usuario: " . $currentUser['rol']);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
    exit;
}

$titulo = $_GET['titulo'] ?? '';
$tipoActividadId = $_GET['tipo_actividad_id'] ?? '';

error_log("get-task-info.php - Buscando: titulo='$titulo', tipo_actividad_id='$tipoActividadId'");

if (empty($titulo) || empty($tipoActividadId)) {
    error_log("get-task-info.php - Parámetros faltantes");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

try {
    $activityModel = new Activity();
    $actividad = $activityModel->getTaskInfoByTitleAndType($titulo, $tipoActividadId);
    
    error_log("get-task-info.php - Resultado: " . json_encode($actividad));
    
    if ($actividad) {
        echo json_encode([
            'success' => true,
            'actividad' => $actividad
        ]);
    } else {
        error_log("get-task-info.php - Actividad no encontrada");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
    }
} catch (Exception $e) {
    error_log("get-task-info.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

