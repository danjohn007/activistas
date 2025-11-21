# Solución al Problema de Gráficas en Dashboard Admin

## Problema Identificado

Las gráficas en `dashboards/admin.php` no se muestran a pesar de que hay datos en la base de datos.

## Causa Raíz

1. **Falta la tabla `tipos_actividades`** o está vacía
2. La consulta en `getActivitiesByType()` depende de esta tabla
3. Sin tipos de actividades registrados, las gráficas aparecen vacías

## Evidencia de Datos

Tu base de datos **SÍ tiene actividades**:
- Tabla `actividades` tiene 182+ registros (IDs 2208-2390)
- Hay actividades completadas, programadas y en progreso
- Los datos están correctos

## Solución

### Paso 1: Verificar Tabla `tipos_actividades`

Ejecuta este SQL en tu servidor:

```sql
-- Ver tipos de actividades existentes
SELECT * FROM tipos_actividades;
```

Si está vacía o no existe, ejecuta:

```sql
-- Crear tipos de actividades basados en los datos actuales
INSERT INTO tipos_actividades (id, nombre, descripcion, puntos, icono, estado) VALUES
(1, 'Publicaciones en Redes Sociales', 'Publicaciones en redes sociales para difundir información', 10, 'fa-share-alt', 'activo'),
(3, 'Dinámica Express', 'Actividades dinámicas rápidas', 15, 'fa-bolt', 'activo'),
(5, 'Transmisiones en Vivo', 'Transmisiones en vivo en redes sociales', 20, 'fa-video', 'activo'),
(8, 'Comentarios en Publicaciones', 'Comentar en publicaciones', 5, 'fa-comment', 'activo'),
(9, 'Subir Contenido', 'Subir contenido a historias y/o en formato publicación', 15, 'fa-cloud-upload-alt', 'activo'),
(11, 'Eventos', 'Participación en eventos', 25, 'fa-calendar', 'activo');
```

### Paso 2: Verificar Conexión a Base de Datos

Crea este archivo de prueba:

**Archivo**: `public/test_db_connection.php`

```php
<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Conexión exitosa a la base de datos<br>";
    
    // Test 1: Contar actividades
    $stmt = $conn->query("SELECT COUNT(*) as total FROM actividades");
    $result = $stmt->fetch();
    echo "Total actividades: " . $result['total'] . "<br>";
    
    // Test 2: Verificar tipos_actividades
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM tipos_actividades");
        $result = $stmt->fetch();
        echo "Total tipos actividades: " . $result['total'] . "<br>";
        
        if ($result['total'] == 0) {
            echo "⚠️ PROBLEMA: No hay tipos de actividades registrados<br>";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: Tabla tipos_actividades no existe: " . $e->getMessage() . "<br>";
    }
    
    // Test 3: Actividades por tipo
    try {
        $stmt = $conn->query("
            SELECT ta.nombre, COUNT(a.id) as cantidad
            FROM tipos_actividades ta
            LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id
            GROUP BY ta.id, ta.nombre 
            ORDER BY cantidad DESC
        ");
        $results = $stmt->fetchAll();
        
        echo "<br><strong>Actividades por tipo:</strong><br>";
        foreach ($results as $row) {
            echo "- {$row['nombre']}: {$row['cantidad']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ ERROR en consulta: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ Error de conexión a la base de datos";
}
?>
```

### Paso 3: Verificar API Stats

Accede a: `https://fix360.app/ad/public/api/stats.php`

Debería devolver algo como:

```json
{
    "success": true,
    "timestamp": "2025-11-18T...",
    "data": {
        "activity_stats": {...},
        "activities_by_type": [
            {"nombre": "Publicaciones...", "cantidad": "50"},
            ...
        ],
        "user_stats": {...}
    }
}
```

Si ves `"activities_by_type": []`, entonces **confirma que falta la tabla tipos_actividades**.

## Pasos de Solución Rápida

### 1. Ejecutar SQL en tu servidor

```sql
-- Verificar si existe la tabla
SHOW TABLES LIKE 'tipos_actividades';

-- Si no existe, crearla
CREATE TABLE IF NOT EXISTS `tipos_actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8_unicode_ci,
  `puntos` int(11) DEFAULT '10',
  `icono` varchar(50) COLLATE utf8_unicode_ci DEFAULT 'fa-tasks',
  `estado` enum('activo','inactivo') COLLATE utf8_unicode_ci DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Insertar tipos básicos
INSERT INTO tipos_actividades (id, nombre, descripcion, puntos, icono, estado) VALUES
(1, 'Publicaciones en Redes Sociales', 'Publicaciones en redes sociales para difundir información', 10, 'fa-share-alt', 'activo'),
(3, 'Dinámica Express', 'Actividades dinámicas rápidas', 15, 'fa-bolt', 'activo'),
(5, 'Transmisiones en Vivo', 'Transmisiones en vivo en redes sociales', 20, 'fa-video', 'activo'),
(8, 'Comentarios en Publicaciones', 'Comentar en publicaciones', 5, 'fa-comment', 'activo'),
(9, 'Subir Contenido', 'Subir contenido a historias y/o en formato publicación', 15, 'fa-cloud-upload-alt', 'activo'),
(11, 'Eventos', 'Participación en eventos', 25, 'fa-calendar', 'activo');
```

### 2. Recargar el Dashboard

Después de ejecutar el SQL:
1. Accede a `https://fix360.app/ad/public/dashboards/admin.php`
2. Las gráficas deberían aparecer inmediatamente
3. Si no aparecen, presiona el botón "Actualizar Datos"

## Verificación Final

Las gráficas deberían mostrar:

1. **Gráfica de Actividades por Tipo** (barras):
   - Publicaciones en Redes Sociales
   - Transmisiones en Vivo
   - Subir Contenido
   - Dinámica Express
   - Comentarios
   - Eventos

2. **Gráfica de Usuarios por Rol** (dona):
   - SuperAdmin
   - Gestor
   - Líder
   - Activista

3. **Gráfica de Actividades Mensuales** (línea):
   - Últimos 12 meses
   - Tendencia

4. **Ranking de Equipos** (barras horizontales):
   - Top 10 líderes con más actividades completadas

## Diagnóstico de Problemas

Si las gráficas siguen sin aparecer:

1. **Abrir Consola del Navegador** (F12)
   - Buscar errores en rojo
   - Verificar que Chart.js se haya cargado

2. **Verificar en Network Tab**:
   - Ver si `api/stats.php` responde correctamente
   - Ver el contenido de la respuesta JSON

3. **Revisar logs del servidor**:
   - `error_log` de Apache/PHP
   - Ver errores de base de datos

## Contacto

Si después de estos pasos las gráficas no aparecen, proporciona:
1. Resultado de ejecutar `SELECT COUNT(*) FROM tipos_actividades`
2. Respuesta JSON de `api/stats.php`
3. Cualquier error en la consola del navegador

---

**Nota**: El problema más común es que la tabla `tipos_actividades` esté vacía o no exista. Una vez añadidos los tipos, todo debería funcionar correctamente.
