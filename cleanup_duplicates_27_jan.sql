-- Limpieza de duplicados específicos del 27 de enero de 2026
-- Estos duplicados fueron creados ANTES de implementar el sistema anti-duplicados

-- 1. VERIFICAR cuántos duplicados hay del 27 de enero
SELECT 
    COUNT(*) as total_duplicados,
    COUNT(DISTINCT usuario_id) as usuarios_afectados
FROM (
    SELECT 
        usuario_id,
        titulo,
        tipo_actividad_id,
        fecha_actividad,
        COUNT(*) as cantidad
    FROM actividades
    WHERE fecha_creacion = '2026-01-27 12:23:31'
    GROUP BY usuario_id, titulo, tipo_actividad_id, fecha_actividad
    HAVING COUNT(*) > 1
) as duplicados;

-- 2. VER detalles de los duplicados antes de eliminar
SELECT 
    a.id,
    a.usuario_id,
    u.nombre_completo,
    a.titulo,
    a.fecha_actividad,
    a.fecha_creacion,
    a.estado,
    ROW_NUMBER() OVER (PARTITION BY a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad ORDER BY a.id) as fila
FROM actividades a
LEFT JOIN usuarios u ON a.usuario_id = u.id
WHERE fecha_creacion = '2026-01-27 12:23:31'
    AND (a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad) IN (
        SELECT usuario_id, titulo, tipo_actividad_id, fecha_actividad
        FROM actividades
        WHERE fecha_creacion = '2026-01-27 12:23:31'
        GROUP BY usuario_id, titulo, tipo_actividad_id, fecha_actividad
        HAVING COUNT(*) > 1
    )
ORDER BY a.usuario_id, a.titulo, a.id;

-- 3. ELIMINAR duplicados (conservar solo el primero - ID menor)
-- IMPORTANTE: Esto eliminará los registros duplicados
-- Descomenta y ejecuta cuando estés listo:

DELETE a1 FROM actividades a1
INNER JOIN actividades a2 
WHERE 
    a1.usuario_id = a2.usuario_id 
    AND a1.titulo = a2.titulo
    AND a1.tipo_actividad_id = a2.tipo_actividad_id
    AND a1.fecha_actividad = a2.fecha_actividad
    AND a1.id > a2.id  -- Eliminar el de ID mayor (mantener el primero)
    AND a1.fecha_creacion = '2026-01-27 12:23:31'
    AND a2.fecha_creacion = '2026-01-27 12:23:31';

-- 4. VERIFICAR que se eliminaron correctamente
SELECT 
    COUNT(*) as duplicados_restantes
FROM (
    SELECT 
        usuario_id,
        titulo,
        tipo_actividad_id,
        fecha_actividad,
        COUNT(*) as cantidad
    FROM actividades
    WHERE fecha_creacion = '2026-01-27 12:23:31'
    GROUP BY usuario_id, titulo, tipo_actividad_id, fecha_actividad
    HAVING COUNT(*) > 1
) as restantes;

-- 5. Ver resumen de lo eliminado
SELECT 
    'Duplicados eliminados exitosamente' as resultado,
    ROW_COUNT() as cantidad_eliminada;

-- NOTA: 
-- - Conservamos la actividad con ID menor (la primera creada)
-- - Si una actividad está "completada" y otra "programada", se mantendrá la primera por ID
-- - Las evidencias asociadas se eliminarán automáticamente si hay ON DELETE CASCADE
