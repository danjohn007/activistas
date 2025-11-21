-- Query para verificar qué actividades existen con el título buscado
SELECT 
    a.id,
    a.titulo,
    a.tipo_actividad_id,
    ta.nombre as tipo_actividad,
    a.tarea_pendiente,
    a.estado,
    a.fecha_creacion,
    u.nombre_completo as usuario
FROM actividades a
JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
JOIN usuarios u ON a.usuario_id = u.id
WHERE a.titulo = 'Niño Luis / Escuela Real Madrid'
  AND a.tipo_actividad_id = 1
  AND a.fecha_creacion >= '2025-11-01 00:00:00'
  AND a.fecha_creacion <= '2025-11-30 23:59:59'
ORDER BY a.fecha_creacion DESC;

-- Query alternativa SIN filtrar por tarea_pendiente
SELECT 
    a.id,
    a.titulo,
    a.tipo_actividad_id,
    ta.nombre as tipo_actividad,
    a.tarea_pendiente,
    a.estado,
    a.fecha_creacion,
    COUNT(*) as total
FROM actividades a
JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
WHERE a.titulo = 'Niño Luis / Escuela Real Madrid'
  AND a.tipo_actividad_id = 1
GROUP BY a.tarea_pendiente, a.estado;
