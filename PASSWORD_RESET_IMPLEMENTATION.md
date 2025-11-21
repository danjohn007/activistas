# Sistema de Recuperación de Contraseña - Implementación Completa

## ✅ Archivos Creados

### Vistas
1. **views/forgot-password.php** - Formulario para solicitar recuperación
2. **views/reset-password.php** - Formulario para establecer nueva contraseña

### Controladores Públicos
3. **public/forgot-password.php** - Endpoint para solicitar recuperación
4. **public/reset-password.php** - Endpoint para restablecer contraseña

### Base de Datos
5. **database_migration_password_reset.sql** - Tabla para tokens de recuperación

### Documentación
6. **PHPMAILER_SETUP.md** - Instrucciones para instalar PHPMailer

## ✅ Archivos Modificados

### Modelos
- **models/user.php** - Agregados 5 métodos nuevos:
  - `getUserByEmail($email)` - Obtener usuario por correo
  - `createPasswordResetToken($userId, $token, $expires)` - Crear token
  - `validatePasswordResetToken($token)` - Validar token
  - `updatePassword($userId, $newPassword)` - Actualizar contraseña
  - `markTokenAsUsed($token)` - Marcar token como usado

### Controladores
- **controllers/userController.php** - Agregados 8 métodos nuevos:
  - `showForgotPassword()` - Mostrar formulario de recuperación
  - `processForgotPassword()` - Procesar solicitud de recuperación
  - `showResetPassword()` - Mostrar formulario de nueva contraseña
  - `processResetPassword()` - Procesar nueva contraseña
  - `sendPasswordResetEmail()` - Enviar correo (detecta PHPMailer o usa mail())
  - `sendEmailWithPHPMailer()` - Envío con SMTP
  - `sendEmailWithMailFunction()` - Envío con función mail()
  - `getPasswordResetEmailHTML()` - Plantilla HTML del correo
  - `getPasswordResetEmailText()` - Plantilla texto del correo

### Vistas
- **views/login.php** - Agregado enlace "¿Olvidaste tu contraseña?"

## 📋 Pasos para Completar la Instalación

### 1. Ejecutar Migración de Base de Datos

Ejecuta el archivo SQL en tu base de datos:

```bash
mysql -u usuario -p ejercito_activistas < database_migration_password_reset.sql
```

O desde phpMyAdmin, ejecuta el contenido de `database_migration_password_reset.sql`

### 2. Instalar PHPMailer (Recomendado)

**Opción A: Descarga manual**
1. Descarga: https://github.com/PHPMailer/PHPMailer/archive/v5.2.28.zip
2. Extrae y copia a: `includes/phpmailer/`
3. Verifica que existan:
   - `includes/phpmailer/PHPMailerAutoload.php`
   - `includes/phpmailer/class.phpmailer.php`
   - `includes/phpmailer/class.smtp.php`

**Opción B: Sin PHPMailer**
- El sistema funcionará usando la función `mail()` de PHP
- Menos confiable pero funcional

## 🔧 Configuración SMTP

La configuración ya está incluida en el código:

```
Host: ejercitodigital.com.mx
Usuario: resetpassword@ejercitodigital.com.mx
Contraseña: Danjohn007
Puerto: 465 (SSL)
```

## 🧪 Probar el Sistema

1. **Solicitar recuperación:**
   - Ve a: `http://tudominio.com/forgot-password.php`
   - Ingresa un correo registrado
   - Haz clic en "Enviar Enlace de Recuperación"

2. **Verificar correo:**
   - Revisa la bandeja de entrada (y spam)
   - El correo incluye un botón y un enlace

3. **Restablecer contraseña:**
   - Haz clic en el enlace del correo
   - Ingresa nueva contraseña (mínimo 8 caracteres)
   - Confirma la contraseña
   - Inicia sesión con la nueva contraseña

## 🔐 Características de Seguridad

- ✅ Token único de 64 caracteres (generado con `random_bytes()`)
- ✅ Expiración de 1 hora
- ✅ Token de un solo uso (se marca como usado)
- ✅ Tokens antiguos se eliminan al generar uno nuevo
- ✅ Validación CSRF en todos los formularios
- ✅ Contraseña hasheada con `password_hash()`
- ✅ Validación de fortaleza de contraseña en el frontend
- ✅ Mensajes genéricos (no revela si el email existe o no)

## 📊 Estructura de la Tabla

```sql
password_reset_tokens:
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK a usuarios)
- token (VARCHAR 64)
- expires_at (DATETIME)
- used (TINYINT, 0 o 1)
- created_at (TIMESTAMP)
```

## 🎨 Interfaz de Usuario

Todas las páginas tienen:
- ✅ Diseño responsive con Bootstrap 5
- ✅ Gradiente morado consistente con el login
- ✅ Iconos de Font Awesome
- ✅ Mensajes flash para feedback
- ✅ Indicador de fortaleza de contraseña
- ✅ Botón para mostrar/ocultar contraseña
- ✅ Validación en tiempo real

## 📧 Plantilla del Correo

El correo incluye:
- ✅ Diseño HTML profesional
- ✅ Botón destacado "Restablecer Contraseña"
- ✅ Enlace alternativo (para clientes que bloquean imágenes)
- ✅ Advertencia de expiración (1 hora)
- ✅ Nota de seguridad
- ✅ Versión texto plano (fallback)

## 🔄 Flujo Completo

1. Usuario hace clic en "¿Olvidaste tu contraseña?" en login
2. Ingresa su correo electrónico
3. Sistema verifica que el correo existe
4. Genera token único de 64 caracteres
5. Guarda token en BD con expiración de 1 hora
6. Envía correo con enlace de recuperación
7. Usuario hace clic en el enlace del correo
8. Sistema valida que el token sea válido y no haya expirado
9. Usuario ingresa nueva contraseña (validación de fortaleza)
10. Sistema actualiza contraseña (hasheada)
11. Marca token como usado
12. Usuario puede iniciar sesión con nueva contraseña

## ⚠️ Notas Importantes

- Los tokens expiran en **1 hora**
- Cada usuario solo puede tener **1 token activo** a la vez
- Los tokens son de **un solo uso**
- Si PHPMailer no está instalado, usa `mail()` de PHP automáticamente
- Se registran todas las acciones en los logs

## 🐛 Solución de Problemas

**No llega el correo:**
1. Verifica que el correo esté en spam
2. Verifica logs de PHP: `logActivity()` registra cada envío
3. Verifica credenciales SMTP si usas PHPMailer
4. Verifica que el servidor permita función `mail()` si no usas PHPMailer

**Token inválido o expirado:**
1. Verifica que no haya pasado 1 hora
2. Verifica que el token no se haya usado ya
3. Solicita nuevo enlace de recuperación

**Error en base de datos:**
1. Verifica que se haya ejecutado `database_migration_password_reset.sql`
2. Verifica permisos de la tabla
3. Revisa logs de PHP para error específico
