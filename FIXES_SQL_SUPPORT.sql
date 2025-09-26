-- SQL queries that support the implemented fixes
-- This file documents the database support for the functionality changes

-- =============================================================
-- Fix 1: Dashboard Charts
-- =============================================================
-- The dashboard charts use existing views and tables:

-- User statistics (usuarios table)
SELECT rol, 
       COUNT(*) as total,
       COUNT(CASE WHEN estado = 'activo' THEN 1 END) as activos
FROM usuarios 
GROUP BY rol;

-- Activity statistics (actividades + tipos_actividades tables)
SELECT ta.nombre as tipo, COUNT(*) as cantidad
FROM actividades a
JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
GROUP BY ta.id, ta.nombre;

-- Monthly activities (actividades table)
SELECT DATE_FORMAT(fecha_actividad, '%Y-%m') as mes,
       COUNT(*) as cantidad
FROM actividades 
WHERE fecha_actividad >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(fecha_actividad, '%Y-%m')
ORDER BY mes;

-- Team ranking (usuarios + actividades tables)
SELECT l.nombre_completo as lider_nombre,
       COUNT(a.id) as total_actividades,
       COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as completadas
FROM usuarios l
LEFT JOIN usuarios u ON l.id = u.lider_id
LEFT JOIN actividades a ON (u.id = a.usuario_id OR l.id = a.usuario_id)
WHERE l.rol = 'Líder' AND l.estado = 'activo'
GROUP BY l.id, l.nombre_completo
ORDER BY completadas DESC, total_actividades DESC;

-- =============================================================
-- Fix 2: Group Assignment in User Approval
-- =============================================================
-- New functionality uses grupos table and grupo_id column in usuarios

-- Approve user with group assignment:
UPDATE usuarios 
SET estado = 'activo', 
    vigencia_hasta = ?, 
    rol = ?, 
    lider_id = ?, 
    grupo_id = ?
WHERE id = ?;

-- Get active groups for selection:
SELECT g.*, 
       l.nombre_completo as lider_nombre,
       COUNT(u.id) as miembros_count
FROM grupos g
LEFT JOIN usuarios l ON g.lider_id = l.id
LEFT JOIN usuarios u ON u.grupo_id = g.id AND u.estado = 'activo'
WHERE g.activo = 1 
GROUP BY g.id
ORDER BY g.nombre;

-- =============================================================
-- Fix 3: Group Assignment in User Edit
-- =============================================================
-- Uses existing updateUser method now enhanced with grupo_id support

-- Update user with group assignment:
UPDATE usuarios 
SET nombre_completo = ?, 
    telefono = ?, 
    direccion = ?, 
    grupo_id = ?
WHERE id = ?;

-- =============================================================
-- Fix 4: Auto-selection of Team/Group Members
-- =============================================================
-- Team members query (existing):
SELECT u.*, l.nombre_completo as lider_nombre
FROM usuarios u
LEFT JOIN usuarios l ON u.lider_id = l.id
WHERE u.lider_id = ? AND u.estado = 'activo'
ORDER BY u.rol, u.nombre_completo;

-- Group members query (enhanced):
SELECT u.*, l.nombre_completo as lider_nombre
FROM usuarios u
LEFT JOIN usuarios l ON u.lider_id = l.id
WHERE u.grupo_id = ? AND u.estado = 'activo'
ORDER BY u.rol, u.nombre_completo;

-- =============================================================
-- Database Schema Verification
-- =============================================================
-- Verify that all required tables and columns exist:

-- Check if grupos table exists:
SELECT COUNT(*) as table_exists 
FROM INFORMATION_SCHEMA.TABLES 
WHERE table_schema = DATABASE() 
AND table_name = 'grupos';

-- Check if grupo_id column exists in usuarios table:
SELECT COUNT(*) as column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE table_schema = DATABASE() 
AND table_name = 'usuarios' 
AND column_name = 'grupo_id';

-- All changes are backward compatible and use existing schema
-- created by database_migration_groups_complete.sql