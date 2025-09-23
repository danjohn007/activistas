<?php
/**
 * Export monthly activities report to Excel
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';

// Verificar autenticación y permisos
$auth = getAuth();
$auth->requireRole(['SuperAdmin', 'Gestor', 'Líder']);

try {
    $activityModel = new Activity();
    $currentUser = $auth->getCurrentUser();
    
    // Determinar el mes a exportar (por defecto el actual)
    $month = cleanInput($_GET['month'] ?? date('Y-m'));
    
    // Validar formato del mes
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }
    
    // Configurar filtros según el rol
    $filters = [];
    switch ($currentUser['rol']) {
        case 'Líder':
            $filters['lider_id'] = $currentUser['id'];
            break;
        case 'SuperAdmin':
        case 'Gestor':
            // Pueden ver todas las actividades
            break;
    }
    
    // Aplicar filtro de fecha para el mes seleccionado
    $filters['fecha_desde'] = $month . '-01';
    $filters['fecha_hasta'] = date('Y-m-t', strtotime($month . '-01')); // Last day of the month
    
    // Aplicar otros filtros de la URL
    if (!empty($_GET['tipo'])) {
        $filters['tipo_actividad_id'] = intval($_GET['tipo']);
    }
    if (!empty($_GET['estado'])) {
        $filters['estado'] = cleanInput($_GET['estado']);
    }
    
    // Obtener todas las actividades del mes (sin paginación)
    $activities = $activityModel->getActivities($filters);
    
    // Configurar headers para descarga CSV
    $filename = 'actividades_' . $month . '_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Agregar BOM para UTF-8 (Excel compatibility)
    echo "\xEF\xBB\xBF";
    
    // Abrir el output
    $output = fopen('php://output', 'w');
    
    // Escribir headers
    $headers = [
        'ID',
        'Título',
        'Descripción',
        'Tipo de Actividad',
        'Usuario',
        'Email Usuario',
        'Teléfono Usuario',
        'Fecha Actividad',
        'Estado',
        'Tarea Pendiente',
        'Autorizada',
        'Solicitante',
        'Fecha Creación',
        'Enlace 1',
        'Enlace 2',
        'Grupo'
    ];
    
    fputcsv($output, $headers);
    
    // Escribir datos
    foreach ($activities as $activity) {
        $row = [
            $activity['id'],
            $activity['titulo'],
            $activity['descripcion'] ?? '',
            $activity['tipo_nombre'],
            $activity['usuario_nombre'],
            $activity['usuario_correo'] ?? '',
            $activity['usuario_telefono'] ?? '',
            formatDate($activity['fecha_actividad'], 'd/m/Y'),
            ucfirst(str_replace('_', ' ', $activity['estado'])),
            $activity['tarea_pendiente'] ? 'Sí' : 'No',
            $activity['autorizada'] ? 'Sí' : 'No',
            $activity['solicitante_nombre'] ?? 'N/A',
            formatDate($activity['fecha_creacion'], 'd/m/Y H:i'),
            $activity['enlace_1'] ?? '',
            $activity['enlace_2'] ?? '',
            $activity['grupo'] ?? ''
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    logActivity("Error al exportar actividades: " . $e->getMessage(), 'ERROR');
    redirectWithMessage('activities/', 'Error al generar el reporte de exportación', 'error');
}
?>