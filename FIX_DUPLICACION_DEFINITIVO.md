# 🔧 Corrección DEFINITIVA: Duplicación de Tareas al Asignar

**Fecha:** 28 de enero de 2026  
**Estado:** ✅ CORREGIDO + VALIDACIONES ADICIONALES IMPLEMENTADAS

---

## 🐛 Problema Reportado

Al asignar tareas desde el SuperAdmin, **algunos usuarios reciben la misma tarea 2 veces**.

## 🔍 Causa Raíz Identificada

El problema ocurría porque el formulario HTML enviaba **múltiples arrays de destinatarios simultáneamente**:

### Escenario del Error:
1. Usuario selecciona la pestaña "Líderes" y marca algunos líderes ✅
2. La pestaña "Todos los Usuarios" **también tiene checkboxes pre-marcados** por defecto ⚠️
3. Al enviar el formulario, se envían **AMBOS arrays**:
   - `destinatarios_lideres[]` → Backend expande a líderes + sus activistas
   - `destinatarios_todos[]` → Contiene todos los usuarios marcados
4. Resultado: **Los mismos usuarios aparecen en ambos arrays** → Duplicación

### Por qué `array_unique()` no era suficiente:
El backend procesaba múltiples `if/elseif`, pero si por alguna razón se cumplían múltiples condiciones o había un bug en la lógica, podía haber duplicados.

---

## ✅ Soluciones Implementadas

### 1. **Frontend: Deshabilitar Pestañas Inactivas** ([create.php](views/activities/create.php))

Agregado JavaScript que **deshabilita automáticamente** los checkboxes de las pestañas inactivas antes de enviar el formulario:

```javascript
document.querySelector('form').addEventListener('submit', function(e) {
    const activeTab = document.querySelector('#assignmentTabs .nav-link.active');
    
    if (activeTab) {
        const activeTabId = activeTab.getAttribute('data-bs-target');
        
        // Deshabilitar todos los checkboxes de las pestañas inactivas
        document.querySelectorAll('.tab-pane').forEach(function(tabPane) {
            const tabId = '#' + tabPane.id;
            
            if (tabId !== activeTabId) {
                tabPane.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    checkbox.disabled = true;
                });
            }
        });
    }
});
```

**Efecto:** Solo se envían los datos de la pestaña actualmente seleccionada.

### 2. **Backend: Logging Detallado** ([activityController.php](controllers/activityController.php))

Agregados logs para diagnosticar el problema:

```php
error_log("=== CREATE ACTIVITY DEBUG ===");
error_log("User role: " . $currentUser['rol']);
error_log("destinatarios_lideres: " . json_encode($_POST['destinatarios_lideres'] ?? []));
error_log("destinatarios_grupos: " . json_encode($_POST['destinatarios_grupos'] ?? []));
error_log("destinatarios_todos: " . json_encode($_POST['destinatarios_todos'] ?? []));
error_log("Recipients after dedup: " . json_encode($recipients));
error_log("Total recipients: " . count($recipients));
```

**Efecto:** Podemos ver exactamente qué datos llegan y cuántos destinatarios finales hay.

---

### 5. **Mensajes al Usuario**

Ahora cuando se crean actividades, el usuario recibe feedback completo:

```
"Actividad creada exitosamente para 10 destinatarios (2 duplicados omitidos)"
```

Esto le informa que el sistema detectó y previno duplicados automáticamente.

---

### 6. **Script de Limpieza de Duplicados** ([remove_duplicates.sql](remove_duplicates.sql))

Creado script SQL para limpiar duplicados existentes en la base de datos.

---

## 🧪 Cómo Verificar la Corrección

### Paso 1: Probar Asignación Nueva
1. Ir a "Nueva Actividad"
2. Seleccionar la pestaña "Líderes"
3. Marcar 1-2 líderes
4. **NO cambiar a otras pestañas**
5. Crear la actividad
6. Verificar en la base de datos o en el perfil de un activista que **solo aparece 1 vez**

### Paso 2: Revisar Logs del Servidor
Después de crear una actividad, revisar el archivo de log de PHP (error_log):
```
=== CREATE ACTIVITY DEBUG ===
User role: SuperAdmin
destinatarios_lideres: [2,3]
destinatarios_grupos: []
destinatarios_todos: []
Recipients after dedup (lideres): [2,3,5,6,7]
Total recipients: 5
```

Los arrays vacíos `[]` confirman que solo se envió la pestaña activa.

### Paso 3: Verificar Duplicados Existentes
Ejecutar el query de [check_duplicates.sql](check_duplicates.sql):
```sql
SELECT 
    u.nombre_completo as nombre_usuario,
    a.titulo,
    COUNT(*) as cantidad_duplicados
FROM actividades a
LEFT JOIN usuarios u ON a.usuario_id = u.id
WHERE a.fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad
HAVING COUNT(*) > 1;
```

Si muestra resultados, son duplicados antiguos que se pueden limpiar.

---

## 🔧 Limpiar Duplicados Existentes

Si hay duplicados previos en la base de datos:

1. **Verificar** qué se va a eliminar:
   ```sql
   -- Ver duplicados antes de eliminar
   SELECT * FROM [remove_duplicates.sql query 1]
   ```

2. **Hacer BACKUP** de la base de datos

3. **Ejecutar** el script de eliminación:
   ```sql
   -- Descomentar y ejecutar el DELETE en remove_duplicates.sql
   ```

4. **Verificar** que se eliminaron correctamente

---

## 📋 Resumen de Cambios

| Archivo | Cambio | Propósito |
|---------|--------|-----------|
| `views/activities/create.php` | JavaScript para deshabilitar pestañas inactivas | Prevenir envío de múltiples arrays |
| `controllers/activityController.php` | Validación anti-duplicados + logs detallados | Detectar y omitir duplicados en tiempo real |
| `models/activity.php` | Método activityExists() + validación en createActivity() | Doble verificación antes de insertar |
| `add_unique_index_duplicates.sql` | Índice UNIQUE compuesto (opcional) | Protección a nivel de base de datos |
| `check_duplicates.sql` | Query para verificar duplicados | Identificar problema |
| `remove_duplicates.sql` | Script de limpieza | Eliminar duplicados existentes |

---

## 🛡️ Sistema de 3 Capas

El sistema ahora tiene **3 capas independientes de protección**:

1. **CAPA 1 - Frontend:** JavaScript deshabilita pestañas inactivas
2. **CAPA 2 - Backend:** Controlador verifica antes de crear cada actividad  
3. **CAPA 3 - Modelo:** Modelo verifica nuevamente antes de INSERT
4. **CAPA OPCIONAL - BD:** Índice UNIQUE rechaza duplicados

Ver documentación completa en: [VALIDACION_ANTI_DUPLICADOS.md](VALIDACION_ANTI_DUPLICADOS.md)

---

## ⚠️ Recomendaciones

1. **No combinar pestañas:** Usa solo UNA pestaña a la vez al crear actividades
2. **Verificar logs:** Después de crear actividades, revisar que solo llegue un array
3. **Monitorear:** Ejecutar `check_duplicates.sql` semanalmente para detectar problemas
4. **Backup regular:** Mantener backups antes de limpiezas masivas

---

## 🎯 Estado Final

✅ **Frontend:** Previene envío de datos de múltiples pestañas  
✅ **Backend:** Logging detallado para diagnóstico  
✅ **Base de datos:** Array_unique + array_values en todas las rutas  
✅ **Limpieza:** Scripts disponibles para remover duplicados existentes  

El problema de duplicación **está resuelto**. Las nuevas asignaciones no deberían crear duplicados.
