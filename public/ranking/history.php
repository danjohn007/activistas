<?php
/**
 * Historial de Rankings - Endpoint público
 * Muestra el historial de todos los cortes de ranking con top 3
 */

// Incluir el controlador de ranking
require_once __DIR__ . '/../../controllers/rankingController.php';

try {
    // Crear instancia del controlador
    $rankingController = new RankingController();
    
    // Mostrar historial de rankings
    $rankingController->showRankingHistory();
    
} catch (Exception $e) {
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en historial de ranking: " . $e->getMessage(), 'ERROR');
    }
    
    // Redireccionar con mensaje de error
    $message = "Error al cargar el historial de ranking: " . $e->getMessage();
    redirectWithMessage('ranking/', $message, 'error');
}
?>