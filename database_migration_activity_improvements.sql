-- Migration: Activity System Improvements
-- This migration implements the required changes for:
-- 1. Activity expiry (vigencia) with date and time
-- 2. Monthly ranking system with history
-- 3. Updated point calculation system

-- 1. Add activity expiry fields
ALTER TABLE actividades 
ADD COLUMN fecha_cierre DATE NULL AFTER fecha_actividad,
ADD COLUMN hora_cierre TIME NULL AFTER fecha_cierre;

-- Add index for performance on expiry queries
CREATE INDEX idx_actividades_cierre ON actividades(fecha_cierre, hora_cierre);

-- 2. Create monthly rankings table for ranking history
CREATE TABLE rankings_mensuales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    anio INT NOT NULL,
    mes INT NOT NULL,
    puntos INT NOT NULL DEFAULT 0,
    posicion INT NOT NULL DEFAULT 0,
    actividades_completadas INT NOT NULL DEFAULT 0,
    porcentaje_cumplimiento DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (usuario_id, anio, mes)
);

-- Add index for efficient monthly ranking queries
CREATE INDEX idx_rankings_mensuales_fecha ON rankings_mensuales(anio, mes);
CREATE INDEX idx_rankings_mensuales_puntos ON rankings_mensuales(anio, mes, puntos DESC);

-- 3. Update ranking points to reflect the new system (1000 + total users)
-- This will be handled in the application code, but we'll reset current points
-- to start fresh with the new calculation system
UPDATE usuarios SET ranking_puntos = 0 WHERE id != 1;

-- 4. Add comments to document the new fields
ALTER TABLE actividades COMMENT = 'Tabla de actividades con campos de vigencia (fecha_cierre, hora_cierre) para control de expiración automática';
ALTER TABLE rankings_mensuales COMMENT = 'Tabla de rankings mensuales para mantener historial de puntuaciones por mes y año';

-- Optional: Add some sample data for testing (remove in production)
-- You can uncomment these lines if you want test data
-- INSERT INTO rankings_mensuales (usuario_id, anio, mes, puntos, posicion, actividades_completadas, porcentaje_cumplimiento) 
-- SELECT id, YEAR(CURDATE()), MONTH(CURDATE()) - 1, ranking_puntos, 0, 0, 0.00 
-- FROM usuarios WHERE id != 1 AND estado = 'activo';