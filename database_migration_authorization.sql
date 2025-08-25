-- Migration to add authorization fields to activities table
-- This adds the new fields mentioned in the problem statement

-- Add new authorization fields to activities table
ALTER TABLE actividades 
ADD COLUMN propuesto_por INT NULL AFTER tarea_pendiente,
ADD COLUMN autorizado_por INT NULL AFTER propuesto_por,
ADD COLUMN autorizada TINYINT(1) DEFAULT 0 AFTER autorizado_por,
ADD COLUMN bonificacion_ranking INT DEFAULT 0 AFTER autorizada;

-- Add foreign key constraints for the new fields
ALTER TABLE actividades
ADD CONSTRAINT fk_actividades_propuesto_por FOREIGN KEY (propuesto_por) REFERENCES usuarios(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_actividades_autorizado_por FOREIGN KEY (autorizado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Create index for better performance on authorization queries
CREATE INDEX idx_actividades_autorizada ON actividades(autorizada);
CREATE INDEX idx_actividades_propuesto_por ON actividades(propuesto_por);