<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id'])) {
    die("Falta parámetro id");
}

$activityId = intval($_GET['id']);
$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug Evidencias - Actividad #$activityId</h1><pre>";

// 1. Ver todas las evidencias de esta actividad
$stmt = $db->prepare("
    SELECT id, tipo_evidencia, archivo, contenido, bloqueada, fecha_subida
    FROM evidencias
    WHERE actividad_id = ?
    ORDER BY bloqueada ASC, fecha_subida ASC
");
$stmt->execute([$activityId]);
$evidencias = $stmt->fetchAll();

echo "=== TODAS LAS EVIDENCIAS EN LA BD ===\n";
echo "Total: " . count($evidencias) . "\n\n";

foreach ($evidencias as $ev) {
    $tipo = $ev['bloqueada'] == 0 ? '📎 REFERENCIA (bloqueada=0)' : '✅ EVIDENCIA (bloqueada=1)';
    echo "$tipo\n";
    echo "  ID: {$ev['id']}\n";
    echo "  Tipo: {$ev['tipo_evidencia']}\n";
    echo "  Archivo: {$ev['archivo']}\n";
    echo "  Contenido: " . substr($ev['contenido'], 0, 50) . "...\n";
    echo "  Fecha: {$ev['fecha_subida']}\n\n";
}

// 2. Ver datos de la actividad
$stmt = $db->prepare("SELECT * FROM actividades WHERE id = ?");
$stmt->execute([$activityId]);
$actividad = $stmt->fetch();

echo "\n=== DATOS DE LA ACTIVIDAD ===\n";
echo "Título: {$actividad['titulo']}\n";
echo "Estado: {$actividad['estado']}\n";
echo "Tarea pendiente: " . ($actividad['tarea_pendiente'] ? 'SÍ' : 'NO') . "\n";
echo "Usuario ID: {$actividad['usuario_id']}\n";
echo "Solicitante ID: {$actividad['solicitante_id']}\n";

echo "\n</pre>";

echo "<hr><h3>Diagnóstico:</h3>";
$referencias = array_filter($evidencias, fn($e) => $e['bloqueada'] == 0);
$completadas = array_filter($evidencias, fn($e) => $e['bloqueada'] == 1);

echo "<ul>";
echo "<li><strong>Imágenes de referencia (bloqueada=0):</strong> " . count($referencias) . "</li>";
echo "<li><strong>Evidencias completadas (bloqueada=1):</strong> " . count($completadas) . "</li>";
echo "</ul>";

if (count($referencias) == 0 && count($completadas) > 0) {
    echo "<div class='alert alert-warning'>⚠️ <strong>Problema detectado:</strong> No hay imágenes de referencia pero sí hay evidencias. Las imágenes de referencia fueron eliminadas o nunca existieron.</div>";
} elseif (count($referencias) > 0) {
    echo "<div class='alert alert-success'>✅ Las imágenes de referencia ESTÁN en la base de datos.</div>";
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
