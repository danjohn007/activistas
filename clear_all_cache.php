<?php
/**
 * LIMPIAR CACHÉ COMPLETO
 * Ejecuta este archivo para eliminar toda la caché y ver los cambios inmediatamente
 */

// Limpiar caché de archivos
$cacheDir = __DIR__ . '/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.cache');
    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }
    echo "✅ Eliminados $deleted archivos de caché\n";
} else {
    echo "⚠️ Directorio cache/ no existe\n";
}

// Limpiar sesiones si existen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
echo "✅ Sesión limpiada\n";

// Limpiar caché de OPcache si está habilitado
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache limpiado\n";
}

// Limpiar realpath cache
clearstatcache(true);
echo "✅ StatCache limpiado\n";

echo "\n🎉 CACHÉ COMPLETAMENTE LIMPIADO\n";
echo "Ahora recarga tu página para ver los cambios\n";
?>
