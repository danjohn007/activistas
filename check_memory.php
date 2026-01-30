<?php
/**
 * DIAGNÓSTICO DE PROBLEMA DE MEMORIA
 */
session_start();
require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Diagnóstico de Memoria</h1><pre>";

$db = Database::getInstance()->getConnection();

echo "=== 1. CONTEO DE TABLAS ===\n";
$tables = ['actividades', 'evidencias', 'usuarios', 'tipos_actividades'];
foreach ($tables as $table) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM $table");
    $count = $stmt->fetch()['total'];
    echo sprintf("%-20s: %s registros\n", $table, number_format($count));
}

echo "\n=== 2. PRUEBA DE SUBCONSULTA PROBLEMÁTICA ===\n";
echo "Ejecutando: SELECT actividad_id, COUNT(*) FROM evidencias GROUP BY actividad_id\n";
$start = microtime(true);
$stmt = $db->query("SELECT actividad_id, COUNT(*) as evidence_count FROM evidencias GROUP BY actividad_id");
$results = $stmt->fetchAll();
$time = microtime(true) - $start;
echo "✅ Completado en " . round($time, 3) . " segundos\n";
echo "Resultados: " . count($results) . " actividades con evidencias\n";

echo "\n=== 3. MEMORIA UTILIZADA ===\n";
echo "Memoria actual: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "Memoria pico: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "Límite memoria: " . ini_get('memory_limit') . "\n";

echo "\n=== 4. PRUEBA DE QUERY COMPLETA (CON LIMIT) ===\n";
$sql = "SELECT DISTINCT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre,
               s.nombre_completo as solicitante_nombre, u.email as usuario_correo, u.telefono as usuario_telefono,
               p.nombre_completo as propuesto_por_nombre, auth.nombre_completo as autorizado_por_nombre,
               COALESCE(ec.evidence_count, 0) as evidence_count
        FROM actividades a 
        JOIN usuarios u ON a.usuario_id = u.id 
        JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
        LEFT JOIN usuarios s ON a.solicitante_id = s.id
        LEFT JOIN usuarios p ON a.propuesto_por = p.id
        LEFT JOIN usuarios auth ON a.autorizado_por = auth.id
        LEFT JOIN (
            SELECT actividad_id, COUNT(*) as evidence_count 
            FROM evidencias 
            GROUP BY actividad_id
        ) ec ON a.id = ec.actividad_id
        WHERE a.usuario_id = 1396
        AND (a.autorizada = 1 OR a.propuesto_por IS NULL)
        LIMIT 15";

$start = microtime(true);
$stmt = $db->query($sql);
$activities = $stmt->fetchAll();
$time = microtime(true) - $start;
echo "✅ Query ejecutada en " . round($time, 3) . " segundos\n";
echo "Actividades devueltas: " . count($activities) . "\n";
echo "Memoria después de query: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";

echo "\n=== 5. PROBAR SIN SUBCONSULTA ===\n";
$sql2 = "SELECT DISTINCT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre
        FROM actividades a 
        JOIN usuarios u ON a.usuario_id = u.id 
        JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
        WHERE a.usuario_id = 1396
        AND (a.autorizada = 1 OR a.propuesto_por IS NULL)
        LIMIT 15";

$start = microtime(true);
$stmt = $db->query($sql2);
$activities2 = $stmt->fetchAll();
$time = microtime(true) - $start;
echo "✅ Query SIN subconsulta ejecutada en " . round($time, 3) . " segundos\n";
echo "Actividades devueltas: " . count($activities2) . "\n";
echo "Memoria: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";

echo "\n=== 6. DIAGNÓSTICO ===\n";
$totalEvidencias = $db->query("SELECT COUNT(*) as total FROM evidencias")->fetch()['total'];
$totalActividades = $db->query("SELECT COUNT(*) as total FROM actividades")->fetch()['total'];

if ($totalEvidencias > 50000) {
    echo "⚠️ PROBLEMA DETECTADO: Hay " . number_format($totalEvidencias) . " evidencias\n";
    echo "La subconsulta GROUP BY está procesando demasiados registros\n";
    echo "SOLUCIÓN: Optimizar la subconsulta o desactivar include_evidence_count\n";
} else {
    echo "✅ Cantidad de evidencias es manejable: " . number_format($totalEvidencias) . "\n";
}

echo "\n</pre>";
?>
