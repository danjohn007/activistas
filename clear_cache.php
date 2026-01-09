<?php
/**
 * Script de Limpieza de Caché
 * 
 * Limpia el caché del sistema para forzar la recarga de datos
 */

require_once __DIR__ . '/config/optimization.php';

echo "==============================================\n";
echo "LIMPIEZA DE CACHÉ DEL SISTEMA\n";
echo "==============================================\n\n";

// Limpiar caché expirado
echo "1. Limpiando caché expirado...\n";
$expiredCount = clearExpiredCache();
echo "   ✓ Archivos de caché expirado eliminados: $expiredCount\n\n";

// Limpiar todo el caché (descomenta si necesitas limpieza completa)
// echo "2. Limpiando TODO el caché...\n";
// $totalCount = clearAllCache();
// echo "   ✓ Total de archivos eliminados: $totalCount\n\n";

echo "==============================================\n";
echo "LIMPIEZA COMPLETADA\n";
echo "==============================================\n";
echo "\nEl caché se regenerará automáticamente en la próxima visita.\n";
?>
