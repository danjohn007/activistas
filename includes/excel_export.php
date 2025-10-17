<?php
/**
 * Excel Export Helper
 * Simple CSV-based Excel export functionality
 */

/**
 * Export best by group report to Excel (CSV format)
 * 
 * @param array $groups Groups with best performers
 * @param string $fechaDesde Start date
 * @param string $fechaHasta End date
 */
function exportBestByGroupToExcel($groups, $fechaDesde, $fechaHasta) {
    // Set headers for Excel download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mejores_por_grupo_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header
    fputcsv($output, ['INFORME DE MEJORES POR GRUPO']);
    fputcsv($output, ['Período: ' . date('d/m/Y', strtotime($fechaDesde)) . ' - ' . date('d/m/Y', strtotime($fechaHasta))]);
    fputcsv($output, ['Generado: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []); // Empty row
    
    // Process each group
    foreach ($groups as $group) {
        // Group header
        fputcsv($output, ['GRUPO: ' . $group['nombre']]);
        if (!empty($group['descripcion'])) {
            fputcsv($output, ['Descripción: ' . $group['descripcion']]);
        }
        fputcsv($output, ['Cumplimiento Promedio: ' . $group['avg_compliance'] . '%']);
        fputcsv($output, []); // Empty row
        
        // Leader section
        if (!empty($group['leader'])) {
            fputcsv($output, ['LÍDER DEL GRUPO']);
            fputcsv($output, [
                'Nombre',
                'Email',
                'Tareas Asignadas',
                'Tareas Completadas',
                'Porcentaje Cumplimiento',
                'Puntos Ranking'
            ]);
            fputcsv($output, [
                $group['leader']['nombre_completo'],
                $group['leader']['email'],
                $group['leader']['tareas_asignadas'],
                $group['leader']['tareas_completadas'],
                $group['leader']['porcentaje_cumplimiento'] . '%',
                $group['leader']['ranking_puntos']
            ]);
            fputcsv($output, []); // Empty row
        }
        
        // Best activists section
        if (!empty($group['best_performers'])) {
            fputcsv($output, ['TOP 5 ACTIVISTAS DEL GRUPO']);
            fputcsv($output, [
                'Posición',
                'Nombre',
                'Email',
                'Rol',
                'Tareas Asignadas',
                'Tareas Completadas',
                'Porcentaje Cumplimiento',
                'Puntos Ranking'
            ]);
            
            foreach ($group['best_performers'] as $index => $performer) {
                fputcsv($output, [
                    $index + 1,
                    $performer['nombre_completo'],
                    $performer['email'],
                    $performer['rol'],
                    $performer['tareas_asignadas'],
                    $performer['tareas_completadas'],
                    $performer['porcentaje_cumplimiento'] . '%',
                    $performer['ranking_puntos']
                ]);
            }
        } else {
            fputcsv($output, ['No hay activistas registrados en este grupo']);
        }
        
        fputcsv($output, []); // Empty row
        fputcsv($output, ['-------------------------------------------']);
        fputcsv($output, []); // Empty row
    }
    
    fclose($output);
    exit;
}

/**
 * Export users to Excel (CSV format)
 * Used for general user exports
 * 
 * @param array $users Users to export
 */
function exportUsersToExcel($users) {
    // Set headers for Excel download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="usuarios_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header
    fputcsv($output, [
        'ID',
        'Nombre',
        'Email',
        'Teléfono',
        'Rol',
        'Estado',
        'Líder',
        'Fecha Registro'
    ]);
    
    // Write data
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['nombre_completo'],
            $user['email'],
            $user['telefono'] ?? '',
            $user['rol'],
            $user['estado'],
            $user['lider_nombre'] ?? '',
            $user['fecha_registro']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
