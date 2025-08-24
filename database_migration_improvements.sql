-- 1. Permitir imagen de perfil hasta 20MB
-- Actualiza el valor en la tabla de configuraciones
UPDATE configuraciones SET valor='20971520' WHERE clave='max_tamaño_archivo';

-- 2. Eliminar campos de lugar y alcance_estimado en actividades, agregar tarea_pendiente, solicitante_id y hora_evidencia
ALTER TABLE actividades
  DROP COLUMN lugar,
  DROP COLUMN alcance_estimado,
  ADD COLUMN tarea_pendiente TINYINT(1) DEFAULT 0 AFTER estado,
  ADD COLUMN solicitante_id INT DEFAULT NULL AFTER tarea_pendiente,
  ADD COLUMN hora_evidencia DATETIME AFTER solicitante_id;

-- 3. Evidencia: bloquear edición y registrar hora automática (ya tienes fecha_subida, solo agregamos bloqueada)
ALTER TABLE evidencias
  ADD COLUMN bloqueada TINYINT(1) DEFAULT 0 AFTER fecha_subida;

-- 4. Ranking en usuarios (agregar columna para guardar puntos)
ALTER TABLE usuarios
  ADD COLUMN ranking_puntos INT DEFAULT 0 AFTER cuenta_pago;

-- 5. Asegurar integridad referencial de nuevos campos (si solicitante_id viene de usuarios)
ALTER TABLE actividades
  ADD CONSTRAINT fk_actividades_solicitante_id FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- 6. Si quieres que todos los cambios sean atómicos:
START TRANSACTION;
-- (Coloca aquí todos los ALTER/UPDATE anteriores)
COMMIT;

-- NOTA: Si tienes vistas o procedimientos que usen los campos "lugar" o "alcance_estimado", actualízalos manualmente.
