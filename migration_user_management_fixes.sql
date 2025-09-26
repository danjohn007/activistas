-- Migration to add 'eliminado' status and support delete/suspend functionality
-- Generated for fixing user management and activity type issues

-- Add 'eliminado' status to users enum
ALTER TABLE usuarios MODIFY COLUMN estado ENUM('pendiente', 'activo', 'suspendido', 'desactivado', 'eliminado') DEFAULT 'pendiente';

-- Make sure grupo_id can be NULL for "Sin grupo específico" functionality
ALTER TABLE usuarios MODIFY COLUMN grupo_id INT NULL;

-- Add indexes for better performance on group operations
ALTER TABLE usuarios ADD INDEX idx_grupo_id (grupo_id);
ALTER TABLE usuarios ADD INDEX idx_lider_id_rol (lider_id, rol);

-- Update any existing users with empty grupo_id to NULL
UPDATE usuarios SET grupo_id = NULL WHERE grupo_id = '' OR grupo_id = 0;