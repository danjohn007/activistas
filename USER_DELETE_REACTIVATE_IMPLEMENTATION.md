# Implementación de Funcionalidad de Eliminación y Reactivación de Usuarios

## Resumen
Se ha implementado correctamente la funcionalidad de eliminación (soft delete) y reactivación de usuarios para el rol SuperAdmin en el sistema de gestión de activistas.

## Características Implementadas

### 1. Eliminación de Usuarios (Soft Delete)
- **Rol requerido**: SuperAdmin únicamente
- **Método**: Soft delete - cambia el estado del usuario a 'eliminado'
- **Ubicación**: Vista de Gestión de Usuarios (`views/admin/users.php`)
- **Botón**: Ícono de papelera (🗑️) - Color rojo
- **Funcionalidad**:
  - No elimina físicamente el usuario de la base de datos
  - Marca el usuario con estado 'eliminado'
  - Mantiene toda la información y relaciones del usuario
  - Registra la acción en el log de actividades
  - Muestra confirmación antes de ejecutar

### 2. Reactivación de Usuarios
- **Rol requerido**: SuperAdmin únicamente
- **Estados que se pueden reactivar**:
  - Eliminado (`eliminado`)
  - Desactivado (`desactivado`)
  - Suspendido (`suspendido`)
- **Ubicación**: Vista de Gestión de Usuarios (`views/admin/users.php`)
- **Botón**: Ícono de recargar (🔄) - Color verde
- **Funcionalidad**:
  - Cambia el estado del usuario a 'activo'
  - Permite que el usuario vuelva a acceder al sistema
  - Registra la reactivación en el log de actividades
  - Muestra confirmación antes de ejecutar

### 3. Filtrado de Usuarios
- Los usuarios con estado 'eliminado' pueden ser filtrados en la vista de gestión
- Solo SuperAdmin puede ver usuarios eliminados en el filtro de estado
- Los usuarios eliminados se muestran con badge de color oscuro

## Flujos de Estados de Usuario

### Estados Disponibles
1. **Pendiente** (`pendiente`) - Esperando aprobación
2. **Activo** (`activo`) - Usuario activo en el sistema
3. **Suspendido** (`suspendido`) - Temporalmente suspendido
4. **Desactivado** (`desactivado`) - Rechazado o desactivado
5. **Eliminado** (`eliminado`) - Marcado como eliminado (soft delete)

### Transiciones de Estado
```
Activo → Suspendido (SuperAdmin/Gestor)
Activo → Desactivado (SuperAdmin/Gestor)
Activo → Eliminado (SuperAdmin solamente)

Suspendido → Activo (SuperAdmin/Gestor)
Suspendido → Activo (SuperAdmin via Reactivar)

Desactivado → Activo (SuperAdmin via Reactivar)
Eliminado → Activo (SuperAdmin via Reactivar)
```

## Archivos Modificados

### 1. `/views/admin/users.php`
**Cambios realizados**:
- Añadido botón de reactivación para usuarios eliminados/desactivados
- Actualizado el badge de estado para incluir 'eliminado' con color oscuro
- Añadida función JavaScript `reactivateUser()` con:
  - Validación y confirmación
  - Estados de carga
  - Manejo de errores
  - Recarga automática después de éxito
- Actualizado mensaje de confirmación de eliminación para clarificar que es soft delete

### 2. `/public/api/users.php`
**Cambios realizados**:
- Añadido case 'reactivate' en el switch de acciones
- Validaciones de permisos (solo SuperAdmin)
- Validación de estados permitidos para reactivación
- Registro de actividad con estado anterior
- Respuestas JSON estructuradas con mensajes de éxito/error

## Seguridad

### Control de Acceso
- Solo usuarios con rol **SuperAdmin** pueden:
  - Eliminar usuarios (soft delete)
  - Reactivar usuarios
- Validación de permisos en el backend (API)
- Validación de permisos en el frontend (botones condicionados)

### Auditoría
- Todas las acciones se registran en el log de actividades
- El log incluye:
  - ID del usuario afectado
  - Acción realizada (eliminado/reactivado)
  - Usuario que realizó la acción (SuperAdmin)
  - Estado anterior (en caso de reactivación)
  - Timestamp automático

## Base de Datos

