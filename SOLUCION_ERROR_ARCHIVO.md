# 🚨 Solución: Error "No se encontró tu archivo" al subir actividades/tareas

## 📋 Problema
Cuando los usuarios intentan subir una actividad/tarea, reciben el error: **"No se encontró tu archivo"**

## 🔍 Causas Identificadas

El error puede deberse a múltiples causas:

### 1. **Problemas de Configuración PHP** ⚙️
- `upload_max_filesize` muy bajo (menor a 20MB)
- `post_max_size` insuficiente (debe ser mayor que upload_max_filesize)
- `file_uploads` deshabilitado
- Límite de archivos simultáneos bajo

### 2. **Problemas de Permisos** 🔐
- Directorio `/public/assets/uploads/evidencias/` no existe
- Directorio sin permisos de escritura
- Directorio temporal sin acceso

### 3. **Problemas de Red/Cliente** 🌐
- Archivo demasiado grande
- Conexión interrumpida durante la carga
- Timeout del servidor
- Formulario enviado sin archivos

### 4. **Problemas del Navegador** 💻
- JavaScript deshabilitado (validación HTML5 no funciona)
- Cache del navegador
- Extensiones que bloquean la carga

---

## 🛠️ Soluciones Implementadas

### ✅ 1. Mejoras en el Código

#### A) Validación Detallada con Mensajes Específicos
Se mejoró el archivo `controllers/taskController.php` para:
- Detectar exactamente qué parte del proceso falla
- Mostrar mensajes de error específicos con códigos
- Registrar logs detallados para diagnóstico
- Validar estructura de `$_FILES` paso a paso

**Códigos de error agregados:**
- `NO_FILES`: No se encontró $_FILES['archivo']
- `INVALID_FORMAT`: El formato del array es incorrecto
- `EMPTY_FILE`: No se proporcionó ningún archivo
- `FILE_TOO_LARGE`: Archivo excede el límite
- `NO_VALID_FILES`: Ningún archivo pasó la validación

#### B) Creación Automática de Directorios
El sistema ahora:
- Crea automáticamente el directorio de evidencias si no existe
- Intenta corregir permisos automáticamente
- Registra cada paso en logs para diagnóstico

#### C) Mensajes de Error Informativos
Los usuarios ahora ven mensajes como:
```
ERROR: No se encontró tu archivo. Verifica que:
1) Seleccionaste un archivo
2) El archivo no es muy grande (máx 20MB)
3) Tu conexión a internet es estable
Código: NO_FILES
```

---

## 🔧 Soluciones para el Administrador

### Paso 1: Diagnóstico Inicial

#### Opción A: Usar el Script de Test (Recomendado)
1. Accede a: `https://tudominio.com/sistema/public/test_upload.php`
2. Revisa los resultados:
   - ✓ Verde = OK
   - ⚠ Naranja = Advertencia
   - ✗ Rojo = Error crítico
3. Usa el formulario de prueba para verificar carga real

#### Opción B: Usar el Script de Línea de Comandos
```bash
cd /ruta/al/proyecto
php debug_upload.php
```

### Paso 2: Corregir Permisos de Directorios

#### En Linux/Mac:
```bash
# Opción 1: Permisos estándar (recomendado)
chmod -R 0755 public/assets/uploads
chown -R www-data:www-data public/assets/uploads

# Opción 2: Si persiste el error, permisos completos
chmod -R 0777 public/assets/uploads
```

#### En Windows:
1. Click derecho en la carpeta `public/assets/uploads`
2. Propiedades → Seguridad
3. Agregar permisos de escritura para el usuario del servidor web (IIS_IUSRS o IUSR)

#### Verificar:
```bash
ls -la public/assets/uploads
# Debe mostrar: drwxr-xr-x (755) o drwxrwxrwx (777)
```

### Paso 3: Configurar PHP

#### Ubicar php.ini:
```bash
php --ini
# O buscar en: /etc/php/8.x/apache2/php.ini
```

