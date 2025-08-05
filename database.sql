CREATE DATABASE IF NOT EXISTS fix360_ad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE fix360_ad;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    foto_perfil VARCHAR(255),
    password_hash VARCHAR(255) NOT NULL,
    direccion TEXT NOT NULL,
    rol ENUM('SuperAdmin', 'Gestor', 'Líder', 'Activista') NOT NULL,
    lider_id INT NULL,
    estado ENUM('pendiente', 'activo', 'suspendido', 'desactivado') DEFAULT 'pendiente',
    email_verificado BOOLEAN DEFAULT FALSE,
    token_verificacion VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lider_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de tipos de actividades
CREATE TABLE tipos_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de actividades
CREATE TABLE actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_actividad_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_actividad DATE NOT NULL,
    lugar VARCHAR(255),
    alcance_estimado INT DEFAULT 0,
    estado ENUM('programada', 'en_progreso', 'completada', 'cancelada') DEFAULT 'programada',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_actividad_id) REFERENCES tipos_actividades(id)
);

-- Tabla de evidencias
CREATE TABLE evidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    tipo_evidencia ENUM('foto', 'video', 'audio', 'comentario', 'live') NOT NULL,
    archivo VARCHAR(255),
    contenido TEXT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
);

-- Tabla de configuraciones del sistema
CREATE TABLE configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descripcion TEXT,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de notificaciones
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Insertar usuario SuperAdmin por defecto
INSERT INTO usuarios (nombre_completo, telefono, email, password_hash, direccion, rol, estado, email_verificado) 
VALUES ('Administrador del Sistema', '0000000000', 'admin@activistas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Oficina Central', 'SuperAdmin', 'activo', TRUE);

-- Insertar tipos de actividades por defecto
INSERT INTO tipos_actividades (nombre, descripcion) VALUES 
('Campaña de Redes Sociales', 'Publicaciones en redes sociales para difundir información'),
('Evento Presencial', 'Actividades presenciales como marchas, reuniones, etc.'),
('Capacitación', 'Sesiones de formación y capacitación'),
('Encuesta/Sondeo', 'Recolección de opiniones y datos'),
('Live/Transmisión', 'Transmisiones en vivo en redes sociales'),
('Volanteo', 'Distribución de material impreso'),
('Reunión de Equipo', 'Reuniones de coordinación y planificación');

-- Insertar configuraciones por defecto
INSERT INTO configuraciones (clave, valor, descripcion) VALUES 
('sistema_nombre', 'Sistema de Activistas Digitales', 'Nombre del sistema'),
('email_verificacion_requerida', '0', 'Si requiere verificación de email (1=Sí, 0=No)'),
('max_tamaño_archivo', '5242880', 'Tamaño máximo de archivos en bytes (5MB)'),
('formatos_imagen_permitidos', 'jpg,jpeg,png,gif', 'Formatos de imagen permitidos'),
('zona_horaria', 'America/Mexico_City', 'Zona horaria del sistema');

-- =====================================
-- DATOS DE EJEMPLO EXTENSIVOS
-- =====================================

-- Insertar usuarios de ejemplo (adicionales al SuperAdmin)
INSERT INTO usuarios (nombre_completo, telefono, email, password_hash, direccion, rol, lider_id, estado, email_verificado, fecha_registro) VALUES 
-- Gestores
('María Elena Rodríguez', '+52-555-0101', 'maria.rodriguez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Reforma 123, Ciudad de México', 'Gestor', NULL, 'activo', TRUE, '2024-01-15 08:00:00'),
('Carlos Antonio Mendoza', '+52-555-0102', 'carlos.mendoza@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Blvd. Constitución 456, Guadalajara, Jalisco', 'Gestor', NULL, 'activo', TRUE, '2024-01-20 09:30:00'),