### Tabla: `usuarios`
**Campo modificado**: `estado`
- Tipo: ENUM
- Valores permitidos: `'pendiente'`, `'activo'`, `'suspendido'`, `'desactivado'`, `'eliminado'`
- Default: `'pendiente'`

**Migración requerida**: `migration_user_management_fixes.sql`
```sql
ALTER TABLE usuarios 
MODIFY COLUMN estado ENUM('pendiente', 'activo', 'suspendido', 'desactivado', 'eliminado') 
DEFAULT 'pendiente';
```

## API Endpoints

### DELETE - Eliminar Usuario (Soft Delete)
**Endpoint**: `/api/users.php`
**Método**: POST
**Acción**: `delete`

**Request Body**:
```json
{
  "action": "delete",
  "user_id": 123
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Usuario eliminado exitosamente"
}
```

**Response Error**:
```json
{
  "success": false,
  "error": "No tienes permisos para eliminar usuarios"
}
```

### REACTIVATE - Reactivar Usuario
**Endpoint**: `/api/users.php`
**Método**: POST
**Acción**: `reactivate`

**Request Body**:
```json
{
  "action": "reactivate",
  "user_id": 123
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Usuario reactivado exitosamente"
}
```

**Response Error**:
```json
{
  "success": false,
  "error": "Solo se pueden reactivar usuarios eliminados, desactivados o suspendidos"
}
```

## Interfaz de Usuario

### Botones de Acción por Estado

| Estado Usuario | Botones Disponibles (SuperAdmin) |
|----------------|----------------------------------|
| Activo | Editar, Cambiar Contraseña, Suspender, Desactivar, Eliminar |
| Suspendido | Editar, Cambiar Contraseña, Activar, Desactivar, Eliminar |
| Desactivado | Editar, Reactivar |
| Eliminado | Editar, Reactivar |
| Pendiente | (Pendiente de aprobación) |

### Colores de Badge por Estado
- **Activo**: Verde (`bg-success`)
- **Pendiente**: Amarillo (`bg-warning`)
- **Suspendido**: Rojo (`bg-danger`)
- **Desactivado**: Gris (`bg-secondary`)
- **Eliminado**: Negro (`bg-dark`)

## Notas Técnicas

### Soft Delete vs Hard Delete
Se optó por **soft delete** por las siguientes razones:
1. **Preservación de datos**: Mantiene el historial de actividades del usuario
2. **Referencias intactas**: No rompe relaciones con actividades, líderes, etc.
3. **Auditoría**: Permite rastrear usuarios eliminados para auditorías
4. **Reversibilidad**: Posibilidad de reactivar usuarios si es necesario
5. **Seguridad**: Evita pérdida accidental de datos importantes

### Consideraciones Futuras
- Implementar hard delete programado (después de X meses de eliminación)
- Añadir razón de eliminación/reactivación
- Notificaciones por email en eliminación/reactivación
- Dashboard de usuarios eliminados

## Testing

### Casos de Prueba Recomendados
1. ✅ SuperAdmin puede eliminar un usuario activo
2. ✅ SuperAdmin puede reactivar un usuario eliminado
3. ✅ SuperAdmin puede reactivar un usuario desactivado
4. ✅ Usuario eliminado no puede iniciar sesión
5. ✅ Usuario reactivado puede iniciar sesión
6. ✅ Gestor NO puede eliminar usuarios
7. ✅ Gestor NO puede reactivar usuarios
8. ✅ Los logs registran correctamente las acciones
9. ✅ Los filtros muestran correctamente usuarios eliminados

## Documentación de Código

### Funciones JavaScript

#### `deleteUser(userId, userName)`
Elimina (soft delete) un usuario del sistema.
- **Parámetros**:
  - `userId`: ID del usuario a eliminar
  - `userName`: Nombre del usuario (para confirmación)
- **Retorna**: void (recarga la página en éxito)

#### `reactivateUser(userId, userName)`
Reactiva un usuario eliminado/desactivado.
- **Parámetros**:
  - `userId`: ID del usuario a reactivar
  - `userName`: Nombre del usuario (para confirmación)
- **Retorna**: void (recarga la página en éxito)

## Conclusión
La implementación de eliminación y reactivación de usuarios está completa y funcional. Cumple con los requisitos de seguridad, auditoría y usabilidad del sistema.
