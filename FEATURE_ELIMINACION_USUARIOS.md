# Nueva Funcionalidad: Gestión Avanzada de Usuarios para SuperAdmin

## Resumen

Se han agregado nuevas capacidades para que los **SuperAdmin** puedan gestionar usuarios de manera más completa:

1. ✅ **Desvincular activistas de sus líderes**
2. ✅ **Eliminar usuarios permanentemente del sistema** (con validaciones de seguridad)

## 1. Desvincular Activistas de Líderes

### Funcionalidad
Permite al SuperAdmin **remover la relación** entre un activista y su líder asignado, dejando al activista sin líder.

### Ubicación
- **Vista**: [views/admin/users.php](views/admin/users.php)
- **API**: [public/api/users.php](public/api/users.php) - Acción `unlink_from_leader`
- **Modelo**: [models/user.php](models/user.php) - Función `unlinkFromLeader()`

### Cómo usar
1. Ir a **Admin > Gestión de Usuarios**
2. Buscar un activista que tenga líder asignado
3. Hacer clic en el botón con el ícono 🔗 (unlink)
4. Confirmar la acción

### Validaciones
- ✅ Solo SuperAdmin puede desvincular
- ✅ Solo funciona con usuarios de rol "Activista"
- ✅ El activista debe tener un líder asignado

### Código Relevante

#### Botón en la Vista
```php
<?php if ($currentUser['rol'] === 'SuperAdmin' && $user['rol'] === 'Activista' && !empty($user['lider_id'])): ?>
    <button type="button" class="btn btn-outline-warning" 
            onclick="unlinkFromLeader(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_completo']) ?>')" 
            title="Desvincular de Líder">
        <i class="fas fa-unlink"></i>
    </button>
<?php endif; ?>
```

#### Función del Modelo
```php
public function unlinkFromLeader($userId) {
    $stmt = $this->db->prepare("UPDATE usuarios SET lider_id = NULL WHERE id = ? AND rol = 'Activista'");
    $result = $stmt->execute([$userId]);
    
    if ($result) {
        logActivity("Activista ID $userId desvinculado de su líder");
    }
    
    return $result;
}
```

---

## 2. Eliminación Permanente de Usuarios

### Funcionalidad
Permite al SuperAdmin **eliminar completamente** un usuario del sistema (hard delete), incluyendo todas sus dependencias si es necesario.

### Diferencia con "Eliminar Usuario" (Soft Delete)
| Característica | Soft Delete (🗑️) | Hard Delete (🗑️🗑️) |
|---------------|------------------|---------------------|
| **Acción** | Cambia estado a "eliminado" | Elimina registro de BD |
| **Reversible** | ✅ Sí (cambiar estado) | ❌ No, permanente |
| **Datos** | Se conservan | Se eliminan |
| **Actividades** | Se mantienen | Se pueden eliminar |
| **Uso recomendado** | Suspensión temporal | Limpieza definitiva |

### Ubicación
- **Vista**: [views/admin/users.php](views/admin/users.php)
- **API**: [public/api/users.php](public/api/users.php) - Acciones `check_delete` y `delete_permanent`
- **Modelo**: [models/user.php](models/user.php) - Funciones `canDeleteUser()` y `deleteUserPermanently()`

### Cómo usar

#### Caso 1: Usuario sin Dependencias
1. Ir a **Admin > Gestión de Usuarios**
2. Hacer clic en el botón rojo con ícono 🗑️🗑️ (Eliminar Permanentemente)
3. El sistema verifica automáticamente si el usuario tiene:
   - Actividades registradas
   - Activistas asignados (si es líder)
   - Evidencias subidas
4. Si **no tiene dependencias**, muestra:
   ```
   ✅ Usuario sin dependencias, se puede eliminar.
   ¿Confirmas que deseas ELIMINAR PERMANENTEMENTE este usuario?
   ```
5. Confirmar para eliminar

#### Caso 2: Usuario con Dependencias
1. El sistema detecta las dependencias automáticamente
2. Muestra advertencia detallada:
   ```
   ⚠️ ADVERTENCIA: Esta acción NO se puede deshacer ⚠️
   
   Usuario: Juan Pérez
   
   ❌ Actividades: 25
   ❌ Activistas asignados: 5
   ❌ Evidencias: 42
   
   ¿Deseas ELIMINAR PERMANENTEMENTE este usuario y TODAS sus dependencias?
   
   Esto eliminará:
   - Todas sus actividades (25)
   - Todas las evidencias (42)
   - Desvinculará 5 activista(s)
   
   ⚠️ Esta acción es IRREVERSIBLE ⚠️
   ```
