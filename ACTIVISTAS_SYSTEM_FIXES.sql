-- SQL statements to support Activistas system fixes
-- These queries address the issues mentioned in the requirements

-- 1. Ensure grupos table exists and has proper structure
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

-- 2. Add grupo_id to usuarios table if it doesn't exist
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS grupo_id INT NULL AFTER lider_id,
ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL;

-- 3. Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_grupos_activo ON grupos(activo);
CREATE INDEX IF NOT EXISTS idx_grupos_lider ON grupos(lider_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_grupo ON usuarios(grupo_id);

-- 4. Remove duplicate activity types if they exist
-- This will help fix the duplicate entries issue
DELETE t1 FROM tipos_actividades t1
INNER JOIN tipos_actividades t2 
WHERE t1.id > t2.id 
AND t1.nombre = t2.nombre 
AND t1.activo = t2.activo;

-- 5. Insert default groups if they don't exist
INSERT IGNORE INTO grupos (nombre, descripcion, activo) VALUES 
('GeneracionesVa', 'Grupo principal de activistas de GeneracionesVa', 1),
('Grupo mujeres Lupita', 'Grupo enfocado en activismo femenino', 1),
('Grupo Herman', 'Grupo de activistas coordinado por Herman', 1),
('Grupo Anita', 'Grupo de activistas coordinado por Anita', 1);

-- 6. Trigger to automatically assign activists to group when leader is added
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS assign_activists_on_leader_group_change
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    -- When a leader is assigned to a group, assign their activists too
    IF OLD.grupo_id != NEW.grupo_id AND NEW.rol = 'Líder' AND NEW.grupo_id IS NOT NULL THEN
        UPDATE usuarios 
        SET grupo_id = NEW.grupo_id 
        WHERE lider_id = NEW.id AND rol = 'Activista' AND estado = 'activo';
    END IF;
END$$
DELIMITER ;

-- 7. Procedure to update group member counts (for reference)
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS UpdateGroupMemberCounts()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE grupo_id INT;
    DECLARE cur CURSOR FOR SELECT id FROM grupos WHERE activo = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO grupo_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- This is just for reference - member counts are calculated dynamically in queries
        -- UPDATE grupos SET miembros_count = (
        --     SELECT COUNT(*) FROM usuarios 
        --     WHERE grupo_id = grupo_id AND estado = 'activo'
        -- ) WHERE id = grupo_id;
        
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;