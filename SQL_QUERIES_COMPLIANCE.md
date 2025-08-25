# SQL QUERIES FOR COMPLIANCE CALCULATION AND TRAFFIC LIGHT FILTERING

This document contains the complete SQL queries used for calculating compliance percentages and implementing the traffic light filter system in the Activistas platform.

## 1. Base Query for User Compliance Calculation

```sql
SELECT u.*, l.nombre_completo as lider_nombre,
       COUNT(a.id) as total_tareas,
       COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as tareas_completadas,
       CASE 
           WHEN COUNT(a.id) = 0 THEN 0
           ELSE ROUND((COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) * 100, 1)
       END as porcentaje_cumplimiento
FROM usuarios u 
LEFT JOIN usuarios l ON u.lider_id = l.id 
LEFT JOIN actividades a ON u.id = a.usuario_id AND a.tarea_pendiente = 1 AND a.autorizada = 1
WHERE 1=1
GROUP BY u.id, u.nombre_completo, u.telefono, u.email, u.foto_perfil, u.direccion, u.rol, u.lider_id, u.estado, u.fecha_registro, l.nombre_completo
ORDER BY u.fecha_registro DESC;
```

## 2. Traffic Light Filter Queries

### High Compliance (Verde - >60%)
```sql
SELECT /* base query from above */
HAVING COUNT(a.id) > 0 AND (COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) > 0.6;
```

### Medium Compliance (Amarillo - 20-60%)
```sql
SELECT /* base query from above */
HAVING COUNT(a.id) > 0 AND (COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) BETWEEN 0.2 AND 0.6;
```

### Low Compliance (Rojo - <20%)
```sql
SELECT /* base query from above */
HAVING COUNT(a.id) > 0 AND (COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) < 0.2;
```

### No Tasks Assigned (Gris - Sin tareas)
```sql
SELECT /* base query from above */
HAVING COUNT(a.id) = 0;
```

## 3. Complete Query with Filters and Pagination

```sql
SELECT u.*, l.nombre_completo as lider_nombre,
       COUNT(a.id) as total_tareas,
       COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) as tareas_completadas,
       CASE 
           WHEN COUNT(a.id) = 0 THEN 0
           ELSE ROUND((COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) * 100, 1)
       END as porcentaje_cumplimiento
FROM usuarios u 
LEFT JOIN usuarios l ON u.lider_id = l.id 
LEFT JOIN actividades a ON u.id = a.usuario_id AND a.tarea_pendiente = 1 AND a.autorizada = 1
WHERE 1=1
  -- Optional role filter
  AND (:rol IS NULL OR u.rol = :rol)
  -- Optional status filter
  AND (:estado IS NULL OR u.estado = :estado)
GROUP BY u.id, u.nombre_completo, u.telefono, u.email, u.foto_perfil, u.direccion, u.rol, u.lider_id, u.estado, u.fecha_registro, l.nombre_completo
-- Compliance filter using HAVING clause
HAVING (:cumplimiento IS NULL 
        OR (:cumplimiento = 'alto' AND COUNT(a.id) > 0 AND (COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) > 0.6)
        OR (:cumplimiento = 'medio' AND COUNT(a.id) > 0 AND (COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) BETWEEN 0.2 AND 0.6)
        OR (:cumplimiento = 'bajo' AND COUNT(a.id) > 0 AND (COUNT(CASE WHEN a.estado = 'completada' THEN 1 END) / COUNT(a.id)) < 0.2)
        OR (:cumplimiento = 'sin_tareas' AND COUNT(a.id) = 0))
ORDER BY u.fecha_registro DESC
LIMIT :perPage OFFSET :offset;
```

## 4. Count Query for Pagination

