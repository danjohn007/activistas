<?php
/**
 * Instalador de PHPMailer
 * Este script descarga e instala PHPMailer automáticamente
 * ELIMINAR después de usar
 */

echo "<h1>Instalador de PHPMailer</h1>";
echo "<hr>";

$installDir = __DIR__ . '/includes/phpmailer';
$zipUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip';
$zipFile = __DIR__ . '/phpmailer.zip';

// Verificar si ya está instalado
if (file_exists($installDir . '/src/PHPMailer.php')) {
    echo "<p style='color: green;'>✓ PHPMailer ya está instalado en: $installDir</p>";
    echo "<p><a href='test_email.php'>Probar envío de correo</a></p>";
    exit;
}

echo "<p>📦 Descargando PHPMailer v6.9.1...</p>";

// Descargar el archivo ZIP
$zipContent = @file_get_contents($zipUrl);
if ($zipContent === false) {
    die("<p style='color: red;'>❌ Error: No se pudo descargar PHPMailer. Verifica tu conexión a Internet.</p>");
}

file_put_contents($zipFile, $zipContent);
echo "<p style='color: green;'>✓ PHPMailer descargado exitosamente</p>";

// Verificar si la extensión ZIP está disponible
if (!class_exists('ZipArchive')) {
    echo "<p style='color: orange;'>⚠️ ZipArchive no disponible. Instalación manual requerida:</p>";
    echo "<ol>";
    echo "<li>Descarga: <a href='$zipUrl' target='_blank'>$zipUrl</a></li>";
    echo "<li>Extrae el archivo ZIP</li>";
    echo "<li>Sube la carpeta PHPMailer-6.9.1 a: <code>$installDir</code></li>";
    echo "<li>Renombra PHPMailer-6.9.1 a 'phpmailer'</li>";
    echo "</ol>";
    unlink($zipFile);
    exit;
}

// Extraer el ZIP
echo "<p>📂 Extrayendo archivos...</p>";
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    // Crear directorio si no existe
    if (!is_dir($installDir)) {
        mkdir($installDir, 0755, true);
    }
    
    // Extraer a directorio temporal
    $tempDir = __DIR__ . '/phpmailer_temp';
    $zip->extractTo($tempDir);
    $zip->close();
    
    // Mover archivos a la ubicación correcta
    $extractedDir = $tempDir . '/PHPMailer-6.9.1';
    if (is_dir($extractedDir)) {
        // Mover src/
        if (is_dir($extractedDir . '/src')) {
            rename($extractedDir . '/src', $installDir . '/src');
        }
        
        // Crear archivo de autoload personalizado
        $autoloadContent = "<?php
// PHPMailer Autoloader
require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

// Alias para compatibilidad con versión antigua
if (!class_exists('PHPMailer')) {
    class_alias('PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
    class_alias('PHPMailer\\PHPMailer\\Exception', 'phpmailerException');
}
";
        file_put_contents($installDir . '/PHPMailerAutoload.php', $autoloadContent);
        
        // Limpiar
        deleteDirectory($tempDir);
        unlink($zipFile);
        
        echo "<p style='color: green;'>✓ PHPMailer instalado exitosamente en: $installDir</p>";
        echo "<p style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Archivos instalados:</strong><br>";
        echo "- includes/phpmailer/src/PHPMailer.php<br>";
        echo "- includes/phpmailer/src/SMTP.php<br>";
        echo "- includes/phpmailer/src/Exception.php<br>";
        echo "- includes/phpmailer/PHPMailerAutoload.php<br>";
        echo "</p>";
        echo "<p><a href='test_email.php'>🧪 Probar envío de correo con PHPMailer</a></p>";
        echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (install_phpmailer.php) después de usarlo por seguridad.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al extraer archivos</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error al abrir el archivo ZIP</p>";
}

// Función auxiliar para eliminar directorios recursivamente
function deleteDirectory($dir) {
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
?>