-- Líderes
('Ana Isabel Morales', '+52-555-0201', 'ana.morales@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Calle Hidalgo 789, Monterrey, Nuevo León', 'Líder', NULL, 'activo', TRUE, '2024-02-01 10:15:00'),
('Roberto Jiménez Castro', '+52-555-0202', 'roberto.jimenez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Universidad 321, Puebla, Puebla', 'Líder', NULL, 'activo', TRUE, '2024-02-05 11:00:00'),
('Luisa Fernanda García', '+52-555-0203', 'luisa.garcia@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Calle 60 No. 489, Mérida, Yucatán', 'Líder', NULL, 'activo', TRUE, '2024-02-10 14:30:00'),
('Diego Alejandro Ruiz', '+52-555-0204', 'diego.ruiz@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Revolución 654, Tijuana, Baja California', 'Líder', NULL, 'activo', TRUE, '2024-02-15 16:45:00'),

-- Activistas del equipo de Ana Morales (ID 3)
('Pedro González Vega', '+52-555-0301', 'pedro.gonzalez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Centro, Monterrey, NL', 'Activista', 3, 'activo', TRUE, '2024-02-20 08:00:00'),
('Sandra López Martínez', '+52-555-0302', 'sandra.lopez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. San Pedro, Monterrey, NL', 'Activista', 3, 'activo', TRUE, '2024-02-22 09:15:00'),
('Miguel Ángel Torres', '+52-555-0303', 'miguel.torres@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Mitras, Monterrey, NL', 'Activista', 3, 'activo', TRUE, '2024-02-25 10:30:00'),

-- Activistas del equipo de Roberto Jiménez (ID 4)
('Carmen Elena Flores', '+52-555-0401', 'carmen.flores@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Centro Histórico, Puebla, PUE', 'Activista', 4, 'activo', TRUE, '2024-03-01 11:00:00'),
('Javier Hernández Soto', '+52-555-0402', 'javier.hernandez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. La Paz, Puebla, PUE', 'Activista', 4, 'activo', TRUE, '2024-03-03 12:15:00'),
('Gabriela Ramírez Luna', '+52-555-0403', 'gabriela.ramirez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Angelópolis, Puebla, PUE', 'Activista', 4, 'activo', TRUE, '2024-03-05 13:30:00'),
('Eduardo Martín Vargas', '+52-555-0404', 'eduardo.vargas@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. San Manuel, Puebla, PUE', 'Activista', 4, 'activo', TRUE, '2024-03-07 14:45:00'),

-- Activistas del equipo de Luisa García (ID 5)
('Alejandra Cruz Medina', '+52-555-0501', 'alejandra.cruz@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. García Ginerés, Mérida, YUC', 'Activista', 5, 'activo', TRUE, '2024-03-10 15:00:00'),
('Fernando Castro Peña', '+52-555-0502', 'fernando.castro@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Itzimná, Mérida, YUC', 'Activista', 5, 'activo', TRUE, '2024-03-12 16:15:00'),
('Paola Stephanie Aguilar', '+52-555-0503', 'paola.aguilar@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Campestre, Mérida, YUC', 'Activista', 5, 'activo', TRUE, '2024-03-15 08:30:00'),

-- Activistas del equipo de Diego Ruiz (ID 6)
('Arturo Moreno Silva', '+52-555-0601', 'arturo.moreno@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Zona Centro, Tijuana, BC', 'Activista', 6, 'activo', TRUE, '2024-03-18 09:45:00'),
('Beatriz Alejandra Ramos', '+52-555-0602', 'beatriz.ramos@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Cacho, Tijuana, BC', 'Activista', 6, 'activo', TRUE, '2024-03-20 10:00:00'),
('Daniel Robles Martín', '+52-555-0603', 'daniel.robles@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Hipódromo, Tijuana, BC', 'Activista', 6, 'activo', TRUE, '2024-03-22 11:15:00'),

-- Usuarios pendientes de activación
('Sofia Mendez Torres', '+52-555-0701', 'sofia.mendez@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Roma Norte, Ciudad de México', 'Líder', NULL, 'pendiente', FALSE, '2024-03-25 12:00:00'),
('Ricardo Delgado Ponce', '+52-555-0702', 'ricardo.delgado@fix360.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Col. Condesa, Ciudad de México', 'Activista', 3, 'pendiente', FALSE, '2024-03-27 13:30:00');

