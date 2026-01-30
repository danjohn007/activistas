<?php
/**
 * DIAGNÓSTICO PROFUNDO - Verificar qué devuelve el controlador
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/activity.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>🔍 Diagnóstico Controlador</h1><pre>";

// Simular usuario activista ID 1396
$userId = 1396;

echo "=== 1. SIMULACIÓN DEL CONTROLADOR ===\n";
echo "Usuario ID: $userId\n\n";

// Crear instancia del modelo
$activityModel = new Activity();

// Filtros exactos que usa el controlador para activistas
$filters = [
    'usuario_id' => $userId,
    'exclude_expired' => true, // Esto es CRÍTICO para activistas
    'include_evidence_count' => true,
    'page' => 1,
    'per_page' => 15
];

echo "Filtros aplicados:\n";
print_r($filters);
echo "\n";

// Obtener actividades usando el método del modelo
$activities = $activityModel->getActivities($filters);

echo "=== 2. RESULTADO DEL MODELO ===\n";
echo "Total actividades devueltas: " . count($activities) . "\n\n";

if (count($activities) > 0) {
    echo "✅ EL MODELO SÍ DEVUELVE ACTIVIDADES\n\n";
    echo "Lista de actividades:\n";
    foreach ($activities as $act) {
        echo "-----------------------------------\n";
        echo "ID: {$act['id']}\n";
        echo "Título: {$act['titulo']}\n";
        echo "Estado: {$act['estado']}\n";
        echo "Fecha actividad: {$act['fecha_actividad']}\n";
        echo "Fecha publicación: {$act['fecha_publicacion']}\n";
        echo "Hora publicación: {$act['hora_publicacion']}\n";
        echo "Fecha cierre: {$act['fecha_cierre']}\n";
        echo "Hora cierre: {$act['hora_cierre']}\n";
        echo "Autorizada: " . ($act['autorizada'] ? 'SÍ' : 'NO') . "\n";
        echo "\n";
    }
} else {
    echo "❌ EL MODELO NO DEVUELVE ACTIVIDADES\n";
    echo "Esto significa que los filtros están bloqueando las actividades.\n\n";
    
    // Probar sin filtro exclude_expired
    echo "=== 3. PRUEBA SIN FILTRO exclude_expired ===\n";
    $filters2 = [
        'usuario_id' => $userId,
        'include_evidence_count' => true,
        'page' => 1,
        'per_page' => 15
    ];
    $activities2 = $activityModel->getActivities($filters2);
    echo "Total sin exclude_expired: " . count($activities2) . "\n\n";
    
    if (count($activities2) > 0) {
        echo "⚠️ EL PROBLEMA ES EL FILTRO exclude_expired\n";
        echo "Las actividades están siendo filtradas por fecha de cierre o publicación.\n\n";
        
        foreach ($activities2 as $act) {
            echo "ID: {$act['id']} - {$act['titulo']}\n";
            echo "  Publicación: {$act['fecha_publicacion']} {$act['hora_publicacion']}\n";
            echo "  Cierre: {$act['fecha_cierre']} {$act['hora_cierre']}\n";
            echo "  NOW: " . date('Y-m-d H:i:s') . "\n\n";
        }
    }
}

// Verificar la query SQL exacta
echo "=== 4. VERIFICACIÓN HORA SERVIDOR ===\n";
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT NOW() as now, CURDATE() as today, CURTIME() as time");
$result = $stmt->fetch();
echo "NOW(): {$result['now']}\n";
echo "CURDATE(): {$result['today']}\n";
echo "CURTIME(): {$result['time']}\n\n";

// Verificar actividad específica 144431
echo "=== 5. VERIFICACIÓN ACTIVIDAD 144431 ===\n";
$stmt = $db->prepare("
    SELECT 
        id,
        titulo,
        fecha_publicacion,
        hora_publicacion,
        fecha_cierre,
        hora_cierre,
        CONCAT(DATE(fecha_publicacion), ' ', COALESCE(hora_publicacion, '00:00:00')) as pub_datetime,
        NOW() as ahora,
        CONCAT(DATE(fecha_publicacion), ' ', COALESCE(hora_publicacion, '00:00:00')) <= NOW() as deberia_publicarse,
        (fecha_cierre IS NULL OR fecha_cierre > CURDATE() 
            OR (fecha_cierre = CURDATE() AND (hora_cierre IS NULL OR hora_cierre > CURTIME()))) as no_vencida
    FROM actividades 
    WHERE id = 144431
");
$stmt->execute();
$act = $stmt->fetch();

if ($act) {
    echo "Título: {$act['titulo']}\n";
    echo "Fecha publicación: {$act['fecha_publicacion']}\n";
    echo "Hora publicación: {$act['hora_publicacion']}\n";
    echo "DATETIME publicación: {$act['pub_datetime']}\n";
    echo "NOW(): {$act['ahora']}\n";
    echo "¿Debería publicarse? " . ($act['deberia_publicarse'] ? 'SÍ ✅' : 'NO ❌') . "\n";
    echo "¿No vencida? " . ($act['no_vencida'] ? 'SÍ ✅' : 'NO ❌') . "\n\n";
    
    if (!$act['deberia_publicarse']) {
        echo "❌ PROBLEMA: La actividad AÚN no se ha publicado\n";
        echo "Hora de publicación programada: {$act['pub_datetime']}\n";
        echo "Hora actual: {$act['ahora']}\n";
    } elseif (!$act['no_vencida']) {
        echo "❌ PROBLEMA: La actividad está VENCIDA\n";
        echo "Fecha/hora cierre: {$act['fecha_cierre']} {$act['hora_cierre']}\n";
    } else {
        echo "✅ La actividad DEBERÍA mostrarse\n";
    }
}

echo "\n=== CONCLUSIÓN ===\n";
echo "Si el modelo devuelve actividades pero no aparecen en la interfaz:\n";
echo "1. Revisa la vista (views/activities/list.php o index.php)\n";
echo "2. Revisa JavaScript que filtre actividades\n";
echo "3. Revisa permisos de sesión\n";
echo "4. Inspecciona el HTML generado (Ver código fuente)\n";
echo "</pre>";
?>
