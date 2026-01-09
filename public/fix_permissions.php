<?php
/**
 * Script para verificar y arreglar permisos de directorios de uploads
 */

echo "<h2>Verificación y arreglo de permisos</h2>";

$directories = [
    __DIR__ . '/assets/uploads',
    __DIR__ . '/assets/uploads/evidencias',
    __DIR__ . '/assets/uploads/avatars'
];

foreach ($directories as $dir) {
    echo "<h3>Directorio: $dir</h3>";
    
    // Verificar si existe
    if (!file_exists($dir)) {
        echo "<p style='color: orange;'>⚠ El directorio no existe. Intentando crear...</p>";
        if (@mkdir($dir, 0777, true)) {
            echo "<p style='color: green;'>✓ Directorio creado exitosamente</p>";
        } else {
            echo "<p style='color: red;'>✗ No se pudo crear el directorio</p>";
            continue;
        }
    } else {
        echo "<p style='color: green;'>✓ El directorio existe</p>";
    }
    
    // Verificar permisos
    $perms = fileperms($dir);
    $permsOctal = substr(sprintf('%o', $perms), -4);
    echo "<p>Permisos actuales: $permsOctal</p>";
    
    // Verificar si es escribible
    if (is_writable($dir)) {
        echo "<p style='color: green;'>✓ El directorio es escribible</p>";
    } else {
        echo "<p style='color: red;'>✗ El directorio NO es escribible. Intentando cambiar permisos...</p>";
        if (@chmod($dir, 0777)) {
            echo "<p style='color: green;'>✓ Permisos cambiados a 0777</p>";
        } else {
            echo "<p style='color: red;'>✗ No se pudieron cambiar los permisos. Necesitas acceso SSH con sudo.</p>";
            echo "<p>Ejecuta en SSH: <code>sudo chmod -R 775 $dir && sudo chown -R www-data:www-data $dir</code></p>";
        }
    }
    
    // Intentar crear un archivo de prueba
    $testFile = $dir . '/test_' . time() . '.txt';
    if (@file_put_contents($testFile, 'test')) {
        echo "<p style='color: green;'>✓ Se puede crear archivos en el directorio</p>";
        @unlink($testFile);
    } else {
        echo "<p style='color: red;'>✗ NO se pueden crear archivos en el directorio</p>";
    }
    
    echo "<hr>";
}

echo "<h3>Información del servidor web</h3>";
echo "<p>Usuario PHP: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'No disponible') . "</p>";
echo "<p>Directorio de trabajo: " . getcwd() . "</p>";

echo "<p><a href='tasks/'>Volver a Tareas</a></p>";
?>
