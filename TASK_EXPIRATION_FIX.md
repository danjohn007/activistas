# Corrección: Validación de Tareas Vencidas

## Problema Identificado
Los activistas podían completar tareas incluso después de que la fecha de vencimiento hubiera pasado, lo cual es incorrecto.

## Solución Implementada

### 1. Validaciones en el Controlador (`controllers/taskController.php`)

#### Método `isTaskExpired()` Agregado
Se agregó un método privado que valida si una tarea está vencida basándose en:
- **Fecha de cierre** (`fecha_cierre`)
- **Hora de cierre** (`hora_cierre`) - si aplica

```php
private function isTaskExpired($task) {
    // Si no tiene fecha de cierre, nunca vence
    if (empty($task['fecha_cierre'])) {
        return false;
    }
    
    $fechaCierre = strtotime($task['fecha_cierre']);
    $fechaActual = strtotime(date('Y-m-d'));
    
    // Si la fecha de cierre ya pasó
    if ($fechaCierre < $fechaActual) {
        return true;
    }
    
    // Si es el mismo día, verificar la hora de cierre
    if ($fechaCierre == $fechaActual && !empty($task['hora_cierre'])) {
        $horaCierre = strtotime($task['hora_cierre']);
        $horaActual = strtotime(date('H:i:s'));
        
        if ($horaCierre < $horaActual) {
            return true;
        }
    }
    
    return false;
}
```

#### Validación en `showCompleteForm()`
Se agregó validación para bloquear el acceso al formulario de completar tarea:
```php
// Verificar si la tarea está vencida
if ($this->isTaskExpired($task)) {
    redirectWithMessage('tasks/', 'Esta tarea ya está vencida y no se puede completar', 'error');
}
```

#### Validación en `completeTask()`
Se agregó validación antes de procesar la evidencia:
```php
// Verificar si la tarea está vencida
if ($this->isTaskExpired($task)) {
    redirectWithMessage('tasks/', 'Esta tarea ya está vencida y no se puede completar', 'error');
}
```

### 2. Mejoras en la Vista (`views/tasks/list.php`)

#### Detección de Tareas Vencidas
Se agregó la variable `$isExpired` en el cálculo de urgencia:
```php
if ($closeDate <= $today) {
    $isUrgent = true;
    $isExpired = true;  // Nueva variable
    $urgencyClass = 'task-urgent';
    $urgencyText = 'Vencida';
}
```

#### Interfaz Condicional
Se modificó el footer de las tarjetas de tareas para mostrar:

**Tareas Vencidas:**
- ❌ **NO** se muestra el botón "Completar Tarea"
- ✅ Se muestra mensaje de alerta: "Esta tarea está vencida y no se puede completar"
- ✅ Solo se muestra el botón "Ver Detalle"

```php
<?php if ($isExpired): ?>
    <!-- Tarea vencida: solo mostrar mensaje y botón de Ver Detalle -->
    <div class="alert alert-danger mb-2">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Esta tarea está vencida</strong> y no se puede completar.
    </div>
    <div class="d-grid">
        <a href="..." class="btn btn-outline-primary">
            <i class="fas fa-eye me-1"></i>Ver Detalle
        </a>
    </div>
<?php else: ?>
    <!-- Tarea activa: mostrar ambos botones -->
    ...
<?php endif; ?>
```

**Tareas Activas:**
- ✅ Se muestran ambos botones: "Ver Detalle" y "Completar Tarea"

### 3. Modificación en el Modelo

El modelo `Activity::getPendingTasks()` fue **modificado** para MOSTRAR tareas vencidas en el listado (permitiendo visibilidad pero no completación):
```sql
WHERE a.tarea_pendiente = 1 
AND a.usuario_id = ?
AND a.usuario_id != a.solicitante_id
AND a.estado != 'completada'
-- Removido: filtro de fecha de cierre para mostrar todas las tareas
```

### 4. Validaciones en Activities (controllers/activityController.php)

#### Método `isActivityExpired()` Agregado
Similar al de taskController, valida vencimiento de actividades:
```php
private function isActivityExpired($activity) {
    if (empty($activity['fecha_cierre'])) {
        return false;
    }
    
    $fechaCierre = strtotime($activity['fecha_cierre']);
    $fechaActual = strtotime(date('Y-m-d'));
    
    if ($fechaCierre < $fechaActual) {
        return true;
    }
    
    if ($fechaCierre == $fechaActual && !empty($activity['hora_cierre'])) {
        $horaCierre = strtotime($activity['hora_cierre']);
        $horaActual = strtotime(date('H:i:s'));
        
        if ($horaCierre < $horaActual) {
            return true;
        }
    }
    
    return false;
}
```

#### Validación en `addEvidence()`
```php
// Verificar si la actividad/tarea está vencida
if ($this->isActivityExpired($activity)) {
    redirectWithMessage("activities/detail.php?id=$activityId", 
        'Esta actividad/tarea ya está vencida y no se puede agregar evidencia', 'error');
}
```

### 5. Mejoras en Vista de Detalle (views/activities/detail.php)