#### Modificar configuración:
```ini
; Habilitar carga de archivos
file_uploads = On

; Tamaño máximo de archivo individual
upload_max_filesize = 20M

; Tamaño máximo de POST (debe ser mayor que upload_max_filesize)
post_max_size = 25M

; Número máximo de archivos simultáneos
max_file_uploads = 20

; Tiempo máximo de ejecución (para archivos grandes)
max_execution_time = 300

; Memoria máxima
memory_limit = 256M

; Directorio temporal (opcional, usar solo si hay problemas)
; upload_tmp_dir = /tmp
```

#### Reiniciar servidor web:
```bash
# Apache
sudo systemctl restart apache2
# o
sudo service apache2 restart

# Nginx + PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

### Paso 4: Verificar Logs del Servidor

#### Ver logs de PHP:
```bash
# Logs de Apache
tail -f /var/log/apache2/error.log

# Logs de Nginx
tail -f /var/log/nginx/error.log

# Logs de PHP-FPM
tail -f /var/log/php8.1-fpm.log
```

Buscar mensajes como:
- `ERROR CARGA:`
- `CRÍTICO:`
- `No se pudo crear el directorio`
- `El directorio no es escribible`

---

## 📝 Guía para el Usuario

### Antes de Subir un Archivo:

1. **Verifica el tamaño del archivo**
   - Máximo: 20MB por archivo
   - Si es muy grande, comprímelo o reduce la calidad

2. **Formatos aceptados**
   - Imágenes: JPG, PNG, GIF
   - Videos: MP4
   - Audio: MP3, WAV

3. **Conexión estable**
   - Usa WiFi estable o datos móviles
   - Evita cargar en conexiones lentas

4. **Navegador actualizado**
   - Chrome, Firefox, Safari, Edge (última versión)
   - Habilita JavaScript

### Durante la Carga:

1. **Selecciona al menos un archivo** (obligatorio)
2. **No cierres la ventana** durante la carga
3. **Espera el mensaje de confirmación** antes de salir

### Si Ves un Error:

#### Error: "No se encontró tu archivo" (Código: NO_FILES)
**Solución:**
- Verifica que seleccionaste un archivo antes de hacer clic en "Completar Tarea"
- Intenta seleccionar el archivo nuevamente
- Prueba con un archivo más pequeño
- Recarga la página y vuelve a intentar

#### Error: "Archivo demasiado grande" (Código: FILE_TOO_LARGE)
**Solución:**
- Reduce el tamaño del archivo
- Para fotos: reduce la calidad o resolución
- Para videos: comprime el video o reduce la duración
- Máximo permitido: ver el mensaje de error

#### Error: "Se subió parcialmente"
**Solución:**
- Tu conexión se interrumpió
- Verifica tu conexión a internet
- Intenta nuevamente con mejor señal

#### Error general sin código
**Solución:**
1. Cierra el navegador completamente
2. Limpia el cache del navegador:
   - Chrome: Ctrl+Shift+Supr → Borrar cache
   - Firefox: Ctrl+Shift+Supr → Borrar cache
3. Vuelve a iniciar sesión
4. Intenta subir el archivo nuevamente

---

## 🧪 Testing

### Verificar que Todo Funciona:

1. **Acceder a test_upload.php:**
   ```
   https://tudominio.com/sistema/public/test_upload.php
   ```

2. **Revisar cada sección:**
   - ✅ Todas las secciones deben estar en verde
   - ⚠️ Advertencias naranjas son opcionales pero recomendadas
   - ❌ Errores rojos deben corregirse

3. **Hacer prueba real:**
   - Usar el formulario de prueba en test_upload.php
   - Subir un archivo pequeño (< 5MB)
   - Debe mostrar "✓✓ Archivo guardado exitosamente"

4. **Probar en la aplicación:**
   - Crear una tarea de prueba
   - Intentar completarla con evidencia
   - Verificar que se sube correctamente

---

## 📊 Checklist de Verificación

### Para el Administrador:
- [ ] Configuración PHP correcta (upload_max_filesize, post_max_size)
- [ ] file_uploads = On
- [ ] Directorio `/public/assets/uploads/evidencias/` existe
- [ ] Permisos 0755 o 0777 en directorios
- [ ] Directorio temporal escribible
- [ ] Servidor web reiniciado después de cambios
- [ ] test_upload.php muestra todo en verde
- [ ] Logs no muestran errores críticos

### Para el Usuario:
- [ ] Archivo menor a 20MB
- [ ] Formato permitido (JPG, PNG, GIF, MP4, MP3, WAV)
- [ ] Al menos un archivo seleccionado
- [ ] Conexión a internet estable
- [ ] JavaScript habilitado en el navegador
- [ ] Navegador actualizado

---

## 🔍 Troubleshooting Avanzado

### Problema: Archivos pequeños no se suben

**Posible causa:** Permisos del directorio temporal

**Solución:**
```bash
# Verificar directorio temporal
php -r "echo sys_get_temp_dir();"

