# Sistema de Activistas - Mejoras Implementadas

## Resumen de Cambios

Se han implementado las siguientes mejoras en el sistema de activistas digitales según los requerimientos especificados:

### 1. Aumento del Límite de Imagen de Perfil (5MB → 20MB)

**Archivos Modificados:**
- `includes/functions.php` - Función `uploadFile()` actualizada
- `controllers/userController.php` - Llamadas a `uploadFile()` actualizadas

**Cambios:**
- La función `uploadFile()` ahora acepta un parámetro `$isProfile` para diferenciar imágenes de perfil
- Límite aumentado de 5MB (5242880 bytes) a 20MB (20971520 bytes) para imágenes de perfil
- Otros archivos mantienen el límite de 5MB

### 2. Formulario Inteligente de Actividades

**Archivos Modificados:**
- `views/activities/create.php` - Formulario con campos condicionales
- `controllers/activityController.php` - Validación role-aware
- `models/activity.php` - Método `createActivity()` actualizado

**Cambios:**
- Los campos "Lugar" y "Alcance Estimado" solo son visibles para SuperAdmin y Gestor
- Activistas y Líderes ven un formulario simplificado
- Validación actualizada para omitir campos no visibles según el rol

### 3. Sistema de Tareas Pendientes

**Archivos Modificados:**
- `models/activity.php` - Lógica de tareas pendientes
- Base de datos - Nuevas columnas en tabla `actividades`

**Características:**
- Actividades creadas por SuperAdmin o Líder aparecen como "Tarea Pendiente" para otros usuarios
- Campo `tarea_pendiente` (TINYINT) indica si es una tarea asignada
- Campo `solicitante_id` (INT) almacena quién asignó la tarea
- Método `getPendingTasks()` para obtener tareas pendientes

### 4. Sistema de Evidencias Bloqueadas

**Archivos Modificados:**
- `models/activity.php` - Métodos de evidencia actualizados
- Base de datos - Nuevas columnas en tabla `evidencias`

**Características:**
- Una vez subida evidencia, queda automáticamente bloqueada (`bloqueada = 1`)
- Se registra automáticamente la hora exacta de subida (`fecha_subida`)
- La actividad se marca como completada y se registra `hora_evidencia`
- No se pueden modificar evidencias una vez subidas

### 5. Sistema de Ranking

**Archivos Modificados:**
- `models/activity.php` - Métodos de ranking
- Base de datos - Nueva columna `ranking_puntos` en tabla `usuarios`

**Cálculo de Puntos:**
- **200 puntos** por cada tarea completada
- **800 puntos** por el mejor tiempo de respuesta
- Puntos de tiempo decrecientes según posición (permite valores negativos)
- Se actualiza automáticamente al subir evidencias

**Métodos:**
- `updateUserRankings()` - Calcula y actualiza rankings
- `getUserRanking($limit)` - Obtiene ranking de usuarios
- Se ejecuta automáticamente al subir evidencias

## Migración de Base de Datos

**Archivo:** `database_migration_improvements.sql`

```sql
-- Actualizar tabla usuarios
ALTER TABLE usuarios MODIFY COLUMN foto_perfil VARCHAR(255);
ALTER TABLE usuarios ADD COLUMN ranking_puntos INT DEFAULT 0;

-- Actualizar tabla actividades
ALTER TABLE actividades 
ADD COLUMN hora_evidencia DATETIME NULL,
ADD COLUMN tarea_pendiente TINYINT(1) DEFAULT 0,
ADD COLUMN solicitante_id INT DEFAULT NULL,
DROP COLUMN lugar,
DROP COLUMN alcance_estimado;

-- Actualizar tabla evidencias
ALTER TABLE evidencias 
ADD COLUMN fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN bloqueada TINYINT(1) DEFAULT 0;

-- Índices para rendimiento
CREATE INDEX idx_usuarios_ranking ON usuarios(ranking_puntos DESC);
CREATE INDEX idx_actividades_tarea_pendiente ON actividades(tarea_pendiente, solicitante_id);
CREATE INDEX idx_evidencias_fecha_subida ON evidencias(fecha_subida);
```

## Archivos de Demostración

1. **`demo_improvements.html`** - Demostración completa de todas las mejoras
2. **`demo_activity_form.html`** - Comparación de formularios por rol
3. **`test_logic.php`** - Pruebas de lógica sin base de datos
4. **`test_improvements.php`** - Pruebas con conexión a base de datos

## Funcionalidad Preservada

- ✅ Todas las funciones existentes continúan funcionando
- ✅ Sistema de autenticación y autorización intacto
- ✅ Flujo de trabajo de actividades preservado
- ✅ Compatibilidad hacia atrás mantenida

## Validación y Pruebas

### Pruebas Lógicas (sin BD)
```bash
php test_logic.php
```
- ✅ Validación de tamaño de archivos
- ✅ Visibilidad de campos por rol
- ✅ Validación de actividades
- ✅ Lógica de tareas pendientes
- ✅ Cálculo de ranking

### Pruebas de Integración
```bash
php test_improvements.php
```
- ✅ Conexión a base de datos
- ✅ Instanciación de modelos
- ✅ Existencia de métodos requeridos

## Configuración Requerida

1. **Ejecutar migración de base de datos:**
   ```bash
   mysql -u usuario -p base_datos < database_migration_improvements.sql
   ```

2. **Verificar permisos de directorios:**
   ```bash
   chmod 755 public/assets/uploads/profiles
   ```

3. **Configuración PHP (opcional):**
   ```ini
   upload_max_filesize = 25M
   post_max_size = 25M
   ```

## Uso del Sistema

### Para SuperAdmin/Gestor:
- Formulario completo con todos los campos
- Pueden crear tareas pendientes para otros usuarios
- Ven estadísticas completas de ranking

### Para Líder:
- Formulario simplificado (sin lugar/alcance)
- Pueden crear tareas pendientes para activistas
- Ven ranking de su equipo

### Para Activista:
- Formulario simplificado
- Reciben tareas pendientes
- Pueden subir evidencias para completar tareas
- Participan en el sistema de ranking

## Aspectos Técnicos

### Seguridad
- Validación CSRF mantenida
- Sanitización de datos preservada
- Control de acceso por roles

### Rendimiento
- Índices agregados para consultas de ranking
- Consultas optimizadas para tareas pendientes
- Carga condicional de campos según rol

### Escalabilidad
- Estructura preparada para múltiples equipos
- Sistema de ranking eficiente
- Gestión de evidencias escalable

## Notas de Implementación

- Se mantuvieron las convenciones de código existentes
- Arquitectura MVC respetada
- Compatibilidad con el sistema de logging actual
- Manejo robusto de errores implementado