-- Migración: Agregar campos para enlaces de redes sociales (enlace_3 y enlace_4)
-- Fecha: 5 de enero de 2026
-- Descripción: Agrega dos campos adicionales para enlaces de TikTok y X (Twitter)

-- Agregar columnas enlace_3 y enlace_4
ALTER TABLE actividades
ADD COLUMN enlace_3 VARCHAR(500) NULL AFTER enlace_2,
ADD COLUMN enlace_4 VARCHAR(500) NULL AFTER enlace_3;

-- Actualizar comentario de la tabla
ALTER TABLE actividades COMMENT = 'Tabla de actividades con campos de enlaces para redes sociales (enlace_1: Facebook, enlace_2: Instagram, enlace_3: TikTok, enlace_4: X/Twitter)';

-- Verificar cambios
DESCRIBE actividades;
