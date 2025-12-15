<?php
/**
 * Activist Task Detail Report
 * Shows all tasks assigned and completed by a specific activist
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';
require_once __DIR__ . '/../../models/user.php';
require_once __DIR__ . '/../../config/database.php';

$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['rol'];

// Check permissions - only Admin, Gestor, and Líder can access
if (!in_array($userRole, ['SuperAdmin', 'Gestor', 'Líder'])) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$userId = intval($_GET['user_id'] ?? 0);

// Debug temporal
error_log("activist_tasks.php - user_id: " . $userId);
error_log("activist_tasks.php - corte_id: " . ($_GET['corte_id'] ?? 'none'));

if ($userId <= 0) {
    $backUrl = 'activists.php';
    if (!empty($_GET['corte_id'])) {
        $backUrl .= '?corte_id=' . intval($_GET['corte_id']);
    }
    redirectWithMessage($backUrl, 'Usuario no válido', 'error');
}

$activityModel = new Activity();
$userModel = new User();

// Get user info
$user = $userModel->getUserById($userId);

// Debug temporal
error_log("activist_tasks.php - user found: " . ($user ? 'yes' : 'no'));
if ($user) {
    error_log("activist_tasks.php - user name: " . $user['nombre_completo']);
    error_log("activist_tasks.php - user lider_id: " . ($user['lider_id'] ?? 'null'));
}

if (!$user) {
    $backUrl = 'activists.php';
    if (!empty($_GET['corte_id'])) {
        $backUrl .= '?corte_id=' . intval($_GET['corte_id']);
    }
    redirectWithMessage($backUrl, 'Usuario no encontrado', 'error');
}

// For leaders, verify this user belongs to their team
if ($userRole === 'Líder' && $user['lider_id'] != $currentUser['id']) {
    $backUrl = 'activists.php';
    if (!empty($_GET['corte_id'])) {
        $backUrl .= '?corte_id=' . intval($_GET['corte_id']);
    }
    redirectWithMessage($backUrl, 'No tienes permisos para ver este usuario', 'error');
}

// Check if viewing from a snapshot (corte)
$snapshotMode = false;
$corteData = null;
$corteId = !empty($_GET['corte_id']) ? intval($_GET['corte_id']) : null;

if ($corteId) {
    // Load corte data
    require_once __DIR__ . '/../../models/corte.php';
    $corteModel = new Corte();
    $corteData = $corteModel->getCorteById($corteId);
    
    if ($corteData) {
        $snapshotMode = true;
        
        // Get frozen statistics from cortes_detalle using the corte model connection
        $database = new Database();
        $db = $database->getConnection();
        
        $detalleStmt = $db->prepare("
            SELECT 
                tareas_asignadas,
                tareas_entregadas,
                porcentaje_cumplimiento,
                ranking_posicion
            FROM cortes_detalle
            WHERE corte_id = ? AND usuario_id = ?
        ");
        $detalleStmt->execute([$corteId, $userId]);
        $frozenStats = $detalleStmt->fetch();
        
        // Get tasks from the corte period
        // Note: We get ALL tasks for this user, then filter by completion date
        $filters = [
            'usuario_id' => $userId
        ];
        $allTasksRaw = $activityModel->getActivities($filters);
        
        // Filter tasks to only show those assigned and completed within the corte period
        $allTasks = [];
        foreach ($allTasksRaw as $task) {
            if ($task['tarea_pendiente'] == 1) {
                $fechaInicio = $corteData['fecha_inicio'];
                $fechaFin = $corteData['fecha_fin'];
                
                // Check if task was published before or during the period
                $fechaPublicacion = $task['fecha_publicacion'] ?? null;
                $isPublishedBeforePeriod = !$fechaPublicacion || $fechaPublicacion <= $fechaFin;
                
                if ($isPublishedBeforePeriod) {
                    // For completed tasks, check if completed within the period
                    if ($task['estado'] === 'completada') {
                        $fechaActualizacion = substr($task['fecha_actualizacion'], 0, 10); // Get date part only
                        if ($fechaActualizacion >= $fechaInicio && $fechaActualizacion <= $fechaFin) {
                            $allTasks[] = $task;
                        }
                    } else {
                        // For pending tasks, include if assigned during the period
                        $allTasks[] = $task;
                    }
                }
            }
        }
    } else {
        // Invalid corte_id, show real-time
        $snapshotMode = false;
    }
}

if (!$snapshotMode) {
    // Get date filters if provided
    $filters = ['usuario_id' => $userId];
    if (!empty($_GET['fecha_desde'])) {
        $filters['fecha_desde'] = $_GET['fecha_desde'];
    }
    if (!empty($_GET['fecha_hasta'])) {
        $filters['fecha_hasta'] = $_GET['fecha_hasta'];
    }
    
    // Get tasks assigned to this user with date filters applied
    $allTasks = $activityModel->getActivities($filters);
    $frozenStats = null;
}

// Separate tasks into assigned and completed
$assignedTasks = [];
$completedTasks = [];

foreach ($allTasks as $task) {
    if ($task['tarea_pendiente'] == 1) {
        if ($task['estado'] === 'completada') {
            $completedTasks[] = $task;
        } else {
            $assignedTasks[] = $task;
        }
    }
}

$title = 'Detalle de Tareas - ' . $user['nombre_completo'];

// Pass variables to view
$isSnapshot = $snapshotMode;
$snapshotInfo = $corteData;
$frozenStatistics = $frozenStats;

// Include the view
include __DIR__ . '/../../views/reports/activist_tasks.php';
?>
