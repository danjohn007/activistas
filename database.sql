CREATE DATABASE IF NOT EXISTS activistas_digitales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE activistas_digitales;

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