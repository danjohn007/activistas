-- Verificar si hay actividades duplicadas para el mismo usuario
-- Este query te mostrará si hay personas con tareas duplicadas

SELECT 
    a.usuario_id,
    u.nombre_completo as nombre_usuario,
    u.email,
    a.titulo,
    a.tipo_actividad_id,
    a.fecha_actividad,
    COUNT(*) as cantidad_duplicados
FROM actividades a
LEFT JOIN usuarios u ON a.usuario_id = u.id
WHERE a.fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  -- Últimos 30 días
GROUP BY a.usuario_id, a.titulo, a.tipo_actividad_id, a.fecha_actividad
HAVING COUNT(*) > 1
ORDER BY cantidad_duplicados DESC, a.fecha_actividad DESC;

-- Si quieres ver TODAS las actividades duplicadas con más detalle
SELECT 
    a.id,
    a.usuario_id,
    u.nombre_completo as nombre_usuario,
    a.titulo,
    ta.nombre as tipo_actividad,
    a.fecha_actividad,
    a.fecha_creacion,
    a.estado
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
