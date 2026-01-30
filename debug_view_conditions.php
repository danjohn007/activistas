<?php
require_once 'config/database.php';
require_once 'models/activity.php';
require_once 'models/user.php';

session_start();
$_SESSION['user_id'] = 1; // Simular usuario admin

$activityModel = new Activity();
$actividad_id = 144431;

echo "=== DEBUG CONDICIONES DEL VIEW ===\n\n";

// Obtener la actividad EXACTAMENTE como lo hace el controlador
$activity = $activityModel->getActivityById($actividad_id);

echo "--- DATOS DE \$activity ---\n";
echo "ID: " . ($activity['id'] ?? 'NO EXISTE') . "\n";
echo "tarea_pendiente: " . ($activity['tarea_pendiente'] ?? 'NO EXISTE') . " (tipo: " . gettype($activity['tarea_pendiente'] ?? null) . ")\n";
echo "solicitante_nombre: '" . ($activity['solicitante_nombre'] ?? 'NO EXISTE') . "'\n";
echo "estado: " . ($activity['estado'] ?? 'NO EXISTE') . "\n";
echo "\n";

// Obtener evidencias EXACTAMENTE como lo hace el controlador
$evidence = $activityModel->getActivityEvidence($actividad_id);
$referenceFiles = $activityModel->getReferenceFiles($actividad_id);

echo "--- EVIDENCIAS (getActivityEvidence - bloqueada=1) ---\n";
echo "Total: " . count($evidence) . "\n";
foreach ($evidence as $ev) {
    echo "  - ID {$ev['id']}: bloqueada={$ev['bloqueada']}, archivo='{$ev['archivo']}'\n";
}
echo "\n";

echo "--- ARCHIVOS DE REFERENCIA (getReferenceFiles - bloqueada=0) ---\n";
echo "Total: " . count($referenceFiles) . "\n";
foreach ($referenceFiles as $ref) {
    echo "  - ID {$ref['id']}: bloqueada={$ref['bloqueada']}, archivo='{$ref['archivo']}'\n";
}
echo "\n";

// Verificar condiciones del view
echo "--- CONDICIONES PARA MOSTRAR REFERENCIAS ---\n";
$condition1 = !empty($activity['tarea_pendiente']);
$condition2 = !empty($activity['solicitante_nombre']);
$condition3 = !empty($referenceFiles) && count($referenceFiles) > 0;

echo "!empty(\$activity['tarea_pendiente']): " . ($condition1 ? "✅ TRUE" : "❌ FALSE") . "\n";
echo "!empty(\$activity['solicitante_nombre']): " . ($condition2 ? "✅ TRUE" : "❌ FALSE") . "\n";
echo "!empty(\$referenceFiles) && count(\$referenceFiles) > 0: " . ($condition3 ? "✅ TRUE" : "❌ FALSE") . "\n";

$shouldShowReferences = $condition1 && $condition2 && $condition3;
echo "\n🎯 ¿Debería mostrar referencias?: " . ($shouldShowReferences ? "✅ SÍ" : "❌ NO") . "\n\n";

echo "--- CONDICIONES PARA MOSTRAR EVIDENCIAS ---\n";
$shouldShowEvidence = !empty($evidence);
echo "!empty(\$evidence): " . ($shouldShowEvidence ? "✅ TRUE (sí muestra)" : "❌ FALSE (no muestra)") . "\n";
