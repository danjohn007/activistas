<?php
/**
 * Activist Task Detail Report
 * Shows all tasks assigned and completed by a specific activist
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';
require_once __DIR__ . '/../../models/user.php';

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

if ($userId <= 0) {
    redirectWithMessage('reports/activists.php', 'Usuario no válido', 'error');
}

$activityModel = new Activity();
$userModel = new User();

// Get user info
$user = $userModel->getUserById($userId);

if (!$user) {
    redirectWithMessage('reports/activists.php', 'Usuario no encontrado', 'error');
}

// For leaders, verify this user belongs to their team
if ($userRole === 'Líder' && $user['lider_id'] != $currentUser['id']) {
    redirectWithMessage('reports/activists.php', 'No tienes permisos para ver este usuario', 'error');
}

// Get all tasks assigned to this user
$allTasks = $activityModel->getActivities(['usuario_id' => $userId]);

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

// Include the view
include __DIR__ . '/../../views/reports/activist_tasks.php';
?>
