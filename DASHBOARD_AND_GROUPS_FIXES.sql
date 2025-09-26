-- SQL Migration: Dashboard and Group Management Fixes
-- This migration ensures all necessary database structures are in place for the fixes

-- Ensure grupos table exists with proper structure
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

-- Add grupo_id to usuarios table if not exists
SET @sql_add_grupo_id = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'usuarios' 
     AND column_name = 'grupo_id' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE usuarios ADD COLUMN grupo_id INT NULL AFTER lider_id, ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL',
    'SELECT "Column grupo_id already exists" as msg'
));
PREPARE stmt FROM @sql_add_grupo_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_grupos_activo ON grupos(activo);
CREATE INDEX IF NOT EXISTS idx_grupos_lider ON grupos(lider_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_grupo ON usuarios(grupo_id);

-- Insert sample groups if table is empty
INSERT IGNORE INTO grupos (nombre, descripcion, activo) VALUES 
('GeneracionesVa', 'Grupo principal de activistas de GeneracionesVa', 1),
('Grupo mujeres Lupita', 'Grupo enfocado en activismo femenino', 1),
('Grupo Herman', 'Grupo de activistas coordinado por Herman', 1),
('Grupo Anita', 'Grupo de activistas coordinado por Anita', 1);

-- Ensure tipos_actividades table has proper data for dashboard charts
INSERT IGNORE INTO tipos_actividades (nombre, descripcion, activo) VALUES 
('Campaña de Redes Sociales', 'Publicaciones en redes sociales para difundir información', 1),
('Evento Presencial', 'Actividades presenciales como marchas, reuniones, etc.', 1),
('Capacitación', 'Sesiones de formación y capacitación', 1),
('Encuesta/Sondeo', 'Recolección de opiniones y datos', 1),
('Live/Transmisión', 'Transmisiones en vivo en redes sociales', 1),
('Volanteo', 'Distribución de material impreso', 1),
('Reunión de Equipo', 'Reuniones de coordinación y planificación', 1);

-- Verification queries to test the dashboard data
-- These queries match what the dashboard expects and can be used for testing

-- 1. Test activities by type (for dashboard chart)
SELECT ta.nombre, COUNT(a.id) as cantidad
FROM tipos_actividades ta
LEFT JOIN actividades a ON ta.id = a.tipo_actividad_id
GROUP BY ta.id, ta.nombre 
ORDER BY cantidad DESC;

-- 2. Test user stats by role (for dashboard chart)
SELECT rol, 
       COUNT(*) as total,
       COUNT(CASE WHEN estado = 'activo' THEN 1 END) as activos,
       COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
       COUNT(CASE WHEN estado = 'suspendido' THEN 1 END) as suspendidos
FROM usuarios 
GROUP BY rol;

-- 3. Test monthly activities (for dashboard chart)
SELECT 
    DATE_FORMAT(a.fecha_actividad, '%Y-%m') as mes,
    COUNT(*) as cantidad
FROM actividades a
JOIN usuarios u ON a.usuario_id = u.id
WHERE a.fecha_actividad >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(a.fecha_actividad, '%Y-%m') 
ORDER BY mes;

-- 4. Test team ranking (for dashboard chart)
SELECT 
    l.nombre_completo as lider_nombre,
    COUNT(a.id) as total_actividades,
    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas,
    COUNT(DISTINCT u.id) as miembros_equipo
FROM usuarios l
LEFT JOIN usuarios u ON l.id = u.lider_id
LEFT JOIN actividades a ON (u.id = a.usuario_id OR l.id = a.usuario_id)
WHERE l.rol = 'Líder' AND l.estado = 'activo'
GROUP BY l.id, l.nombre_completo
ORDER BY completadas DESC, total_actividades DESC
LIMIT 10;

-- 5. Test groups with member counts (for new group filtering)
SELECT g.*, 
       l.nombre_completo as lider_nombre,
       COUNT(u.id) as miembros_count
FROM grupos g
LEFT JOIN usuarios l ON g.lider_id = l.id
LEFT JOIN usuarios u ON u.grupo_id = g.id AND u.estado = 'activo'
WHERE g.activo = 1
GROUP BY g.id
ORDER BY g.nombre;

-- Comments for implementation notes:
-- This migration supports the following fixes:
-- 1. Dashboard charts: Ensures data structure exists for getUserStats, getActivitiesByType, etc.
-- 2. Group filtering: Provides grupo_id relationship for filtering activities by group
-- 3. User approval: Supports assigning users to groups during approval process
-- 4. Activity assignment: Enables sending activities to all members of selected groups