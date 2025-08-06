<?php
/**
 * Test script para verificar las consultas de datos del dashboard
 */

// Incluir dependencias
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/user.php';
require_once __DIR__ . '/models/activity.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== Test de Consultas del Dashboard ===\n\n";

try {
    // Inicializar modelos
    $userModel = new User();
    $activityModel = new Activity();
    
    echo "1. Probando getUserStats()...\n";
    $userStats = $userModel->getUserStats();
    echo "Resultado: " . json_encode($userStats, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "2. Probando getActivityStats()...\n";
    $activityStats = $activityModel->getActivityStats();
    echo "Resultado: " . json_encode($activityStats, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "3. Probando getActivitiesByType()...\n";
    $activitiesByType = $activityModel->getActivitiesByType();
    echo "Resultado: " . json_encode($activitiesByType, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "4. Probando getPendingUsers()...\n";
    $pendingUsers = $userModel->getPendingUsers();
    echo "Cantidad de usuarios pendientes: " . count($pendingUsers) . "\n\n";
    
    echo "=== Test completado exitosamente ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>