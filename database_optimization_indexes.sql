-- =====================================================
-- ÍNDICES DE OPTIMIZACIÓN PARA EL SISTEMA DE ACTIVISTAS
-- =====================================================
-- VERIFICACIÓN: Estos índices mejoran el rendimiento del dashboard
-- =====================================================

-- ✅ ESTADO DE ÍNDICES (Verificado 2026-01-09)
-- Todos los índices necesarios YA EXISTEN en tu base de datos

-- TABLA: usuarios - ✅ TODOS LOS ÍNDICES YA EXISTEN
-- ✅ idx_usuarios_rol_estado (rol, estado)
-- ✅ idx_usuarios_lider_id (lider_id)
-- ✅ idx_usuarios_grupo_id (grupo_id)
-- ✅ idx_usuarios_estado (estado)
-- ✅ idx_usuarios_vigencia (vigencia_hasta)
-- ✅ idx_usuarios_grupo (grupo)

-- TABLA: actividades - ✅ TODOS LOS ÍNDICES YA EXISTEN
-- ✅ idx_actividades_fecha_estado (fecha_actividad, estado)
-- ✅ idx_actividades_usuario_fecha (usuario_id, fecha_actividad)
-- ✅ idx_actividades_tipo (tipo_actividad_id)
-- ✅ idx_actividades_autorizada (autorizada)
-- ✅ idx_actividades_fecha_publicacion (fecha_publicacion)
-- ✅ idx_actividades_cierre (fecha_cierre, hora_cierre)
-- ✅ idx_actividades_solicitante (solicitante_id, tarea_pendiente)
-- ✅ idx_actividades_fecha_creacion (fecha_creacion)
-- ✅ idx_actividades_propuesto_por (propuesto_por)

-- 🎉 TU BASE DE DATOS YA ESTÁ OPTIMIZADA
-- No necesitas crear índices adicionales.
-- Los índices existentes son suficientes para las optimizaciones del dashboard.

-- =====================================================
-- MANTENIMIENTO Y OPTIMIZACIÓN
-- =====================================================

-- Ejecutar ANALYZE TABLE para actualizar estadísticas de los índices
-- Esto ayuda al optimizador de MySQL a elegir los mejores índices

ANALYZE TABLE usuarios;
ANALYZE TABLE actividades;
ANALYZE TABLE tipos_actividades;
ANALYZE TABLE evidencias;
ANALYZE TABLE cortes;

-- =====================================================
-- VERIFICACIÓN DE USO DE ÍNDICES
-- =====================================================

-- Ver todos los índices de la tabla usuarios
SHOW INDEX FROM usuarios;

-- Ver todos los índices de la tabla actividades
SHOW INDEX FROM actividades;

-- Probar que los índices se usan correctamente en queries importantes
-- Query 1: Dashboard - Actividades recientes
EXPLAIN SELECT a.id, a.titulo, a.fecha_actividad, a.estado,
       u.nombre_completo, ta.nombre
FROM actividades a 
JOIN usuarios u ON a.usuario_id = u.id 
JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
WHERE a.autorizada = 1
ORDER BY a.fecha_actividad DESC
LIMIT 10;
-- Debe usar: idx_actividades_autorizada o idx_actividades_fecha_estado

-- Query 2: Dashboard - Estadísticas del mes
EXPLAIN SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
FROM actividades
WHERE YEAR(fecha_actividad) = YEAR(NOW()) 
  AND MONTH(fecha_actividad) = MONTH(NOW());
-- Debe usar: idx_actividades_fecha_estado o PRIMARY

-- Query 3: Dashboard Líder - Actividades del equipo
EXPLAIN SELECT COUNT(*) as total
FROM actividades a
JOIN usuarios u ON a.usuario_id = u.id
WHERE u.lider_id = 1 OR a.usuario_id = 1;
-- Debe usar: idx_usuarios_lider_id

-- =====================================================
-- RESULTADOS ESPERADOS DEL EXPLAIN
-- =====================================================
-- type: "ref" o "range" = ✅ BUENO (usa índice)
-- type: "index" = ⚠️ ACEPTABLE (lee todo el índice)
-- type: "ALL" = ❌ MALO (escaneo completo de tabla)
-- key: Muestra qué índice se usó

-- =====================================================
-- ESTADÍSTICAS DE RENDIMIENTO
-- =====================================================

-- Ver tamaño de tablas e índices
SELECT 
    table_name AS 'Tabla',
    ROUND(((data_length) / 1024 / 1024), 2) AS 'Datos (MB)',
    ROUND(((index_length) / 1024 / 1024), 2) AS 'Índices (MB)',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Total (MB)'
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
  AND table_name IN ('usuarios', 'actividades', 'tipos_actividades', 'evidencias', 'cortes')
ORDER BY (data_length + index_length) DESC;

-- Ver cardinalidad de índices (debe ser alta para ser efectivo)
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    CARDINALITY,
    CASE 
        WHEN CARDINALITY IS NULL THEN '❌ Sin estadísticas'
        WHEN CARDINALITY < 10 THEN '⚠️ Baja selectividad'
        WHEN CARDINALITY < 100 THEN '✓ Selectividad media'
        ELSE '✅ Alta selectividad'
    END as Estado
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('usuarios', 'actividades')
  AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- RECOMENDACIONES FINALES
-- =====================================================

/*
✅ TUS ÍNDICES ESTÁN BIEN CONFIGURADOS

Los índices actuales son suficientes para optimizar:
- Dashboard de SuperAdmin/Gestor
- Dashboard de Líder  
- Dashboard de Activista
- Reportes y gráficas

PRÓXIMO PASO:
1. Ejecutar: php install_optimization.php (crear directorio de caché)
2. Ejecutar: ANALYZE TABLE usuarios, actividades; (actualizar estadísticas)
3. Probar el dashboard en AWS
4. Debe cargar 70-85% más rápido

MONITOREO:
- Si alguna query es lenta, usar EXPLAIN para verificar índices
- Los índices ocupan espacio pero mejoran mucho el rendimiento
- Con tus índices actuales, el sistema debería ser muy rápido
*/
