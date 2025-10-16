# Implementación de Requisito: Evidencia Obligatoria para Completar Tareas

## Objetivo
Implementar la restricción que impide completar una tarea sin subir evidencias (foto/archivo). El sistema ahora valida en múltiples niveles que no se pueda marcar una tarea como completada sin evidencia fotográfica o archivo multimedia.

## Cambios Implementados

### 1. Validación en el Controlador (taskController.php)

**Ubicación**: `/controllers/taskController.php` - Método `completeTask()`

**Cambios**:
- Validación mejorada para verificar que se hayan subido archivos antes de procesar
- Validación adicional para asegurar que al menos un archivo fue seleccionado correctamente
- Manejo mejorado de errores de carga de archivos (UPLOAD_ERR_*)
- Validación final que asegura que al menos un archivo fue procesado exitosamente
- Mensajes de error claros que indican "No se puede completar la tarea sin evidencia"

**Código clave**:
```php
// REQUISITO CRÍTICO: Validar que hay archivos obligatorios (foto/evidencia)
// Una tarea NO puede completarse sin subir al menos un archivo de evidencia
if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo']['name']) || empty($_FILES['archivo']['name'][0])) {
    redirectWithMessage('tasks/complete.php?id=' . $taskId, 
        'No se puede completar la tarea: Debe subir al menos una foto/archivo como evidencia (obligatorio)', 'error');
}

// Validar que al menos un archivo fue seleccionado correctamente
$hasValidFile = false;
for ($i = 0; $i < count($_FILES['archivo']['name']); $i++) {
    if (!empty($_FILES['archivo']['name'][$i]) && $_FILES['archivo']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
        $hasValidFile = true;
        break;
    }
}

if (!$hasValidFile) {
    redirectWithMessage('tasks/complete.php?id=' . $taskId, 
        'No se puede completar la tarea: Debe subir al menos una foto/archivo como evidencia (obligatorio)', 'error');
}

// ... procesamiento de archivos ...

// VALIDACIÓN FINAL: Asegurar que al menos un archivo fue procesado exitosamente
if (empty($uploadedFiles)) {
    redirectWithMessage('tasks/complete.php?id=' . $taskId, 
        'No se puede completar la tarea: No se pudo procesar ningún archivo de evidencia. Debe subir al menos una foto/archivo.', 'error');
}
```

### 2. Validación en el Modelo (activity.php)

**Ubicación**: `/models/activity.php` - Método `addEvidence()`

**Cambios**:
- Validación a nivel de modelo que previene marcar una tarea como completada sin archivo
- Solo permite evidencia sin archivo para archivos iniciales (blocked=0)
- Para evidencia de completado (blocked=1), el archivo es OBLIGATORIO
- Logging de intentos de completar tareas sin evidencia

**Código clave**:
```php
// REQUISITO CRÍTICO: Para completar una tarea (blocked=1), DEBE haber un archivo
// No se permite marcar como completada una tarea sin evidencia fotográfica/archivo
if ($blocked == 1 && empty($file)) {
    logActivity("Intento de completar tarea $activityId sin archivo de evidencia - RECHAZADO", 'WARNING');
    return ['success' => false, 'error' => 'No se puede completar la tarea sin subir un archivo de evidencia (foto/video/audio)'];
}
```

### 3. Mejoras en la Interfaz de Usuario (complete.php)

**Ubicación**: `/views/tasks/complete.php`

**Cambios**:
1. **Advertencia mejorada**: Se enfatiza que subir evidencia es OBLIGATORIO
2. **Label del campo de archivo**: Ahora dice "OBLIGATORIO" en mayúsculas con texto en rojo
3. **Texto de ayuda mejorado**: Indica claramente que es obligatorio subir al menos una foto
4. **Validación JavaScript mejorada**: Previene el envío del formulario si no se ha seleccionado ningún archivo

**Cambios específicos**:

**Advertencia**:
```html
<div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>¡Importante!</strong> Para completar esta tarea es <strong>OBLIGATORIO subir al menos una foto o archivo como evidencia</strong>. 
    Una vez que subas la evidencia, esta tarea se marcará como completada automáticamente y <strong>no podrás modificar la evidencia</strong>. 
    Se registrará la hora exacta de finalización para el cálculo del ranking.
</div>
```

**Campo de archivo**:
```html
<label for="archivo" class="form-label">
    <i class="fas fa-file me-1"></i>Archivos de Evidencia (OBLIGATORIO) *
</label>
<input type="file" class="form-control" id="archivo" name="archivo[]" 
       accept="image/*,video/*,audio/*" required multiple>
<div class="form-text">
    <strong class="text-danger">OBLIGATORIO:</strong> Debes subir al menos una foto o archivo para completar la tarea.<br>
    Máximo 20MB por archivo. Formatos: JPG, PNG, GIF, MP4, MP3, WAV<br>
    Puedes seleccionar múltiples archivos manteniendo Ctrl (Windows) o Cmd (Mac) mientras haces clic.
</div>
```

