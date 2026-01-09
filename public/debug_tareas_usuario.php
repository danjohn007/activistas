<?php
/**
 * Script de depuración - Verificar tareas de usuario
 */

session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    die("ERROR: Usuario no autenticado. Por favor inicia sesión primero.");
}

require_once __DIR__ . '/../config/database.php';

echo "<h2>Debug - Tareas de Usuario</h2>";
echo "<p><strong>Usuario actual:</strong> " . $_SESSION['user_name'] . " (ID: " . $_SESSION['user_id'] . ", Rol: " . $_SESSION['user_role'] . ")</p>";
echo "<hr>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("ERROR: No se pudo conectar a la base de datos");
    }
    
    echo "<p style='color: green;'>✓ Conexión a base de datos exitosa</p>";
    
    // Obtener todas las actividades del usuario
    $userId = $_SESSION['user_id'];
    
    echo "<h3>1. Todas las actividades asignadas al usuario</h3>";
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.titulo,
            a.descripcion,
            a.tarea_pendiente,
            a.estado,
            a.usuario_id,
            a.solicitante_id,
            a.fecha_cierre,
            a.hora_cierre,
            a.fecha_publicacion,
            a.hora_publicacion,
            ta.nombre as tipo_nombre,
            u1.nombre_completo as usuario_nombre,
            u2.nombre_completo as solicitante_nombre
        FROM actividades a
        LEFT JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
        LEFT JOIN usuarios u1 ON a.usuario_id = u1.id
        LEFT JOIN usuarios u2 ON a.solicitante_id = u2.id
        WHERE a.usuario_id = ?
        ORDER BY a.fecha_creacion DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activities)) {
        echo "<p style='color: red;'>⚠ No hay actividades asignadas a este usuario</p>";
    } else {
        echo "<p style='color: green;'>✓ Se encontraron " . count($activities) . " actividad(es)</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>
                <th>ID</th>
                <th>Título</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Tarea Pendiente</th>
                <th>Solicitante</th>
                <th>Fecha Cierre</th>
                <th>Fecha Publicación</th>
              </tr>";
        foreach ($activities as $act) {
            echo "<tr>";
            echo "<td>" . $act['id'] . "</td>";
            echo "<td>" . htmlspecialchars($act['titulo']) . "</td>";
            echo "<td>" . htmlspecialchars($act['tipo_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($act['estado']) . "</td>";
            echo "<td>" . ($act['tarea_pendiente'] ? 'Sí' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($act['solicitante_nombre']) . "</td>";
            echo "<td>" . ($act['fecha_cierre'] ? $act['fecha_cierre'] . ' ' . $act['hora_cierre'] : 'N/A') . "</td>";
            echo "<td>" . ($act['fecha_publicacion'] ? $act['fecha_publicacion'] . ' ' . $act['hora_publicacion'] : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>2. Tareas pendientes según query del modelo</h3>";
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.titulo,
            a.tarea_pendiente,
            a.estado,
            a.usuario_id,
            a.solicitante_id,
            a.fecha_cierre,
            a.fecha_publicacion,
            ta.nombre as tipo_nombre
        FROM actividades a
        JOIN usuarios s ON a.solicitante_id = s.id
        JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
        WHERE a.tarea_pendiente = 1 
        AND a.usuario_id = ?
        AND a.usuario_id != a.solicitante_id
        AND a.estado != 'completada'
        AND (a.fecha_cierre IS NULL OR a.fecha_cierre > CURDATE() 
             OR (a.fecha_cierre = CURDATE() AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))
        AND (a.fecha_publicacion IS NULL 
             OR a.fecha_publicacion < NOW()
             OR (DATE(a.fecha_publicacion) = CURDATE() AND (a.hora_publicacion IS NULL OR a.hora_publicacion <= CURTIME())))
        ORDER BY a.fecha_creacion DESC
    ");
    $stmt->execute([$userId]);
    $pendingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingTasks)) {
        echo "<p style='color: red;'>⚠ No hay tareas pendientes según el query del modelo</p>";
        
        // Verificar cada condición del WHERE
        echo "<h4>Análisis de condiciones:</h4>";
        $stmt = $db->prepare("SELECT * FROM actividades WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample) {
            echo "<ul>";
            echo "<li>tarea_pendiente = " . $sample['tarea_pendiente'] . " (debe ser 1)</li>";
            echo "<li>estado = " . $sample['estado'] . " (no debe ser 'completada')</li>";
            echo "<li>usuario_id = " . $sample['usuario_id'] . ", solicitante_id = " . $sample['solicitante_id'] . " (deben ser diferentes)</li>";
            echo "<li>fecha_cierre = " . ($sample['fecha_cierre'] ?? 'NULL') . "</li>";
            echo "<li>fecha_publicacion = " . ($sample['fecha_publicacion'] ?? 'NULL') . "</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color: green;'>✓ Se encontraron " . count($pendingTasks) . " tarea(s) pendiente(s)</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>
                <th>ID</th>
                <th>Título</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Solicitante ID</th>
              </tr>";
        foreach ($pendingTasks as $task) {
            echo "<tr>";
            echo "<td>" . $task['id'] . "</td>";
            echo "<td>" . htmlspecialchars($task['titulo']) . "</td>";
            echo "<td>" . htmlspecialchars($task['tipo_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($task['estado']) . "</td>";
            echo "<td>" . $task['solicitante_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='tasks/'>Ir a Tareas</a> | <a href='activities/'>Ir a Actividades</a> | <a href='dashboards/activista.php'>Ir al Dashboard</a></p>";
?>
