<?php
/**
 * DEBUG: Test getBestPerformersByGroup method
 * ELIMINAR DESPUÉS DE USAR
 */

require_once __DIR__ . '/../models/group.php';

try {
    echo "<h2>Debug: Método getBestPerformersByGroup()</h2>";
    echo "<pre>";
    
    // 1. Crear instancia del modelo
    echo "1. Creando instancia de Group...\n";
    $groupModel = new Group();
    echo "   ✅ Instancia creada\n\n";
    
    // 2. Preparar filtros (mismo que usa la página)
    echo "2. Preparando filtros...\n";
    $filters = [
        'fecha_desde' => date('Y-m-01'), // Primer día del mes actual
        'fecha_hasta' => date('Y-m-t')   // Último día del mes actual
    ];
    echo "   Fecha desde: {$filters['fecha_desde']}\n";
    echo "   Fecha hasta: {$filters['fecha_hasta']}\n\n";
    
    // 3. Llamar al método
    echo "3. Llamando a getBestPerformersByGroup()...\n";
    $reportData = $groupModel->getBestPerformersByGroup($filters, 1, 20);
    echo "   ✅ Método ejecutado\n\n";
    
    // 4. Mostrar resultado
    echo "4. Resultado:\n";
    echo "   Total grupos: " . $reportData['total_groups'] . "\n";
    echo "   Página actual: " . $reportData['current_page'] . "\n";
    echo "   Total páginas: " . $reportData['total_pages'] . "\n";
    echo "   Grupos en array: " . count($reportData['groups']) . "\n\n";
    
    // 5. Si hay grupos, mostrar el primero
    if (!empty($reportData['groups'])) {
        echo "5. Primer grupo (ejemplo):\n";
        $firstGroup = $reportData['groups'][0];
        echo "   ID: {$firstGroup['id']}\n";
        echo "   Nombre: {$firstGroup['nombre']}\n";
        echo "   Descripción: " . ($firstGroup['descripcion'] ?? 'Sin descripción') . "\n";
        echo "   Miembros en best_performers: " . (count($firstGroup['best_performers'] ?? [])) . "\n";
        echo "   Tiene líder: " . (!empty($firstGroup['leader']) ? 'SÍ' : 'NO') . "\n";
        echo "   Promedio cumplimiento: {$firstGroup['avg_compliance']}%\n\n";
        
        // 6. Si hay performers, mostrar el primero
        if (!empty($firstGroup['best_performers'])) {
            echo "6. Primer performer del grupo:\n";
            $firstPerformer = $firstGroup['best_performers'][0];
            echo "   Nombre: {$firstPerformer['nombre_completo']}\n";
            echo "   Email: {$firstPerformer['email']}\n";
            echo "   Completadas: {$firstPerformer['tareas_completadas']}\n";
            echo "   Asignadas: {$firstPerformer['tareas_asignadas']}\n";
            echo "   Cumplimiento: {$firstPerformer['porcentaje_cumplimiento']}%\n";
        } else {
            echo "6. ⚠️ El grupo no tiene performers\n";
        }
    } else {
        echo "5. ❌ No se encontraron grupos en el resultado\n";
        echo "\n   Dump completo del resultado:\n";
        print_r($reportData);
    }
    
    echo "\n✅ Debug completado\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre>";
    echo "❌ Error:\n";
    echo $e->getMessage();
    echo "\n\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
