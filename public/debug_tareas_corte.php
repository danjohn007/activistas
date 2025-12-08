<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

$corteId = 5; // Ajusta según tu corte
$usuarioId = 1396;

echo "<h1>Debug - Ver qué tareas se están recuperando</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener info del corte
    $stmt = $db->prepare("SELECT * FROM cortes_periodo WHERE id = ?");
    $stmt->execute([$corteId]);
    $corte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Info del Corte:</h3>";
    echo "ID: {$corte['id']}<br>";
    echo "Nombre: {$corte['nombre']}<br>";
    echo "Fecha Inicio: {$corte['fecha_inicio']}<br>";
    echo "Fecha Fin: {$corte['fecha_fin']}<br>";
    echo "Fecha Creación Corte: {$corte['fecha_creacion']}<br>";
    
    // Query actual
    $sql = "
        SELECT 
            a.id,
            a.descripcion,
            a.fecha_creacion,
            a.fecha_publicacion,
            a.hora_publicacion,
            a.estado,
            a.fecha_actualizacion,
            ta.nombre as tipo_actividad
        FROM actividades a
        LEFT JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
        WHERE a.usuario_id = ?
        AND a.tarea_pendiente = 1
        AND DATE(a.fecha_creacion) BETWEEN ? AND ?
        AND (
            a.fecha_publicacion IS NULL 
            OR DATE(a.fecha_publicacion) <= ?
        )
        ORDER BY a.fecha_creacion DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$usuarioId, $corte['fecha_inicio'], $corte['fecha_fin'], $corte['fecha_fin']]);
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tareas recuperadas: " . count($tareas) . "</h3>";
    
    if (!empty($tareas)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Descripción</th>";
        echo "<th>Tipo</th>";
        echo "<th>Fecha Creación</th>";
        echo "<th>Fecha Publicación</th>";
        echo "<th>Estado</th>";
        echo "<th>¿Dentro del periodo?</th>";
        echo "</tr>";
        
        foreach ($tareas as $t) {
            $dentroDelPeriodo = (
                $t['fecha_creacion'] >= $corte['fecha_inicio'] . ' 00:00:00' &&
                $t['fecha_creacion'] <= $corte['fecha_fin'] . ' 23:59:59'
            ) ? 'SÍ' : 'NO';
            
            $style = ($dentroDelPeriodo === 'NO') ? "style='background-color: #ffcccc;'" : "";
            
            echo "<tr $style>";
            echo "<td>{$t['id']}</td>";
            echo "<td>" . substr($t['descripcion'], 0, 30) . "...</td>";
            echo "<td>{$t['tipo_actividad']}</td>";
            echo "<td>{$t['fecha_creacion']}</td>";
            echo "<td>" . ($t['fecha_publicacion'] ?? 'NULL') . "</td>";
            echo "<td>{$t['estado']}</td>";
            echo "<td><strong>$dentroDelPeriodo</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Todas las tareas del usuario (para comparar):</h3>";
    $stmt = $db->prepare("
        SELECT id, descripcion, fecha_creacion, estado 
        FROM actividades 
        WHERE usuario_id = ? 
        AND tarea_pendiente = 1 
        ORDER BY fecha_creacion DESC 
        LIMIT 10
    ");
    $stmt->execute([$usuarioId]);
    $todasTareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Descripción</th><th>Fecha Creación</th><th>Estado</th></tr>";
    foreach ($todasTareas as $t) {
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>" . substr($t['descripcion'], 0, 40) . "</td>";
        echo "<td>{$t['fecha_creacion']}</td>";
        echo "<td>{$t['estado']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
