<?php
/**
 * DEBUG FILTROS ACTIVISTA
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/activity.php';

echo "<h1>🔍 Debug Filtros de Actividades</h1><pre>";

if (!isset($_SESSION['user_id'])) {
    die("❌ No estás logueado. Ve a la página principal primero.");
}

echo "=== 1. INFORMACIÓN DEL USUARIO ===\n";
echo "User ID: {$_SESSION['user_id']}\n";
echo "Nombre: {$_SESSION['user_name']}\n";
echo "Rol: {$_SESSION['user_role']}\n\n";

$db = Database::getInstance()->getConnection();
$activityModel = new Activity();

echo "=== 2. HORA ACTUAL DEL SERVIDOR ===\n";
$stmt = $db->query("SELECT NOW() as now, CURDATE() as today, CURTIME() as time");
$time = $stmt->fetch();
echo "NOW(): {$time['now']}\n";
echo "CURDATE(): {$time['today']}\n";
echo "CURTIME(): {$time['time']}\n\n";

echo "=== 3. TODAS LAS ACTIVIDADES DEL USUARIO (SIN FILTROS) ===\n";
$sql = "SELECT a.id, a.titulo, a.estado, a.fecha_actividad,
               a.fecha_cierre, a.hora_cierre,
               a.fecha_publicacion, a.hora_publicacion,
               CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) as publicacion_datetime,
               a.autorizada
        FROM actividades a
        WHERE a.usuario_id = ?
        ORDER BY a.fecha_actividad DESC
        LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$all = $stmt->fetchAll();

echo "Total sin filtros: " . count($all) . "\n";
foreach ($all as $act) {
    $pub_ok = empty($act['fecha_publicacion']) || $act['publicacion_datetime'] <= $time['now'] ? '✅' : '❌';
    $vencida = !empty($act['fecha_cierre']) && $act['fecha_cierre'] < $time['today'] ? '❌ VENCIDA' : '✅';
    $autorizada = $act['autorizada'] == 1 ? '✅' : '❌';
    
    echo "\n  ID {$act['id']}: {$act['titulo']}\n";
    echo "    Estado: {$act['estado']} | Autorizada: $autorizada\n";
    echo "    Publicación: {$act['publicacion_datetime']} $pub_ok\n";
    echo "    Cierre: {$act['fecha_cierre']} {$act['hora_cierre']} $vencida\n";
}

echo "\n\n=== 4. ACTIVIDADES CON FILTROS DE ACTIVISTA ===\n";
$filters = [
    'usuario_id' => $_SESSION['user_id'],
    'exclude_expired' => true,
    'include_evidence_count' => true,
    'page' => 1,
    'per_page' => 15
];

echo "Filtros aplicados:\n";
print_r($filters);

$activities = $activityModel->getActivities($filters);
echo "\nTotal CON filtros: " . count($activities) . "\n";

foreach ($activities as $act) {
    echo "\n  ID {$act['id']}: {$act['titulo']}\n";
    echo "    Estado: {$act['estado']}\n";
    echo "    Fecha: {$act['fecha_actividad']}\n";
    echo "    Evidencias: {$act['evidence_count']}\n";
}

echo "\n\n=== 5. QUERY CON FILTROS (DEBUG) ===\n";
$sql = "SELECT a.id, a.titulo, a.estado, 
               a.fecha_cierre, a.hora_cierre,
               CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) as pub_datetime,
               NOW() as now,
               (a.fecha_cierre IS NULL OR a.fecha_cierre > CURDATE() 
                OR (a.fecha_cierre = CURDATE() AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME()))) as no_vencida,
               (a.fecha_publicacion IS NULL 
                OR CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) <= NOW()) as publicada,
               (a.estado != 'completada') as no_completada
        FROM actividades a
        WHERE a.usuario_id = ?
        AND a.autorizada = 1
        ORDER BY a.fecha_actividad DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$debug = $stmt->fetchAll();

echo "Análisis de cada actividad:\n";
foreach ($debug as $act) {
    $cumple = $act['no_vencida'] && $act['publicada'] && $act['no_completada'];
    $icono = $cumple ? '✅' : '❌';
    
    echo "\n$icono ID {$act['id']}: {$act['titulo']}\n";
    echo "    Estado: {$act['estado']} | No completada: " . ($act['no_completada'] ? 'SÍ' : 'NO') . "\n";
    echo "    Publicada: " . ($act['publicada'] ? 'SÍ' : 'NO') . " ({$act['pub_datetime']} <= {$act['now']})\n";
    echo "    No vencida: " . ($act['no_vencida'] ? 'SÍ' : 'NO') . " (Cierre: {$act['fecha_cierre']} {$act['hora_cierre']})\n";
    if (!$cumple) {
        echo "    ⚠️ NO DEBERÍA MOSTRARSE\n";
    }
}

echo "\n\n=== 6. BUSCAR 'prueba0' ESPECÍFICAMENTE ===\n";
$stmt = $db->prepare("SELECT * FROM actividades WHERE titulo LIKE '%prueba0%' AND usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$prueba = $stmt->fetch();

if ($prueba) {
    echo "✅ Encontrada actividad 'prueba0' (ID: {$prueba['id']})\n";
    echo "Estado: {$prueba['estado']}\n";
    echo "Autorizada: " . ($prueba['autorizada'] ? 'SÍ' : 'NO') . "\n";
    echo "Fecha publicación: {$prueba['fecha_publicacion']} {$prueba['hora_publicacion']}\n";
    echo "Fecha cierre: {$prueba['fecha_cierre']} {$prueba['hora_cierre']}\n";
    
    $pub_check = empty($prueba['fecha_publicacion']) || 
                 date('Y-m-d H:i:s', strtotime($prueba['fecha_publicacion'] . ' ' . ($prueba['hora_publicacion'] ?? '00:00:00'))) <= date('Y-m-d H:i:s');
    echo "¿Debería estar publicada? " . ($pub_check ? 'SÍ ✅' : 'NO ❌') . "\n";
} else {
    echo "❌ NO se encontró actividad 'prueba0' para este usuario\n";
}

echo "\n</pre>";
?>
