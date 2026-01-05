# Corrección: Usuarios Completando Tareas Vencidas

## Problema Identificado

Algunas personas podían completar tareas aunque ya estuvieran vencidas (pasada su fecha/hora de cierre).

## Causa Raíz

La función `isTaskExpired()` en [taskController.php](controllers/taskController.php) tenía un **bug crítico en la validación de hora**:

```php

if ($horaCierre < $horaActual) {
    return true;
}
```

### ¿Por qué fallaba?

`strtotime()` con solo una hora (sin fecha) retorna `false` o un valor incorrecto, porque necesita una fecha de referencia. Por ejemplo:
- `strtotime("14:30:00")` puede retornar valores impredecibles
- La comparación no funcionaba correctamente

### Escenarios donde fallaba:

1. **Tarea con fecha y hora**: Si una tarea vencía a las 2:00 PM, después de esa hora aún se podía completar
2. **Cambio de día**: Si la tarea vencía ayer a las 11:00 PM, hoy aún aparecía como válida

## Solución Implementada

Se reescribió completamente la función `isTaskExpired()` para usar **timestamps completos** (fecha + hora):

```php
// CÓDIGO CORRECTO (después)
private function isTaskExpired($task) {
    // Si no tiene fecha de cierre, nunca vence
    if (empty($task['fecha_cierre'])) {
        return false;
    }
    
    // Si tiene hora de cierre, validar fecha Y hora
    if (!empty($task['hora_cierre'])) {
        // ✅ Combinar fecha y hora: "2026-01-05 14:30:00"
        $fechaHoraCierre = strtotime($task['fecha_cierre'] . ' ' . $task['hora_cierre']);
        $fechaHoraActual = time(); // Timestamp actual completo
        
        // La tarea está vencida si la fecha-hora de cierre ya pasó
        return ($fechaHoraCierre < $fechaHoraActual);
    }
    
    // Si solo tiene fecha (sin hora), vence al final del día (23:59:59)
    $fechaCierre = strtotime($task['fecha_cierre'] . ' 23:59:59');
    $fechaHoraActual = time();
    
    return ($fechaCierre < $fechaHoraActual);
}
```

## Mejoras Adicionales

### 1. **Validación al Mostrar Formulario**
El método `showCompleteForm()` verifica si la tarea está vencida antes de mostrar el formulario:

```php
// Verificar si la tarea está vencida
if ($this->isTaskExpired($task)) {
    redirectWithMessage('tasks/', 'Esta tarea ya está vencida y no se puede completar', 'error');
}
```

### 2. **Validación al Procesar la Tarea**
El método `completeTask()` también verifica antes de procesar la evidencia:

```php
// Verificar si la tarea está vencida
if ($this->isTaskExpired($task)) {
    redirectWithMessage('tasks/', 'Esta tarea ya está vencida y no se puede completar', 'error');
}
```

### 3. **Filtro en la Lista de Tareas**
La función `getPendingTasks()` en [activity.php](models/activity.php) ya filtraba tareas vencidas en la consulta SQL:

```sql
AND (a.fecha_cierre IS NULL 
     OR a.fecha_cierre > CURDATE() 
     OR (a.fecha_cierre = CURDATE() 
         AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))
```

Este filtro SQL funciona bien y complementa la validación de PHP.

## Casos de Prueba

### ✅ Caso 1: Tarea con Fecha y Hora de Cierre
1. Crear una tarea con fecha de cierre: **Hoy** y hora: **14:00:00**
2. Esperar hasta las **14:01:00**
3. Intentar completar la tarea
4. **Resultado esperado**: Error "Esta tarea ya está vencida y no se puede completar"

### ✅ Caso 2: Tarea con Solo Fecha de Cierre
1. Crear una tarea con fecha de cierre: **Hoy** (sin hora)
2. A cualquier hora del día, intentar completarla
3. **Resultado esperado**: Se puede completar hasta las **23:59:59** de hoy
4. Después de la medianoche: Error "Esta tarea ya está vencida"

### ✅ Caso 3: Tarea Sin Fecha de Cierre
1. Crear una tarea sin fecha de cierre
2. En cualquier momento futuro, intentar completarla
3. **Resultado esperado**: Se puede completar en cualquier momento (nunca vence)

### ✅ Caso 4: Tarea que Venció Ayer
1. Crear una tarea con fecha de cierre: **Ayer** a las **18:00:00**
2. Hoy intentar completarla
3. **Resultado esperado**: Error "Esta tarea ya está vencida y no se puede completar"
4. La tarea **no debe aparecer** en la lista de tareas pendientes

## Protección Multinivel

El sistema ahora tiene **3 niveles de protección** contra completar tareas vencidas:

1. **Filtro en Base de Datos** ([activity.php](models/activity.php#L1007-1008))
   - Las tareas vencidas no aparecen en la lista de tareas pendientes
   - Validación con `fecha_cierre` y `hora_cierre` en SQL

2. **Validación al Mostrar Formulario** ([taskController.php](controllers/taskController.php#L57-58))
   - Si un usuario accede directamente a la URL, se verifica si está vencida
   - Redirige con mensaje de error si está vencida

3. **Validación al Procesar** ([taskController.php](controllers/taskController.php#L98-99))
   - Última verificación antes de guardar la evidencia
   - Previene completar tareas vencidas aunque alguien manipule el formulario

## Archivos Modificados

### [controllers/taskController.php](controllers/taskController.php)
- **Función `isTaskExpired()`** (líneas 237-260): Reescrita completamente
  - Ahora combina fecha + hora correctamente con `strtotime()`
  - Maneja correctamente tareas con hora y sin hora
  - Usa `time()` para obtener el timestamp actual

## Verificación en Producción

Para verificar si hay usuarios que completaron tareas vencidas recientemente:

```sql
-- Buscar actividades completadas después de su fecha de cierre
SELECT 
    a.id,
    a.titulo,
    u.nombre_completo,
    a.fecha_cierre,
    a.hora_cierre,
    a.fecha_actualizacion as fecha_completada,
    CASE 
        WHEN a.hora_cierre IS NOT NULL 
        THEN CONCAT(a.fecha_cierre, ' ', a.hora_cierre)
        ELSE CONCAT(a.fecha_cierre, ' 23:59:59')
    END as fecha_hora_limite,
    a.fecha_actualizacion
FROM actividades a
JOIN usuarios u ON a.usuario_id = u.id
WHERE a.estado = 'completada'
  AND a.tarea_pendiente = 1
  AND a.fecha_cierre IS NOT NULL
  AND (
      -- Caso 1: Tiene hora y se completó después
      (a.hora_cierre IS NOT NULL 
       AND a.fecha_actualizacion > CONCAT(a.fecha_cierre, ' ', a.hora_cierre))
      OR
      -- Caso 2: Sin hora y se completó después del final del día
      (a.hora_cierre IS NULL 
       AND a.fecha_actualizacion > CONCAT(a.fecha_cierre, ' 23:59:59'))
  )
ORDER BY a.fecha_actualizacion DESC
LIMIT 20;
```

## Resumen

✅ **Problema**: Usuarios podían completar tareas vencidas debido a bug en validación de hora  
✅ **Causa**: `strtotime()` mal usado con solo hora (sin fecha)  
✅ **Solución**: Combinar fecha + hora correctamente antes de comparar timestamps  
✅ **Protección**: Triple validación (SQL, formulario, procesamiento)  
✅ **Estado**: Corregido y listo para pruebas  

---

*Fecha de corrección: 5 de enero de 2026*
