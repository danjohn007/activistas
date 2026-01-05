# Corrección: Duplicación de Actividades

## Problema Identificado

Cuando el SuperAdmin asignaba una actividad, **algunas personas recibían la actividad duplicada**, no todas, solo algunas.

## Causa Raíz

El problema ocurría debido a una **doble asignación** de activistas cuando se seleccionaban líderes:

### 1. **En el Frontend** ([create.php](views/activities/create.php))
Cuando se seleccionaba un líder en la pestaña "Líderes", el JavaScript automáticamente:
- Marcaba los checkboxes de todos los activistas de ese líder en la pestaña "Todos los Usuarios"
- Esto se hacía mediante las funciones `autoSelectTeamMembers()` y `autoSelectGroupMembers()`

### 2. **En el Backend** ([activityController.php](controllers/activityController.php))
Cuando se procesaban los líderes seleccionados, el código PHP:
- Agregaba automáticamente a todos los activistas de cada líder a la lista de destinatarios
- Esto se hacía en las líneas 204-220

### 3. **Resultado: Duplicación**
Si un usuario seleccionaba:
- ✅ Un líder en la pestaña "Líderes" → El backend agregaba al líder Y sus activistas
- ✅ La pestaña "Todos los Usuarios" seguía activa → El frontend ya había marcado a esos mismos activistas

**Los activistas aparecían DOS veces en el array de destinatarios:**
1. Una vez por la selección automática del frontend en "Todos los Usuarios"
2. Otra vez por la lógica del backend que agregaba automáticamente los activistas del líder

Aunque existía `array_unique()` en el código, si ambas pestañas enviaban datos al formulario, podían ocurrir duplicados porque el código procesaba múltiples arrays de entrada.

## Solución Implementada

### 1. **Eliminación de Auto-selección en Frontend**
- ✅ Deshabilitamos las funciones `autoSelectTeamMembers()` y `autoSelectGroupMembers()`
- ✅ Ahora estas funciones solo registran un mensaje en consola pero NO modifican checkboxes
- ✅ Esto elimina la primera fuente de duplicación

### 2. **Mejora del `array_unique` en Backend**
- ✅ Cambiamos `array_unique($recipients)` por `array_values(array_unique($recipients))`
- ✅ Esto asegura que el array resultante tenga índices secuenciales (0, 1, 2...) sin huecos
- ✅ Se aplicó a todas las rutas de asignación:
  - Líderes seleccionados
  - Grupos seleccionados
  - Todos los usuarios
  - Activistas seleccionados por líder

### 3. **Lógica Clara y Unificada**
Ahora el flujo es más claro:
- El **backend** es responsable de expandir líderes → activistas
- El **frontend** solo envía las selecciones del usuario sin modificar otras pestañas
- El **array_unique** elimina cualquier duplicado residual

## Archivos Modificados

### 1. [views/activities/create.php](views/activities/create.php)
```javascript
// ANTES: Las funciones modificaban checkboxes y causaban duplicados
function autoSelectTeamMembers(leaderCheckbox) {
    // ... código que marcaba checkboxes ...
}

// AHORA: Las funciones solo registran información
function autoSelectTeamMembers(leaderCheckbox) {
    console.log('ℹ️ Los activistas del líder serán asignados automáticamente en el backend');
}
```

### 2. [controllers/activityController.php](controllers/activityController.php)
```php
// ANTES
$recipients = array_unique($recipients);

// AHORA: Asegura índices secuenciales
$recipients = array_values(array_unique($recipients));
```

## Casos de Prueba

Para verificar que el problema está resuelto, prueba estos escenarios:

### ✅ Caso 1: Asignar a Líderes
1. Ir a "Nueva Actividad"
2. Seleccionar uno o más líderes en la pestaña "Líderes"
3. Crear la actividad
4. **Verificar**: Cada líder y cada activista del líder debe recibir UNA sola actividad

### ✅ Caso 2: Asignar a Grupos
1. Ir a "Nueva Actividad"
2. Seleccionar uno o más grupos en la pestaña "Grupos"
3. Crear la actividad
4. **Verificar**: Cada miembro del grupo debe recibir UNA sola actividad

### ✅ Caso 3: Asignar a Todos los Usuarios
1. Ir a "Nueva Actividad"
2. Ir a la pestaña "Todos los Usuarios"
3. Seleccionar usuarios específicos o todos
4. Crear la actividad
5. **Verificar**: Cada usuario seleccionado debe recibir UNA sola actividad

### ✅ Caso 4: Usuario en Múltiples Grupos
1. Tener un usuario que pertenece a 2 o más grupos
2. Asignar una actividad a todos esos grupos
3. **Verificar**: El usuario debe recibir UNA sola actividad, no una por cada grupo

## Prevención de Futuros Problemas

Para evitar que este problema vuelva a ocurrir:

1. **No combinar pestañas**: El frontend ahora solo debe enviar datos de UNA pestaña activa
2. **Siempre usar `array_values(array_unique())`**: Cuando se construyan arrays de destinatarios
3. **Logging**: Agregar logs temporales para verificar el contenido de `$recipients` antes del loop
4. **Testing**: Probar siempre con usuarios que pertenecen a múltiples grupos/líderes

## Verificación en Base de Datos

Para verificar si hay duplicados existentes en la base de datos:

```sql
-- Buscar actividades duplicadas para el mismo usuario
SELECT 
    usuario_id,
    titulo,
    fecha_actividad,
    COUNT(*) as cantidad
FROM actividades
WHERE fecha_actividad >= CURDATE() - INTERVAL 7 DAY
GROUP BY usuario_id, titulo, fecha_actividad
HAVING COUNT(*) > 1
ORDER BY cantidad DESC;
```

Si encuentras duplicados, puedes limpiarlos manualmente o contactar al desarrollador para un script de limpieza.

## Resumen

✅ **Problema**: Algunos usuarios recibían actividades duplicadas cuando el SuperAdmin asignaba actividades  
✅ **Causa**: Doble asignación entre frontend (auto-selección de checkboxes) y backend (expansión de líderes)  
✅ **Solución**: Deshabilitada auto-selección en frontend + mejora de eliminación de duplicados en backend  
✅ **Estado**: Corregido y listo para pruebas  

---

*Fecha de corrección: 5 de enero de 2026*