**Validación JavaScript**:
```javascript
document.querySelector('form').addEventListener('submit', function(e) {
    const files = document.getElementById('archivo').files;
    
    // VALIDACIÓN CRÍTICA: Asegurar que se haya seleccionado al menos un archivo
    if (!files || files.length === 0) {
        alert('ERROR: No se puede completar la tarea sin subir evidencia.\n\nDebe seleccionar al menos una foto o archivo antes de continuar.');
        e.preventDefault();
        return;
    }
    // ... validaciones adicionales ...
});
```

## Flujo de Validación

El sistema ahora implementa **tres niveles de validación** para garantizar que no se puedan completar tareas sin evidencia:

1. **Nivel 1 - Cliente (JavaScript)**: 
   - Previene el envío del formulario si no hay archivos seleccionados
   - Proporciona retroalimentación inmediata al usuario
   - Valida tamaño de archivos antes de enviar

2. **Nivel 2 - Controlador (PHP)**: 
   - Valida que $_FILES contenga archivos válidos
   - Verifica que al menos un archivo fue cargado sin errores
   - Valida que al menos un archivo fue procesado exitosamente
   - Rechaza la solicitud con mensaje claro si falta evidencia

3. **Nivel 3 - Modelo (PHP)**:
   - Última línea de defensa
   - Previene la inserción de evidencia de completado (blocked=1) sin archivo
   - Registra intentos de bypass en los logs
   - Solo permite evidencia sin archivo para adjuntos iniciales (blocked=0)

## Escenarios Cubiertos

### ✅ Escenario 1: Usuario intenta enviar formulario sin seleccionar archivo
- **Resultado**: JavaScript previene el envío y muestra alerta
- **Mensaje**: "ERROR: No se puede completar la tarea sin subir evidencia..."

### ✅ Escenario 2: Usuario intenta bypass de JavaScript (petición directa)
- **Resultado**: Controlador rechaza la petición
- **Mensaje**: "No se puede completar la tarea: Debe subir al menos una foto/archivo..."

### ✅ Escenario 3: Archivo seleccionado pero no se carga correctamente
- **Resultado**: Controlador detecta error de carga y rechaza
- **Mensaje**: Error específico según el tipo de fallo (tamaño, parcial, etc.)

### ✅ Escenario 4: Intento de llamar directamente al modelo sin archivo
- **Resultado**: Modelo rechaza la operación
- **Mensaje**: "No se puede completar la tarea sin subir un archivo de evidencia..."
- **Logging**: Se registra el intento como WARNING

### ✅ Escenario 5: Usuario sube archivo válido
- **Resultado**: Tarea se completa exitosamente
- **Mensaje**: "Tarea completada exitosamente con N archivo(s) de evidencia..."

## Tipos de Archivos Permitidos

El sistema acepta los siguientes tipos de evidencia:
- **Fotos**: JPG, PNG, GIF
- **Videos**: MP4
- **Audio**: MP3, WAV

Límite de tamaño: **20MB por archivo**

## Compatibilidad con Funcionalidad Existente

✅ Los cambios son compatibles con:
- Subida de múltiples archivos (ya implementado)
- Sistema de evidencias bloqueadas (blocked=1 vs blocked=0)
- Archivos iniciales adjuntos a tareas (blocked=0, no requieren archivo obligatorio)
- Sistema de ranking y puntos
- Logs de actividad

## Testing

Se creó un script de prueba (`/tmp/test_evidence_validation.php`) que valida:
- ✅ Rechazo de arrays vacíos
- ✅ Rechazo de nombres de archivo vacíos
- ✅ Aceptación de archivos válidos
- ✅ Detección de archivos válidos en arrays mixtos
- ✅ Rechazo de archivos con errores
- ✅ Validación de modelo (blocked=1 requiere archivo)

**Resultado**: Todos los tests pasaron exitosamente

## Verificación de Sintaxis

✅ `/controllers/taskController.php` - Sin errores de sintaxis
✅ `/models/activity.php` - Sin errores de sintaxis  
✅ `/views/tasks/complete.php` - Sin errores de sintaxis

## Archivos Modificados

1. `/controllers/taskController.php`
2. `/models/activity.php`
3. `/views/tasks/complete.php`

## Conclusión

La implementación cumple completamente con el requisito especificado:
> "Que se complete la tarea solo si suben evidencias (foto) si el usuario no sube la evidencia a la tarea no se pueda marcar como completada."

El sistema ahora **garantiza en múltiples niveles** que:
- ✅ Una tarea NO puede completarse sin subir evidencia
- ✅ Al menos un archivo debe ser cargado exitosamente
- ✅ La validación es robusta y no puede ser fácilmente evadida
- ✅ Los usuarios reciben mensajes claros sobre el requisito
- ✅ Se mantiene la compatibilidad con funcionalidad existente
