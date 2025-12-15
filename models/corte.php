<?php
/**
 * Modelo de Cortes de Periodo
 * Gestiona snapshots históricos del cumplimiento de activistas
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Corte {
    protected $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            if (!$this->db) {
                throw new Exception("No se pudo establecer conexión a la base de datos");
            }
        } catch (Exception $e) {
            error_log("Corte Model Error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    /**
     * Crear un nuevo corte de periodo
     * Calcula y congela los datos de cumplimiento
     */
    public function crearCorte($data) {
        try {
            // Verificar que la conexión existe
            if (!$this->db) {
                throw new Exception("No hay conexión a la base de datos");
            }
            
            $this->db->beginTransaction();
            
            // 1. Crear el registro del corte
            $stmt = $this->db->prepare("
                INSERT INTO cortes_periodo (nombre, descripcion, fecha_inicio, fecha_fin, creado_por, grupo_id, actividad_id, usuario_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['creado_por'],
                $data['grupo_id'] ?? null,
                $data['actividad_id'] ?? null,
                $data['usuario_id'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception("Error al insertar el corte: " . implode(", ", $stmt->errorInfo()));
            }
            
            $corteId = $this->db->lastInsertId();
            
            if (!$corteId) {
                throw new Exception("No se pudo obtener el ID del corte creado");
            }
            
            // 2. Calcular y guardar el detalle para cada activista
            $this->calcularDetalleCorte($corteId, $data['fecha_inicio'], $data['fecha_fin'], $data['grupo_id'] ?? null, $data['actividad_id'] ?? null, $data['usuario_id'] ?? null);
            
            // 3. Calcular estadísticas globales del corte
            $this->actualizarEstadisticasCorte($corteId);
            
            $this->db->commit();
            
            logActivity("Corte de periodo creado: ID $corteId - {$data['nombre']}");
            return $corteId;
            
        } catch (Exception $e) {
            if ($this->db) {
                $this->db->rollBack();
            }
            logActivity("Error al crear corte: " . $e->getMessage(), 'ERROR');
            error_log("Error detallado al crear corte: " . $e->getMessage());
            $_SESSION['corte_error'] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Calcula el snapshot de cada activista para el periodo
     */
    private function calcularDetalleCorte($corteId, $fechaInicio, $fechaFin, $grupoId = null, $actividadId = null, $usuarioId = null) {
        // Construir query de activistas con filtro de grupo y líder opcional
        $sqlActivistas = "
            SELECT DISTINCT u.id, u.nombre_completo 
            FROM usuarios u
            WHERE u.rol = 'Activista' AND u.estado = 'activo'
        ";
        
        $paramsActivistas = [];
        
        if ($grupoId) {
            $sqlActivistas .= " AND u.grupo_id = ?";
            $paramsActivistas[] = $grupoId;
        }
        
        // Si usuarioId está presente, es el ID del líder, entonces filtramos por lider_id
        if ($usuarioId) {
            $sqlActivistas .= " AND u.lider_id = ?";
            $paramsActivistas[] = $usuarioId;
        }
        
        $sqlActivistas .= " ORDER BY u.nombre_completo";
        
        $stmt = $this->db->prepare($sqlActivistas);
        $stmt->execute($paramsActivistas);
        $activistas = $stmt->fetchAll();
        
        foreach ($activistas as $activista) {
            // Construir query de tareas ASIGNADAS con filtros
            $sqlAsignadas = "
                SELECT COUNT(*) as total
                FROM actividades
                WHERE usuario_id = ?
                AND tarea_pendiente = 1
                AND DATE(fecha_creacion) BETWEEN ? AND ?
                AND (
                    fecha_publicacion IS NULL 
                    OR DATE(fecha_publicacion) <= ?
                )
            ";
            $paramsAsignadas = [$activista['id'], $fechaInicio, $fechaFin, $fechaFin];
            
            if ($actividadId) {
                $sqlAsignadas .= " AND tipo_actividad_id = ?";
                $paramsAsignadas[] = $actividadId;
            }
            
            $stmtAsignadas = $this->db->prepare($sqlAsignadas);
            $stmtAsignadas->execute($paramsAsignadas);
            $tareasAsignadas = $stmtAsignadas->fetch()['total'];
            
            // Construir query de tareas ENTREGADAS con filtros
            $sqlEntregadas = "
                SELECT COUNT(*) as total
                FROM actividades
                WHERE usuario_id = ?
                AND tarea_pendiente = 1
                AND estado = 'completada'
                AND DATE(fecha_actualizacion) BETWEEN ? AND ?
                AND (
                    fecha_publicacion IS NULL 
                    OR DATE(fecha_publicacion) <= ?
                )
            ";
            $paramsEntregadas = [$activista['id'], $fechaInicio, $fechaFin, $fechaFin];
            
            if ($actividadId) {
                $sqlEntregadas .= " AND tipo_actividad_id = ?";
                $paramsEntregadas[] = $actividadId;
            }
            
            $stmtEntregadas = $this->db->prepare($sqlEntregadas);
            $stmtEntregadas->execute($paramsEntregadas);
            $tareasEntregadas = $stmtEntregadas->fetch()['total'];
            
            // Calcular porcentaje
            $porcentaje = $tareasAsignadas > 0 ? round(($tareasEntregadas / $tareasAsignadas) * 100, 2) : 0;
            
            // Guardar detalle (snapshot congelado)
            $stmtInsert = $this->db->prepare("
                INSERT INTO cortes_detalle 
                (corte_id, usuario_id, nombre_completo, tareas_asignadas, tareas_entregadas, porcentaje_cumplimiento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([
                $corteId,
                $activista['id'],
                $activista['nombre_completo'],
                $tareasAsignadas,
                $tareasEntregadas,
                $porcentaje
            ]);
        }
        
        // Calcular ranking (posiciones)
        $this->calcularRanking($corteId);
    }
    
    /**
     * Calcula el ranking del corte basado en porcentaje de cumplimiento
     */
    private function calcularRanking($corteId) {
        $stmt = $this->db->prepare("
            SELECT id 
            FROM cortes_detalle 
            WHERE corte_id = ? 
            ORDER BY porcentaje_cumplimiento DESC, tareas_entregadas DESC, nombre_completo ASC
        ");
        $stmt->execute([$corteId]);
        $detalles = $stmt->fetchAll();
        
        $posicion = 1;
        foreach ($detalles as $detalle) {
            $stmtUpdate = $this->db->prepare("
                UPDATE cortes_detalle 
                SET ranking_posicion = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$posicion, $detalle['id']]);
            $posicion++;
        }
    }
    
    /**
     * Actualiza estadísticas globales del corte
     */
    private function actualizarEstadisticasCorte($corteId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_activistas,
                AVG(porcentaje_cumplimiento) as promedio
            FROM cortes_detalle
            WHERE corte_id = ?
        ");
        $stmt->execute([$corteId]);
        $stats = $stmt->fetch();
        
        // Asegurar que promedio no sea null
        $promedio = $stats['promedio'] !== null ? round($stats['promedio'], 2) : 0;
        
        $stmtUpdate = $this->db->prepare("
            UPDATE cortes_periodo 
            SET total_activistas = ?,
                promedio_cumplimiento = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([
            $stats['total_activistas'],
            $promedio,
            $corteId
        ]);
    }
    
    /**
     * Obtener todos los cortes
     */
    public function getCortes($filters = []) {
        try {
            $sql = "
                SELECT c.*, 
                       u.nombre_completo as creador_nombre,
                       g.nombre as grupo_nombre,
                       ta.nombre as actividad_nombre,
                       ua.nombre_completo as activista_nombre
                FROM cortes_periodo c
                LEFT JOIN usuarios u ON c.creado_por = u.id
                LEFT JOIN grupos g ON c.grupo_id = g.id
                LEFT JOIN tipos_actividades ta ON c.actividad_id = ta.id
                LEFT JOIN usuarios ua ON c.usuario_id = ua.id
                WHERE 1=1
            ";
            $params = [];
            
            if (!empty($filters['estado'])) {
                $sql .= " AND c.estado = ?";
                $params[] = $filters['estado'];
            }
            
            if (!empty($filters['fecha_desde'])) {
                $sql .= " AND c.fecha_inicio >= ?";
                $params[] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $sql .= " AND c.fecha_fin <= ?";
                $params[] = $filters['fecha_hasta'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.nombre LIKE ? OR c.descripcion LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND c.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            $sql .= " ORDER BY c.fecha_creacion DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener cortes: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Obtener un corte por ID
     */
    public function getCorteById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       u.nombre_completo as creador_nombre,
                       g.nombre as grupo_nombre,
                       ta.nombre as actividad_nombre,
                       ua.nombre_completo as activista_nombre
                FROM cortes_periodo c
                LEFT JOIN usuarios u ON c.creado_por = u.id
                LEFT JOIN grupos g ON c.grupo_id = g.id
                LEFT JOIN tipos_actividades ta ON c.actividad_id = ta.id
                LEFT JOIN usuarios ua ON c.usuario_id = ua.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logActivity("Error al obtener corte: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Obtener el detalle completo de un corte (todos los activistas)
     */
    public function getDetalleCorte($corteId, $filters = []) {
        try {
            $sql = "
                SELECT cd.*,
                       u.rol,
                       u.email,
                       u.telefono,
                       u.lider_id,
                       ul.nombre_completo as lider_nombre
                FROM cortes_detalle cd
                LEFT JOIN usuarios u ON cd.usuario_id = u.id
                LEFT JOIN usuarios ul ON u.lider_id = ul.id
                WHERE cd.corte_id = ?
            ";
            $params = [$corteId];
            
            if (!empty($filters['search'])) {
                $sql .= " AND cd.nombre_completo LIKE ?";
                $params[] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND cd.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            $sql .= " ORDER BY cd.ranking_posicion ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener detalle de corte: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Obtener tareas de un activista en un corte específico
     */
    public function getTareasActivista($corteId, $usuarioId) {
        try {
            // Obtener información del corte
            $corte = $this->getCorteById($corteId);
            if (!$corte) {
                return [];
            }
            
            // Construir query base
            // Solo incluye tareas CREADAS antes de la fecha de creación del corte
            // Las tareas deben haber sido publicadas antes o durante el periodo
            $sql = "
                SELECT 
                    a.*,
                    ta.nombre as tipo_actividad,
                    CASE 
                        WHEN a.estado = 'completada' THEN 'Completada'
                        WHEN a.estado = 'pendiente' THEN 'Pendiente'
                        ELSE a.estado
                    END as estado_texto
                FROM actividades a
                LEFT JOIN tipos_actividades ta ON a.tipo_actividad_id = ta.id
                WHERE a.usuario_id = ?
                AND a.tarea_pendiente = 1
                AND DATE(a.fecha_creacion) BETWEEN ? AND ?
                AND a.fecha_creacion <= ?
                AND (
                    a.fecha_publicacion IS NULL 
                    OR DATE(a.fecha_publicacion) <= ?
                )
            ";
            
            $params = [$usuarioId, $corte['fecha_inicio'], $corte['fecha_fin'], $corte['fecha_creacion'], $corte['fecha_fin']];
            
            // Agregar filtro de actividad si existe
            if (!empty($corte['actividad_id'])) {
                $sql .= " AND a.tipo_actividad_id = ?";
                $params[] = $corte['actividad_id'];
            }
            
            $sql .= " ORDER BY a.fecha_creacion DESC, a.estado ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $tareas = $stmt->fetchAll();
            
            // Cargar evidencias para cada tarea
            foreach ($tareas as &$tarea) {
                $stmtEvidencias = $this->db->prepare("
                    SELECT * FROM evidencias 
                    WHERE actividad_id = ? 
                    ORDER BY fecha_subida DESC
                ");
                $stmtEvidencias->execute([$tarea['id']]);
                $tarea['evidences'] = $stmtEvidencias->fetchAll();
            }
            
            return $tareas;
        } catch (Exception $e) {
            logActivity("Error al obtener tareas de activista en corte: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Cerrar un corte (cambiar estado)
     */
    public function cerrarCorte($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE cortes_periodo 
                SET estado = 'cerrado' 
                WHERE id = ?
            ");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity("Corte ID $id cerrado");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al cerrar corte: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Eliminar un corte
     */
    public function deleteCorte($id) {
        try {
            // El detalle se elimina automáticamente por CASCADE
            $stmt = $this->db->prepare("DELETE FROM cortes_periodo WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity("Corte ID $id eliminado");
            }
            
            return $result;
        } catch (Exception $e) {
            logActivity("Error al eliminar corte: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Obtener cortes de un grupo específico para líderes
     * Muestra todos los cortes que se han hecho para ese grupo
     */
    public function getCortesByGrupo($grupoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       u.nombre_completo as creador_nombre,
                       g.nombre as grupo_nombre,
                       ta.nombre as actividad_nombre,
                       ua.nombre_completo as activista_nombre
                FROM cortes_periodo c
                LEFT JOIN usuarios u ON c.creado_por = u.id
                LEFT JOIN grupos g ON c.grupo_id = g.id
                LEFT JOIN tipos_actividades ta ON c.actividad_id = ta.id
                LEFT JOIN usuarios ua ON c.usuario_id = ua.id
                WHERE c.grupo_id = ?
                ORDER BY c.fecha_creacion DESC
            ");
            $stmt->execute([$grupoId]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logActivity("Error al obtener cortes por grupo: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
}