-- Actividades de ejemplo
INSERT INTO actividades (usuario_id, tipo_actividad_id, titulo, descripcion, fecha_actividad, lugar, alcance_estimado, estado, fecha_creacion) VALUES
-- Actividades de Ana Morales (Líder)
(3, 1, 'Campaña de Facebook sobre reforma electoral', 'Publicación de contenido educativo sobre los beneficios de la reforma electoral en páginas de Facebook', '2024-03-01', 'Facebook - Páginas locales de Monterrey', 2500, 'completada', '2024-02-25 10:00:00'),
(3, 2, 'Reunión comunitaria en Colonia Centro', 'Asamblea informativa sobre propuestas de candidatos locales', '2024-03-15', 'Salón Comunal Col. Centro, Monterrey', 150, 'completada', '2024-03-10 14:00:00'),
(3, 3, 'Capacitación sobre participación ciudadana', 'Taller para activistas sobre derechos y participación política', '2024-04-05', 'Centro Comunitario Mitras', 40, 'programada', '2024-03-20 09:00:00'),

-- Actividades de Pedro González (Activista)
(7, 1, 'Posts de Instagram sobre transparencia', 'Serie de publicaciones sobre la importancia de la transparencia gubernamental', '2024-03-20', 'Instagram @pedrogonzalezmty', 800, 'completada', '2024-03-18 15:30:00'),
(7, 6, 'Volanteo en metro Cuauhtémoc', 'Distribución de material informativo sobre candidatos', '2024-03-25', 'Estación Cuauhtémoc, Metro Monterrey', 500, 'completada', '2024-03-23 08:00:00'),
(7, 4, 'Encuesta sobre seguridad pública', 'Sondeo de opinión sobre problemas de seguridad en la colonia', '2024-04-01', 'Col. Centro, Monterrey', 200, 'en_progreso', '2024-03-28 16:00:00'),

-- Actividades de Roberto Jiménez (Líder)
(4, 2, 'Foro ciudadano sobre educación', 'Panel de discusión con candidatos sobre propuestas educativas', '2024-03-10', 'Auditorio BUAP, Puebla', 300, 'completada', '2024-03-05 11:00:00'),
(4, 5, 'Live de Facebook sobre debate', 'Transmisión en vivo comentando el debate gubernamental', '2024-03-18', 'Facebook Live desde oficina', 1200, 'completada', '2024-03-18 20:00:00'),
(4, 7, 'Reunión de coordinación de equipo', 'Planificación de actividades de abril con activistas', '2024-03-30', 'Café Central, Puebla', 8, 'programada', '2024-03-25 10:00:00'),

-- Actividades de Carmen Flores (Activista)
(10, 1, 'Campaña TikTok #VotaInformado', 'Videos cortos sobre la importancia del voto informado', '2024-03-22', 'TikTok @carmenflores_pue', 1500, 'completada', '2024-03-20 12:00:00'),
(10, 6, 'Volanteo en Universidad', 'Distribución de material en Ciudad Universitaria BUAP', '2024-03-28', 'BUAP, Puebla', 400, 'en_progreso', '2024-03-26 07:30:00'),

-- Actividades de Luisa García (Líder)
(5, 3, 'Taller de liderazgo femenino', 'Capacitación para mujeres interesadas en participación política', '2024-03-12', 'Casa de la Cultura, Mérida', 60, 'completada', '2024-03-08 09:00:00'),
(5, 1, 'Campaña WhatsApp contra desinformación', 'Mensajes educativos sobre cómo identificar noticias falsas', '2024-03-25', 'Grupos de WhatsApp de Mérida', 3000, 'completada', '2024-03-23 18:00:00'),

-- Actividades de Alejandra Cruz (Activista)
(14, 4, 'Encuesta sobre transporte público', 'Sondeo sobre calidad del transporte en Mérida', '2024-03-29', 'Parque de las Américas, Mérida', 150, 'en_progreso', '2024-03-27 14:00:00'),
(14, 2, 'Evento de registro de votantes', 'Jornada para ayudar a ciudadanos a obtener credencial de elector', '2024-04-08', 'Plaza Grande, Mérida', 80, 'programada', '2024-04-01 10:00:00'),

