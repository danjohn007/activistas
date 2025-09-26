-- Migration: Complete groups system implementation
-- This migration creates the groups table and updates existing tables

-- Create grupos table
CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    lider_id INT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lider_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Add grupo_id to usuarios table (if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'usuarios' 
     AND column_name = 'grupo_id' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE usuarios ADD COLUMN grupo_id INT NULL AFTER lider_id, ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL',
    'SELECT "Column grupo_id already exists" as msg'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add video file size support (50MB limit) to actividades table if not exists
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'actividades' 
     AND column_name = 'permite_videos' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE actividades ADD COLUMN permite_videos TINYINT(1) DEFAULT 1',
    'SELECT "Column permite_videos already exists" as msg'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_grupos_activo ON grupos(activo);
CREATE INDEX IF NOT EXISTS idx_grupos_lider ON grupos(lider_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_grupo ON usuarios(grupo_id);

-- Insert default groups (optional - can be customized)
INSERT IGNORE INTO grupos (nombre, descripcion, activo) VALUES 
('GeneracionesVa', 'Grupo principal de activistas de GeneracionesVa', 1),
('Grupo mujeres Lupita', 'Grupo enfocado en activismo femenino', 1),
('Grupo Herman', 'Grupo de activistas coordinado por Herman', 1),
('Grupo Anita', 'Grupo de activistas coordinado por Anita', 1);

-- Update configuration for video file size (50MB = 52428800 bytes)
INSERT INTO configuraciones (clave, valor, descripcion) 
VALUES ('max_video_size', '52428800', 'Tamaño máximo de videos en bytes (50MB)')
ON DUPLICATE KEY UPDATE valor = '52428800';