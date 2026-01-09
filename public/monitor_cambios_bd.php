<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("ERROR: Usuario no autenticado");
}

require_once __DIR__ . '/../config/database.php';

echo "<h2>Monitoreo de Cambios en Base de Datos</h2>";
echo "<p>Este debug muestra los cambios en tiempo real</p>";
echo "<hr>";

$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];

// Obtener snapshot actual
$stmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM actividades 
    WHERE usuario_id = ?
");
$stmt->execute([$userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total = $result['total'];

// Listar todas con detalles
$stmt = $db->prepare("
    SELECT 
        a.id,
        a.titulo,
        a.estado,
        a.fecha_creacion,
        TIMESTAMPDIFF(SECOND, a.fecha_creacion, NOW()) as segundos_desde_creacion
    FROM actividades a
    WHERE a.usuario_id = ?
    ORDER BY a.id DESC
");
$stmt->execute([$userId]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Total de actividades: $total</h3>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th><th>Título</th><th>Estado</th><th>Creada hace</th><th>Fecha Creación</th>";
echo "</tr>";

foreach ($activities as $act) {
    $tiempo = $act['segundos_desde_creacion'];
    $tiempoTexto = $tiempo < 60 ? "$tiempo segundos" : round($tiempo/60) . " minutos";
    
    echo "<tr>";
    echo "<td><strong>" . $act['id'] . "</strong></td>";
    echo "<td>" . htmlspecialchars($act['titulo']) . "</td>";
    echo "<td>" . $act['estado'] . "</td>";
    echo "<td>" . $tiempoTexto . "</td>";
    echo "<td><small>" . $act['fecha_creacion'] . "</small></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<div style='background: #ffffcc; padding: 15px; border: 1px solid #cccc00;'>";
echo "<h4>📋 Instrucciones:</h4>";
echo "<ol>";
echo "<li><strong>Deja esta página abierta</strong></li>";
echo "<li>En otra pestaña, ve a <a href='activities/' target='_blank'>Actividades</a></li>";
echo "<li><strong>ELIMINA una actividad</strong></li>";
echo "<li>Vuelve a esta pestaña y <button onclick='location.reload()' style='padding: 5px 10px; cursor: pointer;'>🔄 Recargar</button></li>";
echo "<li>Verifica si:<ul>";
echo "<li>✅ El total disminuyó en 1</li>";
echo "<li>❌ El total aumentó o aparecieron actividades nuevas (BUG)</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='activities/'>Ir a Actividades</a></p>";
?>
