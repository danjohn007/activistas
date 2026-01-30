<?php
/**
 * Script de diagnóstico para problemas de carga de archivos
 * Ejecutar este script para verificar configuración y permisos
 */

echo "=== DIAGNÓSTICO DE CARGA DE ARCHIVOS ===\n\n";

// 1. Verificar configuración PHP
echo "1. CONFIGURACIÓN PHP:\n";
echo "   - upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   - post_max_size: " . ini_get('post_max_size') . "\n";
echo "   - max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "   - max_execution_time: " . ini_get('max_execution_time') . " segundos\n";
echo "   - memory_limit: " . ini_get('memory_limit') . "\n";
echo "   - file_uploads: " . (ini_get('file_uploads') ? 'Habilitado' : 'Deshabilitado') . "\n\n";

// 2. Verificar directorios
require_once __DIR__ . '/config/app.php';

echo "2. DIRECTORIOS DE SUBIDA:\n";
$uploadDir = PUBLIC_ROOT . '/assets/uploads';
$evidenciasDir = $uploadDir . '/evidencias';

echo "   - Directorio base: $uploadDir\n";
echo "     * Existe: " . (is_dir($uploadDir) ? 'SÍ' : 'NO') . "\n";
if (is_dir($uploadDir)) {
    echo "     * Escribible: " . (is_writable($uploadDir) ? 'SÍ' : 'NO') . "\n";
    echo "     * Permisos: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
}

echo "\n   - Directorio evidencias: $evidenciasDir\n";
echo "     * Existe: " . (is_dir($evidenciasDir) ? 'SÍ' : 'NO') . "\n";
if (is_dir($evidenciasDir)) {
    echo "     * Escribible: " . (is_writable($evidenciasDir) ? 'SÍ' : 'NO') . "\n";
    echo "     * Permisos: " . substr(sprintf('%o', fileperms($evidenciasDir)), -4) . "\n";
} else {
    echo "     * NOTA: El directorio no existe, se intentará crear automáticamente al subir archivos\n";
}

// 3. Verificar tmp directory
echo "\n3. DIRECTORIO TEMPORAL:\n";
$tmpDir = sys_get_temp_dir();
echo "   - Ubicación: $tmpDir\n";
echo "   - Escribible: " . (is_writable($tmpDir) ? 'SÍ' : 'NO') . "\n";
echo "   - upload_tmp_dir (PHP): " . (ini_get('upload_tmp_dir') ?: 'Valor predeterminado del sistema') . "\n";

// 4. Simular $_FILES
echo "\n4. PRUEBA DE CARGA:\n";
echo "   El formulario debe incluir:\n";
echo "   - enctype=\"multipart/form-data\"\n";
echo "   - Campo: <input type=\"file\" name=\"archivo[]\" multiple>\n";
echo "   - Envío por POST\n\n";

// 5. Códigos de error comunes
echo "5. CÓDIGOS DE ERROR $_FILES:\n";
echo "   - UPLOAD_ERR_OK (0): Sin errores\n";
echo "   - UPLOAD_ERR_INI_SIZE (1): El archivo excede upload_max_filesize\n";
echo "   - UPLOAD_ERR_FORM_SIZE (2): El archivo excede MAX_FILE_SIZE del formulario\n";
echo "   - UPLOAD_ERR_PARTIAL (3): El archivo se subió parcialmente\n";
echo "   - UPLOAD_ERR_NO_FILE (4): No se subió ningún archivo\n";
echo "   - UPLOAD_ERR_NO_TMP_DIR (6): Falta el directorio temporal\n";
echo "   - UPLOAD_ERR_CANT_WRITE (7): No se puede escribir en disco\n";
echo "   - UPLOAD_ERR_EXTENSION (8): Extensión PHP detuvo la carga\n\n";

// 6. Intentar crear directorio si no existe
echo "6. CORRECCIÓN AUTOMÁTICA:\n";
if (!is_dir($evidenciasDir)) {
    echo "   Intentando crear directorio de evidencias...\n";
    if (@mkdir($evidenciasDir, 0755, true)) {
        echo "   ✓ Directorio creado exitosamente: $evidenciasDir\n";
        echo "   ✓ Permisos establecidos: 0755\n";
    } else {
        echo "   ✗ ERROR: No se pudo crear el directorio\n";
        echo "   Solución: Crear manualmente con permisos 0755 o 0777\n";
    }
} else {
    echo "   ✓ Directorio ya existe\n";
    
    // Verificar permisos
    if (!is_writable($evidenciasDir)) {
        echo "   ⚠ ADVERTENCIA: El directorio no es escribible\n";
        echo "   Solución: chmod 0755 $evidenciasDir (o 0777 si persiste el error)\n";
    } else {
        echo "   ✓ Directorio escribible\n";
    }
}

// 7. Recomendaciones
echo "\n7. RECOMENDACIONES:\n";

$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

if (intval($uploadMaxSize) < 20) {
    echo "   ⚠ Aumentar upload_max_filesize a 20M o más en php.ini\n";
}

if (intval($postMaxSize) < 25) {
    echo "   ⚠ Aumentar post_max_size a 25M o más en php.ini\n";
}

if (!ini_get('file_uploads')) {
    echo "   ✗ CRÍTICO: file_uploads está deshabilitado en php.ini\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
