<?php
/**
 * Activist Performance Report
 * Shows task completion statistics and performance metrics
 * Accessible by Admin (all activists) and Leaders (their team only)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';

$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['rol'];

// Check permissions - only Admin, Gestor, and Líder can access
if (!in_array($userRole, ['SuperAdmin', 'Gestor', 'Líder'])) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$activityModel = new Activity();

// Set up filters
$filters = [];

// For leaders, only show their team
if ($userRole === 'Líder') {
    $filters['lider_id'] = $currentUser['id'];
}

// Process search filters
if (!empty($_GET['search_name'])) {
    $filters['search_name'] = trim($_GET['search_name']);
}

if (!empty($_GET['search_email'])) {
    $filters['search_email'] = trim($_GET['search_email']);
}

if (!empty($_GET['search_phone'])) {
    $filters['search_phone'] = trim($_GET['search_phone']);
}

if (!empty($_GET['fecha_desde'])) {
    $filters['fecha_desde'] = $_GET['fecha_desde'];
}

if (!empty($_GET['fecha_hasta'])) {
    $filters['fecha_hasta'] = $_GET['fecha_hasta'];
}

// Group filtering (SuperAdmin only)
if (!empty($_GET['grupo_id']) && $userRole === 'SuperAdmin') {
    $filters['grupo_id'] = intval($_GET['grupo_id']);
}

// Leader filtering (SuperAdmin and Gestor)
if (!empty($_GET['filter_lider_id']) && in_array($userRole, ['SuperAdmin', 'Gestor'])) {
    $filters['filter_lider_id'] = intval($_GET['filter_lider_id']);
}

// Load groups and leaders for filtering
$groups = [];
$leaders = [];
if ($userRole === 'SuperAdmin') {
    require_once __DIR__ . '/../../models/group.php';
    require_once __DIR__ . '/../../models/user.php';
    $groupModel = new Group();
    $userModel = new User();
    $groups = $groupModel->getActiveGroups();
    $leaders = $userModel->getActiveLiders();
} elseif ($userRole === 'Gestor') {
    require_once __DIR__ . '/../../models/user.php';
    $userModel = new User();
    $leaders = $userModel->getActiveLiders();
}

// Get report data
$reportData = $activityModel->getActivistReport($filters);

// Debug: Log if no data
if (empty($reportData)) {
    error_log("No activist report data found. Filters: " . print_r($filters, true));
}

// Page title
$title = $userRole === 'Líder' ? 'Reporte de mi Equipo' : 'Reporte de Activistas';

// Include the view
include __DIR__ . '/../../views/reports/activists.php';
?>