#### Detección de Vencimiento
```php
$isExpired = false;
if (!empty($activity['fecha_cierre'])) {
    $today = new DateTime();
    $closeDate = new DateTime($activity['fecha_cierre']);
    if (!empty($activity['hora_cierre'])) {
        $closeDate->setTime(...explode(':', $activity['hora_cierre']));
    }
    $isExpired = $closeDate <= $today;
}
```

#### Interfaz Condicional en Evidencias
**Si está vencida:**
- Badge "Vencida - No se puede agregar evidencia"
- Alerta: "Esta tarea/actividad está vencida. Ya no es posible agregar evidencia o completarla."
- NO se muestra botón de agregar evidencia

**Si NO está vencida:**
- Botón "COMPLETAR TAREA" (si es tarea pendiente)
- Botón "Agregar Evidencia" (si es actividad regular)

## Puntos de Control

### Prevención Multicapa para TAREAS (tasks/):
1. ✅ **Nivel Base de Datos**: `getPendingTasks()` MUESTRA todas las tareas (incluyendo vencidas)
2. ✅ **Nivel Vista (tasks/list.php)**: El botón "Completar Tarea" no se muestra para tareas vencidas, se muestra alerta
3. ✅ **Nivel Controlador (Formulario)**: `showCompleteForm()` valida antes de mostrar el formulario
4. ✅ **Nivel Controlador (Procesamiento)**: `completeTask()` valida antes de procesar la evidencia

### Prevención Multicapa para ACTIVIDADES (activities/):
1. ✅ **Nivel Vista (activities/detail.php)**: No se muestra botón de agregar evidencia si está vencida
2. ✅ **Nivel Controlador**: `addEvidence()` valida antes de procesar la evidencia
3. ✅ **Nivel Validación**: `isActivityExpired()` en activityController verifica fecha y hora de cierre

### Mensajes al Usuario:
- **En listado**: "Esta tarea está vencida y no se puede completar"
- **Al intentar acceder**: "Esta tarea ya está vencida y no se puede completar"

## Comportamiento Esperado

### Escenario 1: Tarea con fecha de vencimiento futura
- ✅ Aparece en el listado de tareas pendientes
- ✅ Muestra badge con "Vence en X días" / "Vence mañana" / "Vence hoy"
- ✅ Botón "Completar Tarea" visible y funcional

### Escenario 2: Tarea vencida (fecha_cierre < fecha actual)
- ✅ SÍ aparece en el listado de tareas pendientes con badge "Vencida"
- ❌ Botón "Completar Tarea" NO se muestra
- ✅ Se muestra mensaje: "Esta tarea está vencida y no se puede completar"
- ❌ Si se intenta acceder directamente por URL, redirige con mensaje de error

### Escenario 3: Tarea vencida con hora específica (mismo día, hora pasada)
- ✅ Aparece en el listado pero marcada como vencida
- ❌ No se puede completar

### Escenario 4: Tarea sin fecha de vencimiento
- ✅ Siempre aparece en tareas pendientes
- ✅ Siempre se puede completar

### Escenario 5: Actividad vencida en activities/
## Archivos Modificados

1. ✅ `controllers/taskController.php`
   - Agregado método `isTaskExpired()`
   - Validación en `showCompleteForm()`
   - Validación en `completeTask()`

2. ✅ `views/tasks/list.php`
   - Variable `$isExpired` en cálculo de urgencia
   - Interfaz condicional en card-footer
   - Mensaje de alerta para tareas vencidas

3. ✅ `models/activity.php`
   - Modificado `getPendingTasks()` para MOSTRAR tareas vencidas (removido filtro de fecha)

4. ✅ `controllers/activityController.php`
   - Agregado método `isActivityExpired()`
   - Validación en `addEvidence()`

5. ✅ `views/activities/detail.php`
   - Variable `$isExpired` para detectar actividades vencidas
   - Badge "Vencida" en lugar de botones
   - Mensaje de alerta en sección de evidencias
   - Validación en `completeTask()`

2. ✅ `views/tasks/list.php`
   - Variable `$isExpired` en cálculo de urgencia
   - Interfaz condicional en card-footer

## Pruebas Recomendadas

### Probar:
1. ✅ Crear tarea con fecha de vencimiento futura → Debe aparecer y permitir completar
2. ✅ Crear tarea con fecha de vencimiento pasada → NO debe aparecer en listado
3. ✅ Intentar acceder directamente a URL de tarea vencida → Debe redirigir con error
4. ✅ Crear tarea con fecha de vencimiento = hoy, hora futura → Debe permitir completar
5. ✅ Crear tarea con fecha de vencimiento = hoy, hora pasada → NO debe permitir completar
6. ✅ Tarea sin fecha de vencimiento → Siempre disponible

## Notas Técnicas

- La validación considera tanto la **fecha** como la **hora** de vencimiento
- Si no hay `hora_cierre`, solo se valida la fecha
- Si no hay `fecha_cierre`, la tarea nunca vence
- Las tareas vencidas quedan en la base de datos pero no son accesibles para completar
- El SuperAdmin puede ver todas las tareas (incluyendo vencidas) en otros módulos

## Fecha de Implementación
Diciembre 4, 2025