```sql
-- For simple filters (without compliance filter)
SELECT COUNT(DISTINCT u.id) as total
FROM usuarios u 
LEFT JOIN usuarios l ON u.lider_id = l.id 
LEFT JOIN actividades a ON u.id = a.usuario_id AND a.tarea_pendiente = 1 AND a.autorizada = 1
WHERE 1=1
  AND (:rol IS NULL OR u.rol = :rol)
  AND (:estado IS NULL OR u.estado = :estado);

-- For compliance filter (requires subquery)
SELECT COUNT(*) as total 
FROM (
    SELECT u2.id
    FROM usuarios u2 
    LEFT JOIN actividades a2 ON u2.id = a2.usuario_id AND a2.tarea_pendiente = 1 AND a2.autorizada = 1
    WHERE 1=1
      AND (:rol IS NULL OR u2.rol = :rol)
      AND (:estado IS NULL OR u2.estado = :estado)
    GROUP BY u2.id
    HAVING (:cumplimiento = 'alto' AND COUNT(a2.id) > 0 AND (COUNT(CASE WHEN a2.estado = 'completada' THEN 1 END) / COUNT(a2.id)) > 0.6)
        OR (:cumplimiento = 'medio' AND COUNT(a2.id) > 0 AND (COUNT(CASE WHEN a2.estado = 'completada' THEN 1 END) / COUNT(a2.id)) BETWEEN 0.2 AND 0.6)
        OR (:cumplimiento = 'bajo' AND COUNT(a2.id) > 0 AND (COUNT(CASE WHEN a2.estado = 'completada' THEN 1 END) / COUNT(a2.id)) < 0.2)
        OR (:cumplimiento = 'sin_tareas' AND COUNT(a2.id) = 0)
) as filtered_users;
```

## 5. Individual User Compliance Query

```sql
SELECT 
    COUNT(*) as total_tareas,
    COUNT(CASE WHEN estado = 'completada' THEN 1 END) as tareas_completadas,
    CASE 
        WHEN COUNT(*) = 0 THEN 0
        ELSE ROUND((COUNT(CASE WHEN estado = 'completada' THEN 1 END) / COUNT(*)) * 100, 1)
    END as porcentaje_cumplimiento
FROM actividades 
WHERE usuario_id = :userId 
  AND tarea_pendiente = 1 
  AND autorizada = 1;
```

## 6. Activity Proposals Query

```sql
SELECT a.*, u.nombre_completo as usuario_nombre, ta.nombre as tipo_nombre,
       u.email as usuario_email, u.telefono as usuario_telefono,
       s.nombre_completo as solicitante_nombre
FROM actividades a 
JOIN usuarios u ON a.usuario_id = u.id 
JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id 
LEFT JOIN usuarios s ON a.solicitante_id = s.id
WHERE a.tarea_pendiente = 2 AND a.autorizada = 0
ORDER BY a.fecha_creacion DESC;
```

## Database Schema Requirements

### Key Fields Used
- `actividades.tarea_pendiente`: 
  - `0` = Normal activity
  - `1` = Assigned task (used for compliance calculation)
  - `2` = Proposal (pending authorization)
- `actividades.autorizada`: `1` = authorized, `0` = pending authorization
- `actividades.estado`: 'completada', 'programada', 'cancelada'
- `usuarios.rol`: 'SuperAdmin', 'Gestor', 'Líder', 'Activista'
- `usuarios.estado`: 'activo', 'pendiente', 'suspendido', 'desactivado'

### Recommended Indexes for Performance
```sql
-- For user compliance queries
CREATE INDEX idx_actividades_compliance ON actividades(usuario_id, tarea_pendiente, autorizada, estado);

-- For user management
CREATE INDEX idx_usuarios_management ON usuarios(rol, estado, fecha_registro);
CREATE INDEX idx_usuarios_lider ON usuarios(lider_id);

-- For proposals
CREATE INDEX idx_actividades_proposals ON actividades(tarea_pendiente, autorizada, fecha_creacion);
```

## MySQL Compatibility Notes
- All queries are optimized for MySQL 5.7+ and MySQL 8.0
- Uses standard SQL functions: COUNT(), CASE WHEN, ROUND(), etc.
- LIMIT and OFFSET for pagination (MySQL standard)
- LEFT JOIN for optional relationships
- No SQLite-specific functions used

## Performance Considerations
1. The compliance calculation involves GROUP BY and HAVING clauses which can be expensive for large datasets
2. Pagination is implemented to limit results to 20 users per page
3. Proper indexing is crucial for performance with the suggested indexes above
4. The count query for pagination with compliance filters uses a subquery which may be slower but is necessary for accuracy