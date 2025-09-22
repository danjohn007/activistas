-- Migration: Add groups field to activities table
-- This migration adds support for group assignments to activities

-- Add grupo field to activities table
ALTER TABLE actividades ADD COLUMN grupo VARCHAR(255) NULL AFTER lugar;

-- Add index for grupo field for better performance
CREATE INDEX idx_actividades_grupo ON actividades(grupo);

-- Update existing activities to have NULL grupo (which is already the default)
-- No data update needed as the field allows NULL

-- Sample groups that could be used:
-- 'GeneracionesVa'
-- 'Grupo mujeres Lupita'
-- 'Grupo Herman'
-- 'Grupo Anita'
-- Custom groups can be added as needed