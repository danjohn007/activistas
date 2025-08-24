-- Database migration for activity system improvements
-- Implements the requirements for pending tasks, evidence blocking, and ranking system

-- 1. Update usuarios table for profile image and ranking
ALTER TABLE usuarios MODIFY COLUMN foto_perfil VARCHAR(255);
ALTER TABLE usuarios ADD COLUMN ranking_puntos INT DEFAULT 0;

-- 2. Update actividades table - remove lugar and alcance_estimado, add new fields
ALTER TABLE actividades 
ADD COLUMN hora_evidencia DATETIME NULL,
ADD COLUMN tarea_pendiente TINYINT(1) DEFAULT 0,
ADD COLUMN solicitante_id INT DEFAULT NULL,
DROP COLUMN lugar,
DROP COLUMN alcance_estimado;

-- Add foreign key constraint for solicitante_id
ALTER TABLE actividades 
ADD CONSTRAINT fk_actividades_solicitante 
FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- 3. Update evidencias table for blocking and timestamp tracking
ALTER TABLE evidencias 
ADD COLUMN fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN bloqueada TINYINT(1) DEFAULT 0;

-- Update existing evidencias to set fecha_subida if NULL
UPDATE evidencias SET fecha_subida = CURRENT_TIMESTAMP WHERE fecha_subida IS NULL;

-- 4. Create index for better performance on ranking queries
CREATE INDEX idx_usuarios_ranking ON usuarios(ranking_puntos DESC);
CREATE INDEX idx_actividades_tarea_pendiente ON actividades(tarea_pendiente, solicitante_id);
CREATE INDEX idx_evidencias_fecha_subida ON evidencias(fecha_subida);

-- 5. Insert configuration for new features
INSERT INTO configuraciones (clave, valor, descripcion) VALUES 
('max_tamaño_archivo_perfil', '20971520', 'Tamaño máximo de archivos de perfil en bytes (20MB)'),
('ranking_puntos_tarea_completada', '200', 'Puntos por tarea completada'),
('ranking_puntos_mejor_tiempo', '800', 'Puntos máximos por mejor tiempo de respuesta')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);