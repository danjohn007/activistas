# Instrucciones para instalar PHPMailer

Para que funcione el sistema de recuperación de contraseña, necesitas instalar PHPMailer.

## Opción 1: Descargar manualmente (Recomendado)

1. Descarga PHPMailer desde: https://github.com/PHPMailer/PHPMailer/archive/v5.2.28.zip
2. Extrae el contenido
3. Copia la carpeta a: `includes/phpmailer/`
4. Asegúrate de que existan estos archivos:
   - includes/phpmailer/PHPMailerAutoload.php
   - includes/phpmailer/class.phpmailer.php
   - includes/phpmailer/class.smtp.php

## Opción 2: Usando Composer (si está disponible)

```bash
composer require phpmailer/phpmailer
```

## Configuración SMTP utilizada:

- **Host:** ejercitodigital.com.mx
- **Username:** resetpassword@ejercitodigital.com.mx
- **Password:** Danjohn007
- **Puerto:** 465 (SSL)

## Probar el sistema

1. Ve a: http://tudominio.com/forgot-password.php
2. Ingresa un correo registrado
3. Revisa tu bandeja de entrada (y spam) para el correo de recuperación

## Alternativa temporal

Si no puedes instalar PHPMailer inmediatamente, el código usa la función mail() de PHP como respaldo.
Sin embargo, PHPMailer es más confiable y tiene mejor compatibilidad con servidores SMTP.
