-- Migration: Add vigencia_hasta field to usuarios table
-- Add vigencia field after estado column for user validity period

ALTER TABLE usuarios ADD COLUMN vigencia_hasta DATE NULL AFTER estado;

-- Add index for performance on vigencia queries
CREATE INDEX idx_usuarios_vigencia ON usuarios(vigencia_hasta);

-- Update comment for the table to document the new field
ALTER TABLE usuarios COMMENT = 'Tabla de usuarios con campo de vigencia para control de vencimiento de acceso';