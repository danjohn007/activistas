-- Migration: Sistema de Cortes de Periodo
-- Descripción: Permite crear snapshots/cortes históricos del cumplimiento de activistas
--              Los datos quedan congelados y no se actualizan con entregas posteriores
-- Fecha: 2025-12-05

USE fix360_ad;

-- Tabla principal de cortes
CREATE TABLE IF NOT EXISTS cortes_periodo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NOT NULL,
    grupo_id INT NULL,
    actividad_id INT NULL,
    estado ENUM('activo', 'cerrado') DEFAULT 'activo',
    total_activistas INT DEFAULT 0,
    promedio_cumplimiento DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL,
    FOREIGN KEY (actividad_id) REFERENCES tipos_actividades(id) ON DELETE SET NULL,
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_estado (estado),
    INDEX idx_grupo (grupo_id),
    INDEX idx_actividad (actividad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cortes de periodo para snapshots históricos de cumplimiento';

-- Tabla de detalle (snapshot congelado de cada activista)
CREATE TABLE IF NOT EXISTS cortes_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    corte_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    tareas_asignadas INT NOT NULL DEFAULT 0,
    tareas_entregadas INT NOT NULL DEFAULT 0,
    porcentaje_cumplimiento DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    ranking_posicion INT,
    fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (corte_id) REFERENCES cortes_periodo(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    UNIQUE KEY unique_corte_usuario (corte_id, usuario_id),
    INDEX idx_corte (corte_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ranking (corte_id, porcentaje_cumplimiento DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detalle congelado del cumplimiento de cada activista por corte';

-- Listo! Las tablas han sido creadas exitosamente.
