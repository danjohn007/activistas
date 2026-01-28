-- Script para eliminar actividades duplicadas
-- IMPORTANTE: Ejecuta primero el query de verificación para ver qué se va a eliminar

-- 1. VER qué duplicados existen (ejecuta esto primero)
SELECT 
    a.id,
    a.usuario_id,
    u.nombre_completo as nombre_usuario,
    a.titulo,
    ta.nombre as tipo_actividad,
    a.fecha_actividad,
    a.fecha_creacion,
    a.estado,
    COUNT(*) OVER (PARTITION BY a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad) as cantidad_duplicados
FROM actividades a
LEFT JOIN usuarios u ON a.usuario_id = u.id
LEFT JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
WHERE (a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad) IN (
    SELECT usuario_id, titulo, tipo_actividad_id, fecha_actividad
    FROM actividades
    WHERE fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY usuario_id, titulo, tipo_actividad_id, fecha_actividad
    HAVING COUNT(*) > 1
)
ORDER BY a.usuario_id, a.titulo, a.fecha_actividad, a.id;

-- 2. ELIMINAR duplicados (conservando solo el primer registro creado)
-- ADVERTENCIA: Este query ELIMINARÁ datos. Asegúrate de haber verificado arriba.
-- Descomenta las siguientes líneas cuando estés seguro:

/*
DELETE a1 FROM actividades a1
INNER JOIN actividades a2 
WHERE 
    a1.usuario_id = a2.usuario_id 
    AND a1.titulo = a2.titulo
    AND a1.tipo_actividad_id = a2.tipo_actividad_id
    AND a1.fecha_actividad = a2.fecha_actividad
    AND a1.id > a2.id  -- Mantener solo el primero (ID menor)
    AND a1.fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
*/

-- 3. Verificar que se eliminaron correctamente
-- Ejecuta nuevamente el primer query para confirmar que no quedan duplicados

-- 4. Ver resumen de lo que se eliminó
/*
SELECT 
    'Duplicados eliminados' as resultado,
    ROW_COUNT() as cantidad_eliminada;
*/

-- NOTAS IMPORTANTES:
-- - Este script solo afecta actividades de los últimos 30 días
-- - Se conserva la actividad con ID menor (la creada primero)
-- - Las evidencias asociadas se eliminarán automáticamente si hay CASCADE
-- - Haz un BACKUP antes de ejecutar el DELETE
