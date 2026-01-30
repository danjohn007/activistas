<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/activity.php';

if (!isset($_GET['id'])) {
    die("Falta parámetro id");
}

$activityId = intval($_GET['id']);
$activityModel = new Activity();

echo "<h1>🔍 Debug Vista de Detalle - Actividad #$activityId</h1><pre>";

// Obtener los mismos datos que el controlador
$activity = $activityModel->getActivityById($activityId);
$evidence = $activityModel->getActivityEvidence($activityId);
$referenceFiles = $activityModel->getReferenceFiles($activityId);

echo "=== DATOS DE LA ACTIVIDAD (como los ve el controlador) ===\n";
echo "ID: {$activity['id']}\n";
echo "Título: {$activity['titulo']}\n";
echo "tarea_pendiente: " . ($activity['tarea_pendiente'] ?? 'NULL') . "\n";
echo "solicitante_nombre: " . ($activity['solicitante_nombre'] ?? 'NULL/VACÍO') . "\n";
echo "usuario_nombre: {$activity['usuario_nombre']}\n";
echo "\n";

echo "=== EVIDENCIAS (bloqueada=1) ===\n";
echo "Total: " . count($evidence) . "\n";
foreach ($evidence as $e) {
    echo "  - {$e['tipo_evidencia']}: {$e['archivo']}\n";
}
echo "\n";

echo "=== ARCHIVOS DE REFERENCIA (bloqueada=0) ===\n";
echo "Total: " . count($referenceFiles) . "\n";
foreach ($referenceFiles as $r) {
    echo "  - {$r['tipo_evidencia']}: {$r['archivo']}\n";
}
echo "\n";

echo "=== EVALUACIÓN DE CONDICIONES DE LA VISTA ===\n";
$cond1 = !empty($activity['tarea_pendiente']);
$cond2 = !empty($activity['solicitante_nombre']);
$cond3 = !empty($referenceFiles) && count($referenceFiles) > 0;

echo "!empty(\$activity['tarea_pendiente']): " . ($cond1 ? '✅ TRUE' : '❌ FALSE') . "\n";
echo "!empty(\$activity['solicitante_nombre']): " . ($cond2 ? '✅ TRUE' : '❌ FALSE') . "\n";
echo "!empty(\$referenceFiles) && count(\$referenceFiles) > 0: " . ($cond3 ? '✅ TRUE' : '❌ FALSE') . "\n";
echo "\n";

if ($cond1 && $cond2) {
    echo "✅ Se cumple la primera condición: Se mostrará 'Información de la Tarea'\n";
    if ($cond3) {
        echo "✅ Se cumple la segunda condición: Se mostrarán los 'Archivos de Referencia'\n";
    } else {
        echo "❌ NO se cumple la segunda condición: NO se mostrarán los 'Archivos de Referencia'\n";
        echo "   Razón: \$referenceFiles está vacío o es NULL\n";
    }
} else {
    echo "❌ NO se cumple la primera condición: NO se mostrará nada\n";
    if (!$cond1) echo "   Problema: tarea_pendiente es falso o NULL\n";
    if (!$cond2) echo "   Problema: solicitante_nombre está vacío o es NULL\n";
}

echo "\n</pre>";
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
