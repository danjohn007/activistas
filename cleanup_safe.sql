-- Script SEGURO para eliminar duplicados del 27 de enero
-- Mantiene el registro con MEJOR estado (completada > programada)

-- PASO 1: Identificar qué registros mantener (los mejores de cada par)
SELECT 
    MIN(a.id) as id_a_mantener,
    a.usuario_id,
    u.nombre_completo,
    a.titulo,
    a.fecha_actividad,
    COUNT(*) as total_duplicados,
    GROUP_CONCAT(CONCAT(a.id, ':', a.estado) ORDER BY 
        CASE a.estado 
            WHEN 'completada' THEN 1 
            WHEN 'en_progreso' THEN 2
            WHEN 'programada' THEN 3
            WHEN 'cancelada' THEN 4
        END, a.id
    ) as todos_los_ids_estados
FROM actividades a
LEFT JOIN usuarios u ON a.usuario_id = u.id
WHERE fecha_creacion = '2026-01-27 12:23:31'
GROUP BY a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad
HAVING COUNT(*) > 1
ORDER BY a.usuario_id;

-- PASO 2: Eliminar duplicados (ejecuta cuando estés listo)
-- Este DELETE mantiene el registro con ID menor de cada grupo
DELETE FROM actividades 
WHERE id IN (
    SELECT id FROM (
        SELECT 
            a1.id
        FROM actividades a1
        INNER JOIN actividades a2 ON
            a1.usuario_id = a2.usuario_id 
            AND a1.titulo = a2.titulo
            AND a1.tipo_actividad_id = a2.tipo_actividad_id
            AND a1.fecha_actividad = a2.fecha_actividad
            AND a1.id > a2.id  -- Eliminar los de ID mayor
        WHERE 
            a1.fecha_creacion = '2026-01-27 12:23:31'
            AND a2.fecha_creacion = '2026-01-27 12:23:31'
    ) AS ids_to_delete
);
