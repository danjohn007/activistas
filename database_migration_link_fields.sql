-- Migration: Add optional activity link fields
-- This migration adds two optional link fields to activities

-- Add two optional link fields to activities table
ALTER TABLE actividades 
ADD COLUMN enlace_1 VARCHAR(500) NULL AFTER descripcion,
ADD COLUMN enlace_2 VARCHAR(500) NULL AFTER enlace_1;

-- Add comments to document the new fields
ALTER TABLE actividades COMMENT = 'Tabla de actividades con campos opcionales de enlaces (enlace_1, enlace_2) para mostrar links relacionados';