-- Migration: Add group field to users table
-- This migration adds support for group assignments to users
-- Date: Current implementation

-- Add grupo field to users table
ALTER TABLE usuarios ADD COLUMN grupo VARCHAR(255) NULL AFTER lider_id;

-- Add index for grupo field for better performance
CREATE INDEX idx_usuarios_grupo ON usuarios(grupo);

-- Update existing users to have NULL grupo (which is already the default)
-- No data update needed as the field allows NULL

-- Sample groups that could be used:
-- 'GeneracionesVa'
-- 'Grupo mujeres Lupita'  
-- 'Grupo Herman'
-- 'Grupo Anita'
-- Custom groups can be added as needed

-- Note: This allows users to belong to a group in addition to having a leader
-- Groups provide another level of organization for activities and reporting