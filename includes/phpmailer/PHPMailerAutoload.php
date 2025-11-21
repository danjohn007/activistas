<?php
/**
 * PHPMailer Autoloader for version 6.x
 */

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

// Crear alias para compatibilidad con código que usa PHPMailer sin namespace
if (!class_exists('PHPMailer')) {
    class_alias('PHPMailer\PHPMailer\PHPMailer', 'PHPMailer');
}
if (!class_exists('phpmailerException')) {
    class_alias('PHPMailer\PHPMailer\Exception', 'phpmailerException');
}
