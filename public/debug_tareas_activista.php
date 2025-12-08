<?php
/**
 * Debug de getTareasActivista
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/corte.php';

$corteId = intval($_GET['corte_id'] ?? 3);
$usuarioId = intval($_GET['usuario_id'] ?? 1396);

echo "<h1>Debug getTareasActivista</h1>";
echo "<p>Corte ID: $corteId</p>";
echo "<p>Usuario ID: $usuarioId</p>";

try {
    $corteModel = new Corte();
    
    // Obtener información del corte
    $corte = $corteModel->getCorteById($corteId);
    echo "<h3>Información del Corte:</h3>";
    echo "<pre>" . print_r($corte, true) . "</pre>";
    
    if (!$corte) {
        die("Corte no encontrado");
    }
    
    // Query manual para debug
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>Prueba 1: Tareas CREADAS en el periodo</h3>";
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.descripcion,
            a.estado,
            a.fecha_creacion,
            a.fecha_actualizacion,
            ta.nombre as tipo_actividad
        FROM actividades a
        LEFT JOIN tipos_actividades ta ON a.actividad_id = ta.id
        WHERE a.usuario_id = ?
        AND a.tarea_pendiente = 1
        AND DATE(a.fecha_creacion) BETWEEN ? AND ?
    ");
    $stmt->execute([$usuarioId, $corte['fecha_inicio'], $corte['fecha_fin']]);
    $tareasCreadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total: " . count($tareasCreadas) . "</p>";
    if (!empty($tareasCreadas)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Descripción</th><th>Tipo</th><th>Estado</th><th>Fecha Creación</th><th>Fecha Actualización</th></tr>";
        foreach ($tareasCreadas as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>" . substr($t['descripcion'], 0, 50) . "...</td>";
            echo "<td>{$t['tipo_actividad']}</td>";
            echo "<td>{$t['estado']}</td>";
            echo "<td>{$t['fecha_creacion']}</td>";
            echo "<td>{$t['fecha_actualizacion']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Prueba 2: Tareas COMPLETADAS en el periodo</h3>";
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.descripcion,
            a.estado,
            a.fecha_creacion,
            a.fecha_actualizacion,
            ta.nombre as tipo_actividad
        FROM actividades a
        LEFT JOIN tipos_actividades ta ON a.actividad_id = ta.id
        WHERE a.usuario_id = ?
        AND a.tarea_pendiente = 1
        AND a.estado = 'completada'
        AND DATE(a.fecha_actualizacion) BETWEEN ? AND ?
    ");
    $stmt->execute([$usuarioId, $corte['fecha_inicio'], $corte['fecha_fin']]);
    $tareasCompletadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total: " . count($tareasCompletadas) . "</p>";
    if (!empty($tareasCompletadas)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Descripción</th><th>Tipo</th><th>Estado</th><th>Fecha Creación</th><th>Fecha Actualización</th></tr>";
        foreach ($tareasCompletadas as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>" . substr($t['descripcion'], 0, 50) . "...</td>";
            echo "<td>{$t['tipo_actividad']}</td>";
            echo "<td>{$t['estado']}</td>";
            echo "<td>{$t['fecha_creacion']}</td>";
            echo "<td>{$t['fecha_actualizacion']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Prueba 3: Query combinada (como getTareasActivista)</h3>";
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.descripcion,
            a.estado,
            a.fecha_creacion,
            a.fecha_actualizacion,
            ta.nombre as tipo_actividad
        FROM actividades a
        LEFT JOIN tipos_actividades ta ON a.actividad_id = ta.id
        WHERE a.usuario_id = ?
        AND a.tarea_pendiente = 1
        AND (
            (DATE(a.fecha_creacion) BETWEEN ? AND ?)
            OR 
            (a.estado = 'completada' AND DATE(a.fecha_actualizacion) BETWEEN ? AND ?)
        )
        ORDER BY a.fecha_creacion DESC
    ");
    $stmt->execute([$usuarioId, $corte['fecha_inicio'], $corte['fecha_fin'], $corte['fecha_inicio'], $corte['fecha_fin']]);
    $tareasCombinadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total: " . count($tareasCombinadas) . "</p>";
    if (!empty($tareasCombinadas)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Descripción</th><th>Tipo</th><th>Estado</th><th>Fecha Creación</th><th>Fecha Actualización</th></tr>";
        foreach ($tareasCombinadas as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>" . substr($t['descripcion'], 0, 50) . "...</td>";
            echo "<td>{$t['tipo_actividad']}</td>";
            echo "<td>{$t['estado']}</td>";
            echo "<td>{$t['fecha_creacion']}</td>";
            echo "<td>{$t['fecha_actualizacion']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Resultado usando el modelo:</h3>";
    $tareas = $corteModel->getTareasActivista($corteId, $usuarioId);
    echo "<p>Total: " . count($tareas) . "</p>";
    echo "<pre>" . print_r($tareas, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
