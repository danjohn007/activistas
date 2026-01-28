-- Script para agregar índice único compuesto que previene duplicados a nivel de base de datos
-- Esto añade una capa adicional de protección contra duplicados

-- IMPORTANTE: Primero debes limpiar los duplicados existentes
-- Ejecuta remove_duplicates.sql ANTES de ejecutar este script

-- 1. VERIFICAR que no hay duplicados (debe retornar 0 filas)
SELECT 
    usuario_id,
    titulo,
    tipo_actividad_id,
    fecha_actividad,
    COUNT(*) as cantidad
FROM actividades
GROUP BY usuario_id, titulo, tipo_actividad_id, fecha_actividad
HAVING COUNT(*) > 1;

-- 2. Si el query anterior retorna 0 filas, proceder a crear el índice único
-- Este índice previene que se inserten actividades duplicadas a nivel de base de datos

-- OPCIÓN A: Índice único que previene duplicados exactos
-- Descomenta la siguiente línea cuando estés listo:
/*
CREATE UNIQUE INDEX idx_unique_activity 
ON actividades (usuario_id, titulo, tipo_actividad_id, fecha_actividad);
*/

-- OPCIÓN B: Si prefieres permitir duplicados en fechas diferentes pero mismo día
-- Descomenta esta alternativa (solo una de las dos opciones):
/*
CREATE UNIQUE INDEX idx_unique_activity_date 
ON actividades (usuario_id, titulo, tipo_actividad_id, DATE(fecha_actividad));
*/

-- 3. Verificar que el índice fue creado correctamente
/*
SHOW INDEX FROM actividades WHERE Key_name = 'idx_unique_activity';
*/

-- 4. Probar que funciona - Intenta insertar un duplicado (debe fallar con error 1062)
/*
-- Este INSERT debería fallar con error: Duplicate entry
INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad)
SELECT usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad
FROM actividades
LIMIT 1;
*/

-- NOTAS:
-- ✅ Con este índice único, incluso si hay un bug en el código PHP, la base de datos rechazará duplicados
-- ✅ El error será capturado por el try-catch en el modelo y registrado en el log
-- ⚠️ Si necesitas permitir múltiples actividades con el mismo título pero diferentes horas,
--    considera usar fecha_actividad + hora_publicacion en el índice
-- ⚠️ HAZBACKUP de tu base de datos antes de ejecutar este script

-- REVERTIR: Si necesitas eliminar el índice más tarde
/*
DROP INDEX idx_unique_activity ON actividades;
*/
