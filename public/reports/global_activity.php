<?php
/**
 * Global Activity Report
 * Shows statistics and completion rates grouped by activity type
 * Accessible only by SuperAdmin
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';

$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['rol'];

// Check permissions - only SuperAdmin can access
if ($userRole !== 'SuperAdmin') {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$activityModel = new Activity();

// Set up filters
$filters = [];

// Process date filters - default to current month
$fechaDesde = !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fechaHasta = !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-t');

$filters['fecha_desde'] = $fechaDesde;
$filters['fecha_hasta'] = $fechaHasta;

// Process search filter
if (!empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Get report data
$reportData = $activityModel->getGlobalActivityReport($filters, $page, $perPage);

// Page title
$title = 'Informe Global por Actividad';

// Include the view
include __DIR__ . '/../../views/reports/global_activity.php';
?>