3. Si confirmas, el sistema:
   - Elimina todas las evidencias
   - Elimina todas las actividades
   - Desvincula activistas (si es líder)
   - Elimina tokens de reset de contraseña
   - Elimina relaciones de grupos
   - **Elimina el usuario**

### Validaciones y Seguridad

#### Verificación Previa (`canDeleteUser`)
```php
public function canDeleteUser($userId) {
    // Cuenta:
    // - Actividades del usuario
    // - Activistas asignados (si es líder)
    // - Evidencias subidas
    
    // Retorna:
    return [
        'can_delete' => true/false,
        'reason' => 'Razón detallada',
        'stats' => [
            'activities' => 25,
            'activists' => 5,
            'evidences' => 42
        ]
    ];
}
```

#### Eliminación con Transacciones
```php
public function deleteUserPermanently($userId, $force = false) {
    // 1. Verificar si se puede eliminar
    $check = $this->canDeleteUser($userId);
    
    if (!$check['can_delete'] && !$force) {
        return ['success' => false, 'message' => $check['reason']];
    }
    
    // 2. Iniciar transacción
    $this->db->beginTransaction();
    
    try {
        if ($force) {
            // Eliminar dependencias
            // - Evidencias
            // - Actividades
            // - Desvincular activistas (si es líder)
        }
        
        // Eliminar tokens y relaciones
        // Eliminar usuario
        
        $this->db->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}
```

### Botones en la Interfaz

```php
<!-- Botón Soft Delete (estado "eliminado") -->
<button type="button" class="btn btn-outline-danger" 
        onclick="deleteUser(...)" 
        title="Eliminar Usuario (Soft Delete)">
    <i class="fas fa-trash"></i>
</button>

<!-- Botón Hard Delete (eliminación permanente) -->
<button type="button" class="btn btn-danger" 
        onclick="deletePermanently(...)" 
        title="Eliminar Permanentemente (No se puede deshacer)">
    <i class="fas fa-trash-alt"></i>
</button>
```

### JavaScript del Cliente
La función `deletePermanently()` realiza:

1. **Verificación previa** (GET `check_delete`)
   - Obtiene estadísticas de dependencias
   - Muestra información detallada al usuario

2. **Confirmación del usuario**
   - Mensaje personalizado según dependencias
   - Advertencias claras sobre irreversibilidad

3. **Eliminación** (POST `delete_permanent`)
   - Envía parámetro `force` si tiene dependencias
   - Muestra progreso con spinner
   - Recarga página al completar

---

## Casos de Uso

### Caso 1: Reasignar Activistas
**Problema**: Un líder renunció y necesitas reasignar sus activistas a otro líder.

**Solución**:
1. Desvincular todos los activistas del líder original
2. Editar cada activista y asignarle el nuevo líder
3. Eliminar permanentemente al líder original (ahora sin dependencias)

### Caso 2: Limpiar Usuarios de Prueba
**Problema**: Tienes usuarios de prueba con actividades que quieres eliminar.

**Solución**:
1. Hacer clic en "Eliminar Permanentemente"
2. Confirmar la eliminación forzada (incluye actividades)
3. El sistema elimina todo en una transacción

### Caso 3: Usuario Duplicado
**Problema**: Se registró un usuario duplicado por error, sin actividades.

**Solución**:
1. Hacer clic en "Eliminar Permanentemente"
2. El sistema detecta que no tiene dependencias
3. Eliminación rápida confirmando una sola vez

---

## Registro de Actividades (Logs)

Todas las acciones quedan registradas en el sistema:

### Desvincular Activista
```
Activista ID 45 desvinculado de su líder por SuperAdmin Juan Admin
```

### Eliminación Sin Dependencias
```
Usuario ID 23 (María López) eliminado PERMANENTEMENTE del sistema
```

### Eliminación Forzada
```
Usuario ID 15 (Pedro Test) eliminado PERMANENTEMENTE del sistema (eliminación forzada con dependencias)
```

---

## Endpoints de la API

### 1. Desvincular de Líder
```http
POST /api/users.php
Content-Type: application/json

{
  "action": "unlink_from_leader",
  "user_id": 45
}
```

**Respuesta Exitosa**:
```json
{
  "success": true,
  "message": "Activista desvinculado del líder exitosamente"
}
```

### 2. Verificar si se Puede Eliminar
```http
GET /api/users.php?action=check_delete&user_id=23
```

