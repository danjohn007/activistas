<?php
/**
 * LIMPIAR CACHÉ COMPLETO
 */

echo "<h1>🧹 Limpiando Caché</h1><pre>";

// 1. OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache limpiado\n";
} else {
    echo "⚠️ OPcache no disponible\n";
}

// 2. Caché de archivos
$cacheDir = __DIR__ . '/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    echo "✅ $count archivos de caché eliminados\n";
}

// 3. Statcache
clearstatcache(true);
echo "✅ Statcache limpiado\n";

// 4. Session restart
session_start();
session_regenerate_id(true);
echo "✅ Sesión regenerada\n";

echo "\n🎉 Caché completamente limpiado\n";
echo "\n➡️ Ahora ve a la página de actividades y presiona Ctrl+Shift+R\n";
echo "</pre>";
?>
