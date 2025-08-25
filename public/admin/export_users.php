<?php
/**
 * Export functionality for users with compliance data
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/user.php';

// Verificar autenticación y permisos
$auth = getAuth();
$auth->requireRole(['SuperAdmin', 'Gestor']);

try {
    $userModel = new User();
    
    // Obtener filtros
    $filters = [];
    if (!empty($_GET['rol'])) {
        $filters['rol'] = cleanInput($_GET['rol']);
    }
    if (!empty($_GET['estado'])) {
        $filters['estado'] = cleanInput($_GET['estado']);
    }
    if (!empty($_GET['cumplimiento'])) {
        $filters['cumplimiento'] = cleanInput($_GET['cumplimiento']);
    }
    
    // Obtener datos con compliance
    $users = $userModel->getAllUsersWithCompliance($filters);
    
    // Configurar headers para descarga CSV
    $filename = 'usuarios_compliance_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Crear output stream
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel muestre correctamente los acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers del CSV
    fputcsv($output, [
        'ID',
        'Nombre Completo',
        'Email',
        'Teléfono',
        'Rol',
        'Estado',
        'Líder',
        'Total Tareas',
        'Tareas Completadas',
        'Porcentaje Cumplimiento',
        'Clasificación Semáforo',
        'Puntos Ranking',
        'Fecha Registro'
    ], ';');
    
    // Datos
    foreach ($users as $user) {
        $porcentaje = $user['porcentaje_cumplimiento'] ?? 0;
        
        // Determinar clasificación semáforo
        if ($porcentaje == 0) {
            $semaforo = 'Sin tareas';
        } elseif ($porcentaje > 60) {
            $semaforo = 'Alto (Verde)';
        } elseif ($porcentaje >= 20) {
            $semaforo = 'Medio (Amarillo)';
        } else {
            $semaforo = 'Bajo (Rojo)';
        }
        
        fputcsv($output, [
            $user['id'],
            $user['nombre_completo'],
            $user['email'],
            $user['telefono'] ?? '',
            $user['rol'],
            $user['estado'],
            $user['lider_nombre'] ?? 'N/A',
            $user['total_tareas'] ?? 0,
            $user['tareas_completadas'] ?? 0,
            $porcentaje . '%',
            $semaforo,
            $user['ranking_puntos'] ?? 0,
            date('d/m/Y', strtotime($user['fecha_registro']))
        ], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // En caso de error, redirigir con mensaje
    redirectWithMessage('admin/users.php', 'Error al exportar datos: ' . $e->getMessage(), 'error');
}
?>