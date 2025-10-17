<?php
/**
 * Best Performers by Group Report
 * Shows the top leaders and activists per group with highest compliance percentage
 * Accessible only by SuperAdmin
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/group.php';

$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['rol'];

// Check permissions - only SuperAdmin can access
if ($userRole !== 'SuperAdmin') {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$groupModel = new Group();

// Set up filters
$filters = [];

// Process date filters - default to current month
$fechaDesde = !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fechaHasta = !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-t');

$filters['fecha_desde'] = $fechaDesde;
$filters['fecha_hasta'] = $fechaHasta;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Get report data
$reportData = $groupModel->getBestPerformersByGroup($filters, $page, $perPage);

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once __DIR__ . '/../../includes/excel_export.php';
    exportBestByGroupToExcel($reportData['groups'], $fechaDesde, $fechaHasta);
    exit;
}

// Page title
$title = 'Mejores por Grupo';

// Include the view
include __DIR__ . '/../../views/reports/best_by_group.php';
?>
