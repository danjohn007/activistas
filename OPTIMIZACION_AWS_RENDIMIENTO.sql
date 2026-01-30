-- ============================================
-- OPTIMIZACIÓN DE RENDIMIENTO PARA AWS
-- Mejora la velocidad de carga y reduce timeouts
-- ============================================

-- 1. ÍNDICES CRÍTICOS PARA QUERIES MÁS RÁPIDOS
-- Estos índices aceleran las consultas más comunes
-- NOTA: Si ya existen, estos comandos no darán error

-- Índice compuesto para la query principal de actividades
CREATE INDEX IF NOT EXISTS idx_actividades_lookup 
ON actividades (usuario_id, fecha_actividad, fecha_creacion, estado, autorizada);

-- Índice para búsquedas por líder
CREATE INDEX IF NOT EXISTS idx_usuarios_lider 
ON usuarios (lider_id, id);

-- Índice para grupo (filtros de SuperAdmin)
CREATE INDEX IF NOT EXISTS idx_usuarios_grupo 
ON usuarios (grupo_id, id);

-- Índice para conteo de evidencias (usado en LEFT JOIN)
CREATE INDEX IF NOT EXISTS idx_evidencias_actividad 
ON evidencias (actividad_id);

-- Índice para tipos de actividad (usado en JOIN)
CREATE INDEX IF NOT EXISTS idx_tipos_activos 
ON tipos_actividades (id, nombre);

-- Índice para fecha de cierre (filtro de tareas vencidas)
CREATE INDEX IF NOT EXISTS idx_actividades_cierre 
ON actividades (fecha_cierre, hora_cierre);

-- Índice para fecha de publicación (filtro de visibilidad)
CREATE INDEX IF NOT EXISTS idx_actividades_publicacion 
ON actividades (fecha_publicacion, hora_publicacion);

-- 2. OPTIMIZACIÓN DE TABLAS
-- Desfragmentar y reorganizar datos
OPTIMIZE TABLE actividades;
OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE evidencias;
OPTIMIZE TABLE tipos_actividades;

-- 3. ANALIZAR TABLAS PARA MEJORAR PLANES DE EJECUCIÓN
ANALYZE TABLE actividades;
ANALYZE TABLE usuarios;
ANALYZE TABLE evidencias;
ANALYZE TABLE tipos_actividades;

-- 4. CONFIGURACIÓN DE MYSQL PARA MEJOR RENDIMIENTO
-- (Ejecutar solo si tienes acceso root a MySQL)
-- SET GLOBAL query_cache_size = 67108864; -- 64MB cache
-- SET GLOBAL query_cache_type = 1;
-- SET GLOBAL max_connections = 200; -- Más conexiones concurrentes
-- SET GLOBAL innodb_buffer_pool_size = 536870912; -- 512MB buffer pool
-- SET GLOBAL tmp_table_size = 67108864; -- 64MB
-- SET GLOBAL max_heap_table_size = 67108864; -- 64MB

-- 5. VERIFICAR ÍNDICES CREADOS
SHOW INDEX FROM actividades;
SHOW INDEX FROM usuarios;
SHOW INDEX FROM evidencias;

-- 6. REVISAR QUERIES LENTAS (Diagnóstico)
-- Habilitar log de queries lentas para identificar cuellos de botella
-- SET GLOBAL slow_query_log = 1;
-- SET GLOBAL long_query_time = 2; -- Queries > 2 segundos

-- 7. ESTADÍSTICAS DE TABLAS
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    AVG_ROW_LENGTH,
    DATA_LENGTH / 1024 / 1024 AS data_mb,
    INDEX_LENGTH / 1024 / 1024 AS index_mb
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'ejercito_activistas'
ORDER BY DATA_LENGTH DESC;

-- ============================================
-- NOTAS IMPORTANTES:
-- - Ejecuta primero la sección 1 (ÍNDICES)
-- - Luego sección 2 y 3 (OPTIMIZE/ANALYZE)
-- - La sección 4 requiere permisos SUPER
-- - Si ya existen los índices, verás un warning (puedes ignorarlo)
-- ============================================
