-- Migration: Add imagen field to actividades table for task visualization
-- Add imagen field to store activity associated image

ALTER TABLE actividades ADD COLUMN imagen VARCHAR(255) NULL AFTER descripcion;

-- Add index for performance on imagen queries
CREATE INDEX idx_actividades_imagen ON actividades(imagen);

-- Update comment for the table to document the new field
ALTER TABLE actividades COMMENT = 'Tabla de actividades con campo imagen para visualización en tareas pendientes';