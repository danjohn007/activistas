<?php
/**
 * API endpoint to export monthly activities report as Excel
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';
require_once __DIR__ . '/../../models/user.php';

// Check authentication
$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();

// Only allow admin and leader roles
if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
    http_response_code(403);
    die('Acceso denegado');
}

// Verify POST request and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método no permitido');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Token de seguridad inválido');
}

$month = cleanInput($_POST['month'] ?? '');
if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    die('Mes inválido');
}

list($year, $monthNum) = explode('-', $month);

try {
    $activityModel = new Activity();
    $userModel = new User();
    
    // Prepare filters for the month
    $filters = [
        'fecha_desde' => $month . '-01',
        'fecha_hasta' => $month . '-31'
    ];
    
    // Apply role-based filters
    switch ($currentUser['rol']) {
        case 'Activista':
            $filters['usuario_id'] = $currentUser['id'];
            break;
        case 'Líder':
            $filters['lider_id'] = $currentUser['id'];
            break;
        // SuperAdmin and Gestor can see all activities
    }
    
    // Get activities for the month (without pagination)
    $activities = $activityModel->getActivities($filters);
    
    // Set headers for Excel download
    $filename = "reporte_actividades_" . $month . "_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file handle
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 support in Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV headers
    $headers = [
        'ID',
        'Título',
        'Usuario',
        'Correo',
        'Teléfono',
        'Tipo de Actividad',
        'Fecha',
        'Estado',
        'Lugar',
        'Alcance Estimado',
        'Descripción',
        'Fecha de Creación'
    ];
    
    fputcsv($output, $headers);
    
    // Add activity data
    foreach ($activities as $activity) {
        $row = [
            $activity['id'],
            $activity['titulo'],
            $activity['usuario_nombre'],
            $activity['usuario_correo'],
            $activity['usuario_telefono'],
            $activity['tipo_nombre'],
            formatDate($activity['fecha_actividad'], 'd/m/Y'),
            ucfirst(str_replace('_', ' ', $activity['estado'])),
            $activity['lugar'] ?? '',
            $activity['alcance_estimado'] ?? 0,
            $activity['descripcion'] ?? '',
            formatDate($activity['fecha_creacion'], 'd/m/Y H:i')
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    logActivity("Error al exportar reporte mensual: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    die('Error interno del servidor');
}
?>