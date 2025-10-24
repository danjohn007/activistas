# Guía de Uso - Optimización de Imágenes

## Descripción General

El sistema ahora incluye optimización automática de imágenes que reduce el tamaño de los archivos en un 40-90% sin pérdida visible de calidad. Esto funciona automáticamente sin requerir cambios en el código existente.

## ¿Cómo Funciona?

Cuando un usuario sube una imagen (foto de perfil o evidencia), el sistema:

1. **Valida** el archivo (tipo, tamaño, dimensiones)
2. **Comprime** la imagen automáticamente
3. **Redimensiona** si excede las dimensiones máximas
4. **Guarda** la versión optimizada
5. **Registra** el resultado en los logs

Todo esto ocurre de forma transparente - el usuario solo ve que su archivo se subió correctamente.

## Configuración Actual

### Límites de Tamaño
```
Evidencias: 3MB máximo
Fotos de perfil: 5MB máximo
Videos: 30MB máximo
```

### Dimensiones Máximas
```
Evidencias: 1920x1920 píxeles
Fotos de perfil: 800x800 píxeles
Validación general: 4096x4096 píxeles
```

### Calidad de Compresión
```
Fotos de perfil: 85% (alta calidad)
Evidencias pequeñas (<500KB): 85%
Evidencias medianas (500KB-2MB): 75%
Evidencias grandes (>2MB): 70%
```

## Personalizar la Configuración

### Cambiar Límites de Tamaño

Editar `includes/functions.php`, función `uploadFile()`:

```php
// Líneas 118-132
$maxSize = 3145728; // 3MB para evidencias
if ($isVideo || in_array($extension, ['mp4', 'avi', ...])) {
    $maxSize = 31457280; // 30MB para videos
} elseif ($isProfile) {
    $maxSize = 5242880; // 5MB para perfiles
}
```

**Ejemplo:** Para aumentar el límite de evidencias a 5MB:
```php
$maxSize = 5242880; // Cambiar de 3MB a 5MB
```

### Cambiar Dimensiones Máximas

Editar `includes/functions.php`, función `uploadFile()`:

```php
// Para fotos de perfil (líneas 158-161)
$maxWidth = 800;   // Cambiar según necesidad
$maxHeight = 800;  // Mantener cuadrado para perfiles

// Para evidencias (líneas 164-167)
$maxWidth = 1920;  // Cambiar a 2560 para mayor calidad
$maxHeight = 1920; // Cambiar a 2560 para mayor calidad
```

### Cambiar Calidad de Compresión

#### Opción 1: Calidad fija
Editar `includes/functions.php`:

```php
// Línea 161 (perfiles)
$quality = 85; // Aumentar a 90 para mayor calidad

// Línea 167 (evidencias)
$quality = 80; // Cambiar a valor fijo
```

#### Opción 2: Calidad dinámica
Editar `includes/image_utils.php`, función `getOptimalQuality()`:

```php
function getOptimalQuality($fileSize) {
    if ($fileSize < 512000) {
        return 90; // Era 85, aumentar para archivos pequeños
    } elseif ($fileSize < 2097152) {
        return 80; // Era 75, aumentar para archivos medianos
    } else {
        return 75; // Era 70, aumentar para archivos grandes
    }
}
```

## Ejemplos de Uso

### Subir Foto de Perfil

```php
// En tu controlador (ya funciona así)
$result = uploadFile(
    $_FILES['foto_perfil'],
    $uploadDir,
    ['jpg', 'jpeg', 'png', 'gif'],
    true,  // Indica que es foto de perfil
    false
);

// El resultado incluye:
// - Compresión automática a 800x800px
// - Calidad 85%
// - Validación de tamaño (max 5MB)
```

### Subir Evidencia

```php
// En tu controlador (ya funciona así)
$result = uploadFile(
    $_FILES['evidencia'],
    $uploadDir,
    ['jpg', 'jpeg', 'png', 'gif'],
    false, // No es foto de perfil
    false  // No es video
);

// El resultado incluye:
// - Compresión automática a max 1920x1920px
// - Calidad dinámica (70-85%)
// - Validación de tamaño (max 3MB)
```

### Subir Video

```php
// En tu controlador (ya funciona así)
$result = uploadFile(
    $_FILES['video'],
    $uploadDir,
    ['mp4', 'avi', 'mov'],
    false, // No es foto de perfil
    true   // Indica que es video
);

// Los videos NO se comprimen
// Solo se valida el tamaño (max 30MB)
```

## Monitoreo

### Ver Logs de Compresión

Los resultados se registran en `logs/system.log`:

```bash
tail -f logs/system.log | grep "Image compressed"
```

Ejemplo de salida:
```
[2024-10-24 10:30:45] [INFO] Image compressed: 67abc123.jpg - Original: 2.5 MB, Compressed: 450 KB, Savings: 82%
```