**Respuesta**:
```json
{
  "success": true,
  "can_delete": false,
  "reason": "El usuario tiene 25 actividad(es) registrada(s). El líder tiene 5 activista(s) asignado(s). Debes reasignar o eliminar estas dependencias primero.",
  "stats": {
    "activities": 25,
    "activists": 5,
    "evidences": 42
  }
}
```

### 3. Eliminar Permanentemente
```http
POST /api/users.php
Content-Type: application/json

{
  "action": "delete_permanent",
  "user_id": 23,
  "force": true
}
```

**Respuesta Exitosa**:
```json
{
  "success": true,
  "message": "Usuario eliminado permanentemente del sistema"
}
```

---

## Archivos Modificados

### 1. [models/user.php](models/user.php)
**Nuevas funciones agregadas**:
- `unlinkFromLeader($userId)` - Desvincular activista de líder
- `canDeleteUser($userId)` - Verificar dependencias
- `deleteUserPermanently($userId, $force)` - Eliminación permanente

### 2. [public/api/users.php](public/api/users.php)
**Nuevos endpoints agregados**:
- `unlink_from_leader` - Desvincula activista
- `check_delete` - Verifica dependencias
- `delete_permanent` - Elimina permanentemente

### 3. [views/admin/users.php](views/admin/users.php)
**Cambios en la interfaz**:
- Botón "Desvincular de Líder" (solo para activistas con líder)
- Botón "Eliminar Permanentemente" (rojo sólido)
- Función JavaScript `unlinkFromLeader()`
- Función JavaScript `deletePermanently()`

---

## Precauciones y Recomendaciones

### ⚠️ Advertencias Importantes

1. **La eliminación permanente NO se puede deshacer**
   - Los datos se eliminan completamente de la base de datos
   - No hay respaldo automático
   - Usa con extremo cuidado

2. **Siempre verificar antes de eliminar**
   - Revisa las estadísticas mostradas
   - Asegúrate de que es el usuario correcto
   - Considera usar soft delete primero

3. **Backup recomendado**
   - Antes de eliminar usuarios con muchas actividades
   - Haz respaldo manual de la base de datos
   - Especialmente para usuarios líderes

### ✅ Mejores Prácticas

1. **Usa Soft Delete primero**
   - Cambia estado a "eliminado"
   - Observa si causa problemas
   - Elimina permanentemente después

2. **Reasigna antes de eliminar**
   - Si un líder se va, reasigna sus activistas primero
   - Luego elimina al líder sin dependencias

3. **Documenta las eliminaciones**
   - Los logs se guardan automáticamente
   - Revisa el historial periódicamente

---

## Pruebas Recomendadas

### Prueba 1: Desvincular Activista
1. ✅ Crear activista con líder asignado
2. ✅ Desvincular activista
3. ✅ Verificar que `lider_id` es NULL
4. ✅ Comprobar que el botón desaparece

### Prueba 2: Eliminar Usuario Sin Dependencias
1. ✅ Crear usuario nuevo sin actividades
2. ✅ Hacer clic en "Eliminar Permanentemente"
3. ✅ Verificar mensaje de confirmación
4. ✅ Confirmar eliminación
5. ✅ Verificar que el usuario ya no existe

### Prueba 3: Eliminar Usuario Con Dependencias
1. ✅ Usar usuario con actividades y evidencias
2. ✅ Hacer clic en "Eliminar Permanentemente"
3. ✅ Verificar advertencia con estadísticas
4. ✅ Confirmar eliminación forzada
5. ✅ Verificar que se eliminaron:
   - Usuario
   - Actividades
   - Evidencias
   - Relaciones de grupos

### Prueba 4: Cancelar Eliminación
1. ✅ Intentar eliminar usuario
2. ✅ Hacer clic en "Cancelar" en la confirmación
3. ✅ Verificar que el usuario sigue existiendo

---

## Resumen

✅ **Nuevas capacidades para SuperAdmin**:
- Desvincular activistas de líderes
- Eliminar usuarios permanentemente con validaciones inteligentes
- Verificación automática de dependencias
- Eliminación forzada con advertencias claras

✅ **Seguridad implementada**:
- Solo SuperAdmin puede usar estas funciones
- Transacciones de base de datos
- Validaciones múltiples
- Mensajes de confirmación detallados
- Registro completo de actividades

✅ **Interfaz mejorada**:
- Botones claramente diferenciados
- Iconos intuitivos
- Mensajes informativos
- Estados de carga

---

*Fecha de implementación: 5 de enero de 2026*
