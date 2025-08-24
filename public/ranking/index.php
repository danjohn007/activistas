<?php
/**
 * Ranking de Usuarios - Endpoint público
 * Muestra el ranking según el rol del usuario
 */

// Incluir el controlador de ranking
require_once __DIR__ . '/../../controllers/rankingController.php';

try {
    // Crear instancia del controlador
    $rankingController = new RankingController();
    
    // Mostrar ranking según el rol
    $rankingController->showRanking();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en ranking: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar el ranking: " . $e->getMessage();
    $currentUser = $_SESSION['user_role'] ?? 'activista';
    redirectWithMessage('dashboards/' . strtolower($currentUser) . '.php', $message, 'error');
}
?>