-- Actividades de Diego Ruiz (Líder)
(6, 2, 'Mesa redonda fronteriza', 'Discusión sobre políticas migratorias con candidatos', '2024-03-14', 'Centro Cultural Tijuana', 200, 'completada', '2024-03-10 16:00:00'),
(6, 5, 'Live Instagram sobre economía local', 'Transmisión sobre impacto económico de políticas propuestas', '2024-03-26', 'Instagram @diegoruiz_tijuana', 900, 'completada', '2024-03-26 19:00:00'),

-- Actividades de Arturo Moreno (Activista)
(17, 6, 'Volanteo en frontera', 'Información sobre proceso electoral para trabajadores fronterizos', '2024-03-31', 'Garita San Ysidro, Tijuana', 600, 'programada', '2024-03-28 06:00:00'),
(17, 1, 'Posts de Twitter sobre comercio', 'Hilos de Twitter sobre impacto del comercio fronterizo', '2024-03-24', 'Twitter @arturomoreno_tj', 450, 'completada', '2024-03-22 11:00:00');

-- Evidencias de ejemplo
INSERT INTO evidencias (actividad_id, tipo_evidencia, archivo, contenido, fecha_subida) VALUES
-- Evidencias para campaña de Facebook de Ana Morales
(1, 'foto', 'facebook_post_reforma_electoral.jpg', 'Captura de pantalla del post con 342 likes y 89 comentarios', '2024-03-01 14:30:00'),
(1, 'comentario', NULL, 'Post alcanzó 2,847 personas, generó 156 interacciones y 23 compartidos. Comentarios mayormente positivos sobre la información compartida.', '2024-03-01 18:45:00'),

-- Evidencias para reunión comunitaria de Ana Morales
(2, 'foto', 'reunion_comunitaria_centro.jpg', 'Foto del salón lleno con aproximadamente 120 asistentes', '2024-03-15 19:30:00'),
(2, 'comentario', NULL, 'Excelente participación. Se registraron 118 asistentes, se respondieron 45 preguntas y se entregaron 89 folletos informativos.', '2024-03-15 21:00:00'),

-- Evidencias para posts de Instagram de Pedro González
(4, 'foto', 'instagram_transparencia_1.jpg', 'Screenshot del primer post sobre transparencia - 156 likes', '2024-03-20 16:00:00'),
(4, 'foto', 'instagram_transparencia_2.jpg', 'Screenshot del segundo post - 203 likes, 34 comentarios', '2024-03-20 18:00:00'),
(4, 'comentario', NULL, 'Serie de 5 posts publicados. Total: 734 likes, 89 comentarios, 45 compartidos. Alcance orgánico de 1,247 cuentas.', '2024-03-20 20:15:00'),

-- Evidencias para volanteo de Pedro González
(5, 'foto', 'volanteo_metro_cuauhtemoc.jpg', 'Foto del equipo distribuyendo volantes en la estación', '2024-03-25 10:30:00'),
(5, 'comentario', NULL, 'Se distribuyeron 487 volantes en 3 horas. Muy buena recepción de los ciudadanos, varias personas pidieron información adicional.', '2024-03-25 13:45:00'),

-- Evidencias para foro de Roberto Jiménez
(7, 'video', 'foro_educacion_buap.mp4', 'Video resumen del foro (15 minutos)', '2024-03-10 22:00:00'),
(7, 'foto', 'foro_educacion_panoramica.jpg', 'Vista panorámica del auditorio lleno', '2024-03-10 20:30:00'),
(7, 'comentario', NULL, 'Participaron 4 candidatos, 267 asistentes registrados. Se trataron temas de infraestructura educativa, becas y tecnología en aulas.', '2024-03-10 23:15:00'),

-- Evidencias para Live de Facebook de Roberto Jiménez
(8, 'live', 'facebook_live_debate.mp4', 'Grabación del live de 1 hora 23 minutos', '2024-03-18 21:30:00'),
(8, 'comentario', NULL, 'Live alcanzó 1,456 espectadores simultáneos máximo, 127 comentarios durante la transmisión, 89 reacciones.', '2024-03-18 22:00:00'),

-- Evidencias para TikTok de Carmen Flores
(10, 'video', 'tiktok_vota_informado_1.mp4', 'Primer video de la serie - 892 views', '2024-03-22 15:00:00'),
(10, 'video', 'tiktok_vota_informado_2.mp4', 'Segundo video - 1,203 views, 67 likes', '2024-03-22 18:00:00'),
(10, 'comentario', NULL, 'Serie de 6 videos publicados. Total acumulado: 4,567 views, 234 likes, 89 compartidos, 45 comentarios positivos.', '2024-03-22 21:30:00'),

