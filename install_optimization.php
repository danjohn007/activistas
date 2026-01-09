<?php
/**
 * Script de Instalación de Optimizaciones
 * 
 * Ejecutar este archivo una vez para preparar el sistema
 * para las optimizaciones implementadas
 */

echo "==============================================\n";
echo "INSTALACIÓN DE OPTIMIZACIONES - DASHBOARD\n";
echo "==============================================\n\n";

$baseDir = __DIR__;
$errors = [];
$warnings = [];
$success = [];

// 1. Crear directorio de caché
echo "1. Creando estructura de directorios de caché...\n";

$cacheDirs = [
    $baseDir . '/cache',
    $baseDir . '/cache/dashboard',
    $baseDir . '/cache/users',
    $baseDir . '/cache/activities',
    $baseDir . '/cache/reports'
];

foreach ($cacheDirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) {
            $success[] = "✓ Creado: $dir";
            echo "   ✓ $dir\n";
        } else {
            $errors[] = "✗ No se pudo crear: $dir";
            echo "   ✗ ERROR: $dir\n";
        }
    } else {
        echo "   ○ Ya existe: $dir\n";
    }
}

// 2. Verificar permisos de escritura
echo "\n2. Verificando permisos de escritura...\n";

foreach ($cacheDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "   ✓ Escritura OK: $dir\n";
        } else {
            $warnings[] = "⚠ Sin permisos de escritura: $dir";
            echo "   ⚠ ADVERTENCIA: Sin permisos en $dir\n";
            echo "     Ejecutar: chmod 755 $dir\n";
        }
    }
}

// 3. Verificar archivos de configuración
echo "\n3. Verificando archivos de configuración...\n";

$configFiles = [
    $baseDir . '/config/optimization.php' => 'Configuración de optimización',
    $baseDir . '/config/database.php' => 'Configuración de base de datos'
];

foreach ($configFiles as $file => $desc) {
    if (file_exists($file)) {
        echo "   ✓ $desc: OK\n";
    } else {
        $warnings[] = "⚠ Falta archivo: $file";
        echo "   ⚠ ADVERTENCIA: No existe $file\n";
    }
}

// 4. Crear archivo .htaccess para proteger caché
echo "\n4. Protegiendo directorio de caché...\n";

$htaccessContent = "# Denegar acceso directo al caché\nDeny from all\n";
$htaccessFile = $baseDir . '/cache/.htaccess';

if (!file_exists($htaccessFile)) {
    if (@file_put_contents($htaccessFile, $htaccessContent)) {
        $success[] = "✓ Creado .htaccess de protección";
        echo "   ✓ .htaccess creado\n";
    } else {
        $warnings[] = "⚠ No se pudo crear .htaccess";
        echo "   ⚠ No se pudo crear .htaccess\n";
    }
} else {
    echo "   ○ .htaccess ya existe\n";
}

// 5. Probar escritura de caché
echo "\n5. Probando sistema de caché...\n";

$testCacheFile = $baseDir . '/cache/test_' . time() . '.cache';
$testData = ['test' => true, 'timestamp' => time()];

if (@file_put_contents($testCacheFile, serialize($testData))) {
    if (file_exists($testCacheFile)) {
        $readData = @unserialize(file_get_contents($testCacheFile));
        if ($readData && $readData['test'] === true) {
            $success[] = "✓ Sistema de caché funcional";
            echo "   ✓ Escritura y lectura de caché: OK\n";
            @unlink($testCacheFile);
        } else {
            $errors[] = "✗ Error al leer caché";
            echo "   ✗ ERROR: No se puede leer el caché\n";
        }
    } else {
        $errors[] = "✗ Archivo de caché no se creó";
        echo "   ✗ ERROR: Archivo no creado\n";
    }
} else {
    $errors[] = "✗ No se puede escribir en caché";
    echo "   ✗ ERROR: No se puede escribir\n";
}

// 6. Verificar extensiones PHP necesarias
echo "\n6. Verificando extensiones PHP...\n";

$requiredExtensions = [
    'pdo' => 'PDO (base de datos)',
    'pdo_mysql' => 'PDO MySQL',
    'json' => 'JSON'
];

foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "   ✓ $desc: OK\n";
    } else {
        $errors[] = "✗ Falta extensión: $ext";
        echo "   ✗ ERROR: Falta $ext\n";
    }
}

// 7. Información del sistema
echo "\n7. Información del sistema...\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "   Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   Post Max Size: " . ini_get('post_max_size') . "\n";

// Resumen final
echo "\n==============================================\n";
echo "RESUMEN DE INSTALACIÓN\n";
echo "==============================================\n\n";

if (!empty($success)) {
    echo "✓ ÉXITOS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠ ADVERTENCIAS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "✗ ERRORES (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

// Estado final
if (empty($errors)) {
    if (empty($warnings)) {
        echo "🎉 INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
        echo "\nPróximos pasos:\n";
        echo "1. Ejecutar database_optimization_indexes.sql en tu base de datos\n";
        echo "2. Probar el dashboard en tu navegador\n";
        echo "3. Verificar que el caché funcione (segunda carga debe ser instantánea)\n";
    } else {
        echo "⚠ INSTALACIÓN COMPLETADA CON ADVERTENCIAS\n";
        echo "\nRevisar las advertencias arriba y corregirlas si es necesario.\n";
    }
} else {
    echo "✗ INSTALACIÓN COMPLETADA CON ERRORES\n";
    echo "\nCorregir los errores arriba antes de continuar.\n";
}

echo "\n==============================================\n";
?>
