-- Consulta para verificar el estado de las evidencias
-- Ejecuta esta consulta en tu base de datos para ver qué valores tienen en el campo 'bloqueada'

SELECT 
    e.id,
    e.actividad_id,
    e.tipo_evidencia,
    e.archivo,
    e.contenido,
    e.bloqueada,
    e.fecha_subida,
    a.titulo as actividad_titulo
FROM evidencias e
JOIN actividades a ON e.actividad_id = a.id
WHERE e.archivo LIKE '%696911d08e798%' 
   OR e.archivo LIKE '%activity_135386_user_1396%'
ORDER BY e.fecha_subida DESC;

-- También puedes ver todas las evidencias de una actividad específica:
-- SELECT * FROM evidencias WHERE actividad_id = [ID_DE_TU_ACTIVIDAD] ORDER BY fecha_subida;