-- Evidencias para taller de Luisa García
(12, 'foto', 'taller_liderazgo_femenino.jpg', 'Grupo de participantes en sesión de trabajo', '2024-03-12 16:45:00'),
(12, 'comentario', NULL, 'Participaron 52 mujeres de diferentes colonias. Se entregaron certificados de participación y material de seguimiento.', '2024-03-12 18:30:00'),

-- Evidencias para campaña WhatsApp de Luisa García
(13, 'foto', 'whatsapp_campaign_screenshot.jpg', 'Captura de mensajes enviados en grupos', '2024-03-25 20:00:00'),
(13, 'comentario', NULL, 'Mensajes enviados a 47 grupos de WhatsApp con promedio de 65 miembros cada uno. Excelente recepción y reenvío.', '2024-03-25 21:15:00');

-- Notificaciones de ejemplo
INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, leida, fecha_creacion) VALUES
-- Notificaciones para Ana Morales
(3, 'Actividad completada exitosamente', 'Tu campaña de Facebook sobre reforma electoral ha sido marcada como completada con excelentes resultados.', 'success', TRUE, '2024-03-01 19:00:00'),
(3, 'Nueva capacitación programada', 'Recordatorio: Capacitación sobre participación ciudadana programada para el 5 de abril.', 'info', FALSE, '2024-03-30 09:00:00'),
(3, 'Felicitaciones por liderazgo', 'Tu equipo ha mostrado excelente desempeño este mes. ¡Sigue así!', 'success', FALSE, '2024-03-28 14:30:00'),

-- Notificaciones para Pedro González
(7, 'Evidencia subida correctamente', 'Las fotos de tu campaña de Instagram han sido subidas exitosamente.', 'success', TRUE, '2024-03-20 16:30:00'),
(7, 'Próxima actividad', 'Tienes una encuesta programada para el 1 de abril. Prepara los materiales necesarios.', 'warning', FALSE, '2024-03-29 10:00:00'),

-- Notificaciones para Roberto Jiménez
(4, 'Excelente participación en foro', 'El foro ciudadano superó las expectativas de asistencia. ¡Felicitaciones!', 'success', TRUE, '2024-03-10 23:30:00'),
(4, 'Reunión de equipo confirmada', 'Tu reunión del 30 de marzo ha sido confirmada con 7 de 8 activistas.', 'info', FALSE, '2024-03-29 16:45:00'),

-- Notificaciones para otros usuarios
(10, 'Gran alcance en TikTok', 'Tu campaña #VotaInformado está teniendo excelente recepción en la plataforma.', 'success', FALSE, '2024-03-22 22:00:00'),
(5, 'Reconocimiento por taller', 'El taller de liderazgo femenino ha recibido menciones muy positivas de las participantes.', 'success', TRUE, '2024-03-13 10:00:00'),
(6, 'Mesa redonda exitosa', 'La mesa redonda fronteriza generó importante cobertura mediática local.', 'success', TRUE, '2024-03-14 22:15:00');

-- Vista para estadísticas de usuarios
CREATE VIEW vista_estadisticas_usuarios AS
SELECT 
    rol,
    COUNT(*) as total,
    COUNT(CASE WHEN estado = 'activo' THEN 1 END) as activos,
    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
    COUNT(CASE WHEN estado = 'suspendido' THEN 1 END) as suspendidos
FROM usuarios 
GROUP BY rol;

-- Vista para actividades por usuario
CREATE VIEW vista_actividades_usuario AS
SELECT 
    u.id as usuario_id,
    u.nombre_completo,
    u.rol,
    COUNT(a.id) as total_actividades,
    COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as actividades_completadas,
    COUNT(e.id) as total_evidencias
FROM usuarios u
LEFT JOIN actividades a ON u.id = a.usuario_id
LEFT JOIN evidencias e ON a.id = e.actividad_id
GROUP BY u.id, u.nombre_completo, u.rol;