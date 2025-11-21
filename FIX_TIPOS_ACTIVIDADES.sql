-- =====================================================
-- SOLUCIÓN: Añadir tabla tipos_actividades
-- =====================================================
-- Este script crea la tabla tipos_actividades si no existe
-- y la puebla con los tipos básicos necesarios para las gráficas

-- 1. Crear tabla si no existe
CREATE TABLE IF NOT EXISTS `tipos_actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8_unicode_ci,
  `puntos` int(11) DEFAULT '10',
  `icono` varchar(50) COLLATE utf8_unicode_ci DEFAULT 'fa-tasks',
  `color` varchar(20) COLLATE utf8_unicode_ci DEFAULT '#667eea',
  `estado` enum('activo','inactivo') COLLATE utf8_unicode_ci DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
COMMENT='Tipos de actividades para clasificación y puntuación';

-- 2. Insertar tipos de actividades (REPLACE para evitar duplicados)
-- Basados en los datos encontrados en tu tabla actividades
REPLACE INTO tipos_actividades (id, nombre, descripcion, puntos, icono, color, estado) VALUES
(1, 'Publicaciones en Redes Sociales', 'Publicaciones en redes sociales para difundir información', 10, 'fa-share-alt', '#667eea', 'activo'),
(3, 'Dinámica Express', 'Actividades dinámicas rápidas y eventos comunitarios', 15, 'fa-bolt', '#f59e0b', 'activo'),
(5, 'Transmisiones en Vivo', 'Transmisiones en vivo en redes sociales', 20, 'fa-video', '#ef4444', 'activo'),
(8, 'Comentarios en Publicaciones', 'Comentar en publicaciones y dar likes para posicionar', 5, 'fa-comment', '#10b981', 'activo'),
(9, 'Subir Contenido', 'Subir contenido a historias y/o en formato publicación', 15, 'fa-cloud-upload-alt', '#3b82f6', 'activo'),
(11, 'Eventos', 'Participación en eventos presenciales y digitales', 25, 'fa-calendar', '#8b5cf6', 'activo');

-- 3. Actualizar actividades existentes que tengan tipo_actividad_id = NULL
-- (si las hay, asignarles el tipo más común)
UPDATE actividades 
SET tipo_actividad_id = 1 
WHERE tipo_actividad_id IS NULL OR tipo_actividad_id = 0;

-- 4. Verificar que todos los tipo_actividad_id en actividades existen
-- Listar actividades con tipos que no existen en tipos_actividades
SELECT DISTINCT a.tipo_actividad_id, COUNT(*) as cantidad
FROM actividades a
LEFT JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
WHERE ta.id IS NULL AND a.tipo_actividad_id IS NOT NULL
GROUP BY a.tipo_actividad_id;

-- 5. Verificación: Contar actividades por tipo
SELECT 
    ta.id,
    ta.nombre,
    ta.puntos,
    ta.estado,
    COUNT(a.id) as cantidad_actividades,
    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas,
    COUNT(CASE WHEN a.estado = 'programada' THEN 1 END) as programadas,
    COUNT(CASE WHEN a.estado = 'en_progreso' THEN 1 END) as en_progreso
FROM tipos_actividades ta
LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id
GROUP BY ta.id, ta.nombre, ta.puntos, ta.estado
ORDER BY cantidad_actividades DESC;

-- 6. Resultado esperado:
-- Deberías ver algo como:
-- +----+----------------------------------+--------+--------+------------------------+-------------+-------------+-------------+
-- | id | nombre                           | puntos | estado | cantidad_actividades   | completadas | programadas | en_progreso |
-- +----+----------------------------------+--------+--------+------------------------+-------------+-------------+-------------+
-- |  9 | Subir Contenido                  |     15 | activo |                    100 |          30 |          70 |           0 |
-- |  1 | Publicaciones en Redes Sociales  |     10 | activo |                     50 |          20 |          30 |           0 |
-- |  5 | Transmisiones en Vivo            |     20 | activo |                     10 |           8 |           2 |           0 |
-- +----+----------------------------------+--------+--------+------------------------+-------------+-------------+-------------+

-- =====================================================
-- NOTA IMPORTANTE:
-- =====================================================
-- Después de ejecutar este script:
-- 1. Las gráficas del dashboard deberían mostrarse correctamente
-- 2. Recarga la página del dashboard: https://fix360.app/ad/public/dashboards/admin.php
-- 3. Si no aparecen, presiona el botón "Actualizar Datos"
-- 4. Verifica la consola del navegador (F12) para ver si hay errores de JavaScript
