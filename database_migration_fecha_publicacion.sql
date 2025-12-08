-- Migration: Agregar campo fecha_publicacion a tabla actividades
-- Descripción: Este campo permite programar la publicación de tareas para una fecha específica
-- Fecha: 2025-12-05

USE fix360_ad;

-- Agregar columna fecha_publicacion
ALTER TABLE actividades
ADD COLUMN fecha_publicacion DATETIME NULL DEFAULT NULL AFTER fecha_creacion,
ADD COLUMN hora_publicacion TIME NULL DEFAULT NULL AFTER fecha_publicacion;

-- Para las actividades existentes, establecer fecha de publicación igual a fecha de creación
-- (para que aparezcan inmediatamente como antes)
UPDATE actividades 
SET fecha_publicacion = fecha_creacion 
WHERE fecha_publicacion IS NULL;

-- Crear índice para optimizar consultas por fecha de publicación
CREATE INDEX idx_actividades_publicacion ON actividades(fecha_publicacion, hora_publicacion);

-- Comentario de la tabla actualizada
ALTER TABLE actividades COMMENT = 'Tabla de actividades con campos de programación (fecha_publicacion) y vigencia (fecha_cierre)';

-- Listo! Los campos han sido agregados exitosamente.
