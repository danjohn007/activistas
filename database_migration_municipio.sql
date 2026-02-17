-- Migración: agregar campo municipio en usuarios

SET @db_name = DATABASE();
-- 1) Agregar columna municipio si no existe
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'municipio'
);

SET @sql_add_column = IF(
    @col_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN municipio VARCHAR(120) NULL AFTER direccion',
    'SELECT "Columna municipio ya existe" as info'
);
PREPARE stmt_add_column FROM @sql_add_column;
EXECUTE stmt_add_column;
DEALLOCATE PREPARE stmt_add_column;

-- 2) Crear índice en municipio si no existe
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'usuarios'
      AND INDEX_NAME = 'idx_usuarios_municipio'
);

SET @sql_add_index = IF(
    @idx_exists = 0,
    'CREATE INDEX idx_usuarios_municipio ON usuarios (municipio)',
    'SELECT "Índice idx_usuarios_municipio ya existe" as info'
);
PREPARE stmt_add_index FROM @sql_add_index;
EXECUTE stmt_add_index;
DEALLOCATE PREPARE stmt_add_index;

-- Nota: no se fuerza NOT NULL para mantener compatibilidad con usuarios existentes.
-- La obligatoriedad para Líder/Activista se controla desde la aplicación.
