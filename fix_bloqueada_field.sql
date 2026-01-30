-- Corregir evidencias que fueron subidas por usuarios pero tienen bloqueada = 0
-- La evidencia del gato plátano (ID 86305) fue subida por el usuario a las 16:27:07
-- y debería tener bloqueada = 1, no 0

-- 1. Corregir la evidencia específica del gato plátano
UPDATE evidencias 
SET bloqueada = 1 
WHERE id = 86305;

-- 2. Si hay más evidencias con el mismo problema, puedes usar esta consulta
-- para identificarlas (evidencias con contenido descriptivo probablemente son del usuario):
SELECT id, actividad_id, archivo, contenido, bloqueada, fecha_subida
FROM evidencias
WHERE bloqueada = 0 
  AND contenido IS NOT NULL 
  AND contenido != ''
ORDER BY fecha_subida DESC;

-- 3. Si quieres corregir todas las evidencias que tienen contenido/descripción
-- (normalmente los archivos de referencia del admin no tienen descripción):
-- UPDATE evidencias 
-- SET bloqueada = 1 
-- WHERE bloqueada = 0 
--   AND contenido IS NOT NULL 
--   AND contenido != '';

-- 4. También actualizar el estado de la actividad a completada
UPDATE actividades 
SET estado = 'completada',
    hora_evidencia = (SELECT fecha_subida FROM evidencias WHERE id = 86305)
WHERE id = 135386;

-- 5. Verificar el resultado
SELECT 
    e.id,
    e.actividad_id,
    e.tipo_evidencia,
    e.archivo,
    e.contenido,
    e.bloqueada,
    e.fecha_subida,
    a.titulo,
    a.estado
FROM evidencias e
JOIN actividades a ON e.actividad_id = a.id
WHERE e.actividad_id = 135386
ORDER BY e.fecha_subida;
