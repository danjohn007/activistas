<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("ERROR: Usuario no autenticado");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/activity.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

echo "<h2>Debug - Actividades Duplicadas</h2>";
echo "<p><strong>Usuario:</strong> " . $_SESSION['user_name'] . " (ID: $userId, Rol: $userRole)</p>";
echo "<hr>";

$activityModel = new Activity();
$filters = [];

if ($userRole === 'Activista') {
    $filters['usuario_id'] = $userId;
    $filters['exclude_expired'] = true;
}

$activities = $activityModel->getActivities($filters);

echo "<h3>Actividades obtenidas: " . count($activities) . "</h3>";

$activityIds = [];
$duplicates = [];

foreach ($activities as $activity) {
    $id = $activity['id'];
    if (isset($activityIds[$id])) {
        $activityIds[$id]++;
        $duplicates[] = $id;
    } else {
        $activityIds[$id] = 1;
    }
}

if (!empty($duplicates)) {
    echo "<p style='color: red;'><strong>⚠ Se encontraron " . count($duplicates) . " actividades duplicadas:</strong></p>";
    echo "<ul>";
    foreach (array_unique($duplicates) as $dupId) {
        echo "<li>Actividad ID $dupId aparece " . $activityIds[$dupId] . " veces</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ No hay duplicados en el resultado del modelo</strong></p>";
}

echo "<h3>Lista de actividades:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Título</th><th>Estado</th><th>Usuario</th><th>Tipo</th></tr>";

foreach ($activities as $activity) {
    echo "<tr>";
    echo "<td>" . $activity['id'] . "</td>";
    echo "<td>" . htmlspecialchars($activity['titulo']) . "</td>";
    echo "<td>" . $activity['estado'] . "</td>";
    echo "<td>" . htmlspecialchars($activity['usuario_nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($activity['tipo_nombre']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p><a href='activities/'>Ir a Actividades</a></p>";
?>
