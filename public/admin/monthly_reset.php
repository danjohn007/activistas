<?php
/**
 * Monthly Ranking Reset Utility
 * This script should be run monthly (ideally via cron job) to save current rankings
 * and reset for the new month
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/activity.php';

// Only SuperAdmin can access this utility
$auth = getAuth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
if ($currentUser['rol'] !== 'SuperAdmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo SuperAdmin puede ejecutar esta utilidad']);
    exit;
}

$activityModel = new Activity();

try {
    // Check if this is a POST request to actually perform the reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de seguridad inválido');
        }
        
        // Perform monthly ranking save and reset
        $result = $activityModel->saveMonthlyRankingsAndReset();
        
        if ($result) {
            logActivity("Ranking mensual resetado manualmente por usuario {$currentUser['id']}");
            redirectWithMessage('ranking/', 'Ranking mensual guardado y puntos reiniciados exitosamente', 'success');
        } else {
            throw new Exception('Error al guardar ranking mensual');
        }
    }
    
    // Show confirmation form
    $title = 'Reset Ranking Mensual';
    include __DIR__ . '/../../views/admin/monthly_reset.php';
    
} catch (Exception $e) {
    logActivity("Error en reset ranking mensual: " . $e->getMessage(), 'ERROR');
    redirectWithMessage('ranking/', 'Error: ' . $e->getMessage(), 'error');
}
?>