### Estadísticas de Compresión

Para obtener estadísticas, puedes usar:

```bash
# Contar imágenes comprimidas hoy
grep "$(date +%Y-%m-%d)" logs/system.log | grep "Image compressed" | wc -l

# Ver ahorros promedio
grep "Savings:" logs/system.log | awk '{print $NF}' | sed 's/%//' | awk '{s+=$1} END {print s/NR "%"}'
```

## Solución de Problemas

### Problema: "El archivo es demasiado grande"

**Causa:** El archivo excede el límite configurado.

**Solución:** 
1. Pedirle al usuario que reduzca el tamaño
2. O aumentar el límite en `uploadFile()` (ver arriba)

### Problema: "Error al comprimir imagen"

**Causa:** El archivo no es una imagen válida o está corrupto.

**Solución:**
1. Verificar que el archivo sea una imagen real
2. Revisar el log: `tail logs/system.log`
3. El sistema guardará el original si la compresión falla

### Problema: Imágenes se ven borrosas

**Causa:** La calidad de compresión es demasiado baja.

**Solución:**
1. Aumentar el valor de `$quality` en `uploadFile()`
2. Aumentar las dimensiones máximas si están muy reducidas
3. Ver sección "Cambiar Calidad de Compresión"

### Problema: Archivos siguen siendo grandes

**Causa:** Las dimensiones máximas son demasiado altas.

**Solución:**
1. Reducir `$maxWidth` y `$maxHeight`
2. Reducir el valor de `$quality`
3. Verificar que la compresión esté activa (revisar logs)

## Verificar que Funciona

### Test Rápido

```php
// Crear archivo de prueba: test_compression.php
<?php
require_once 'includes/image_utils.php';

$testImage = '/tmp/test.jpg';
// Crear imagen de prueba 2000x1500
$img = imagecreatetruecolor(2000, 1500);
$blue = imagecolorallocate($img, 0, 0, 255);
imagefill($img, 0, 0, $blue);
imagejpeg($img, $testImage, 90);
imagedestroy($img);

$original = filesize($testImage);
echo "Original: " . formatFileSize($original) . "\n";

$result = compressImage($testImage, '/tmp/test_compressed.jpg', 1920, 1920, 80);
echo "Comprimido: " . formatFileSize($result['compressed_size']) . "\n";
echo "Ahorro: " . $result['savings'] . "%\n";
?>
```

Ejecutar:
```bash
php test_compression.php
```

Resultado esperado:
```
Original: ~200 KB
Comprimido: ~60 KB
Ahorro: 70%
```

## Recomendaciones

### Para Producción

1. **Monitorear logs** regularmente para detectar problemas
2. **Ajustar calidad** según feedback de usuarios
3. **Revisar espacio en disco** - debería reducirse significativamente
4. **Medir rendimiento** del servidor - debería mejorar

### Para Desarrollo

1. **Probar con diferentes tipos de imágenes** (PNG, JPEG, GIF)
2. **Probar con diferentes tamaños** (pequeñas, grandes, muy grandes)
3. **Verificar transparencia** en PNG se preserve
4. **Revisar logs** para confirmar que funciona

## Mantenimiento

### Logs

Los logs pueden crecer con el tiempo. Rotar periódicamente:

```bash
# Crear backup y limpiar
mv logs/system.log logs/system.log.$(date +%Y%m%d)
touch logs/system.log
```

### Espacio en Disco

Monitorear el uso de disco en directorios de uploads:

```bash
du -sh public/assets/uploads/*
```

## Preguntas Frecuentes

**P: ¿Afecta la calidad visual de las imágenes?**  
R: La reducción es imperceptible para el ojo humano. Usamos compresión con pérdida mínima.

**P: ¿Funciona con todos los navegadores?**  
R: Sí, la compresión ocurre en el servidor, no en el navegador.

**P: ¿Puedo deshabilitar la compresión?**  
R: Sí, pero no se recomienda. Para deshabilitar, comentar las líneas de compresión en `uploadFile()`.

**P: ¿Los videos también se comprimen?**  
R: No, solo imágenes (JPEG, PNG, GIF, WebP). Los videos se validan pero no se comprimen.

**P: ¿Qué pasa con las imágenes ya subidas?**  
R: Las imágenes existentes no se modifican. Solo las nuevas se comprimen.

## Soporte

Para problemas o preguntas:
1. Revisar logs: `logs/system.log`
2. Verificar configuración en `includes/functions.php`
3. Ejecutar test de compresión (ver arriba)
4. Consultar documentación técnica: `IMAGE_OPTIMIZATION_DOCUMENTATION.md`

---

**Última actualización:** Octubre 2024  
**Versión:** 1.0