# Dar permisos (ejemplo: /tmp)
sudo chmod 1777 /tmp
```

### Problema: Solo afecta a ciertos usuarios

**Posible causa:** Límite de cuota de disco o sesión

**Solución:**
```bash
# Verificar espacio en disco
df -h

# Limpiar archivos temporales antiguos
find /tmp -type f -mtime +7 -delete
```

### Problema: Funciona en local pero no en producción

**Posible causa:** SELinux o AppArmor bloqueando

**Solución:**
```bash
# Verificar SELinux
getenforce

# Temporalmente deshabilitar para probar
sudo setenforce 0

# Permitir escritura permanentemente
sudo chcon -R -t httpd_sys_rw_content_t public/assets/uploads
# O
sudo setsebool -P httpd_unified 1
```

### Problema: Solo afecta a archivos grandes

**Posible causa:** Timeout de PHP o servidor web

**Solución en php.ini:**
```ini
max_execution_time = 300
max_input_time = 300
```

**Solución en Apache (.htaccess o virtualhost):**
```apache
Timeout 300
```

**Solución en Nginx:**
```nginx
client_max_body_size 25M;
client_body_timeout 300s;
```

---

## 📞 Soporte

Si el problema persiste después de aplicar todas las soluciones:

1. **Recopilar información:**
   - Captura de pantalla del error
   - Resultado de test_upload.php
   - Últimas líneas del log de errores
   - Navegador y versión
   - Tamaño del archivo que intentan subir

2. **Logs a revisar:**
   ```bash
   # Últimos 50 errores de carga
   grep "ERROR CARGA" /var/log/apache2/error.log | tail -50
   
   # Errores críticos
   grep "CRÍTICO" /var/log/apache2/error.log | tail -50
   ```

3. **Información del sistema:**
   ```bash
   php -v
   php -m | grep -i upload
   ls -la public/assets/uploads/evidencias/
   ```

---

## 🎯 Resumen Rápido

### Para resolver el 90% de los casos:

```bash
# 1. Dar permisos correctos
chmod -R 0755 public/assets/uploads
chown -R www-data:www-data public/assets/uploads

# 2. Verificar php.ini
grep -E "upload_max_filesize|post_max_size|file_uploads" /etc/php/*/apache2/php.ini

# 3. Si no está configurado, editar:
sudo nano /etc/php/8.1/apache2/php.ini
# Cambiar:
# upload_max_filesize = 20M
# post_max_size = 25M
# file_uploads = On

# 4. Reiniciar Apache
sudo systemctl restart apache2

# 5. Probar
# Acceder a: https://tudominio.com/sistema/public/test_upload.php
```

---

## ✅ Cambios Implementados en el Código

### Archivos Modificados:
1. `controllers/taskController.php`
   - Validación detallada con mensajes específicos
   - Logs de diagnóstico mejorados
   - Creación automática de directorios
   - Corrección automática de permisos

### Archivos Creados:
1. `debug_upload.php` - Script de diagnóstico CLI
2. `test_upload.php` - Interfaz web de diagnóstico y pruebas
3. `SOLUCION_ERROR_ARCHIVO.md` - Este documento

---

**Fecha:** 30 de enero de 2026
**Versión:** 1.0
