<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();

// Verificar permisos
if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

$titulo = $data['titulo'] ?? '';
$tipoActividadId = $data['tipo_actividad_id'] ?? '';
$fechaActividad = $data['fecha_actividad'] ?? '';
$usuariosIds = $data['usuarios_ids'] ?? [];

if (empty($titulo) || empty($tipoActividadId) || empty($fechaActividad) || empty($usuariosIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Preparar placeholders para la consulta
    $placeholders = str_repeat('?,', count($usuariosIds) - 1) . '?';
    
    // Verificar qué usuarios ya tienen esta actividad
    $stmt = $db->prepare("
        SELECT 
            a.usuario_id,
            u.nombre_completo
        FROM actividades a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.titulo = ?
        AND a.tipo_actividad_id = ?
        AND a.fecha_actividad = ?
        AND a.usuario_id IN ($placeholders)
    ");
    
    $params = array_merge([$titulo, $tipoActividadId, $fechaActividad], $usuariosIds);
    $stmt->execute($params);
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo json_encode([
            'success' => false,
            'has_duplicates' => true,
            'duplicates' => $duplicates,
            'message' => 'Algunos usuarios ya tienen esta actividad'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_duplicates' => false,
            'message' => 'No hay duplicados'
        ]);
    }
} catch (Exception $e) {
    error_log("Error verificando duplicados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
