<?php
/**
 * Controlador de Reportes
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/activity.php';

class ReportController {
    private $auth;
    private $activityModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->activityModel = new Activity();
    }
    
    // Mostrar informe global de tareas
    public function showGlobalTaskReport() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin, Gestor y Líder pueden ver el informe global
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('dashboards/activista.php', 'No tienes permisos para ver este informe', 'error');
        }
        
        // Obtener filtros
        $filters = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'nombre_actividad' => $_GET['nombre_actividad'] ?? '',
            'nombre_activista' => $_GET['nombre_activista'] ?? '',
            'grupo_id' => $_GET['grupo_id'] ?? '',
            'lider_id' => $_GET['lider_id'] ?? ''
        ];
        
        // Obtener informe
        $tasks = $this->activityModel->getGlobalTaskReport($filters);
        
        // Obtener listas para los filtros
        require_once __DIR__ . '/../models/group.php';
        require_once __DIR__ . '/../models/user.php';
        
        $groupModel = new Group();
        $userModel = new User();
        
        $grupos = $groupModel->getAllGroups();
        $lideres = $userModel->getActiveLiders();
        
        include __DIR__ . '/../views/reports/global_tasks.php';
    }
    
    // Mostrar detalle de una tarea específica
    public function showTaskDetail() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin, Gestor y Líder pueden ver el detalle
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('dashboards/activista.php', 'No tienes permisos para ver este detalle', 'error');
        }
        
        $titulo = $_GET['titulo'] ?? '';
        $tipoActividadId = $_GET['tipo_actividad_id'] ?? '';
        
        // Debug
        error_log("showTaskDetail - GET params: " . json_encode($_GET));
        error_log("showTaskDetail - Titulo: '$titulo', TipoID: '$tipoActividadId'");
        
        if (empty($titulo) || empty($tipoActividadId)) {
            error_log("showTaskDetail - Parametros vacios!");
            redirectWithMessage('reports/global-tasks.php?fecha_desde=' . ($_GET['fecha_desde'] ?? date('Y-m-01')) . '&fecha_hasta=' . ($_GET['fecha_hasta'] ?? date('Y-m-t')), 'Tarea no especificada', 'error');
        }
        
        // Obtener filtros
        $filters = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'nombre_activista' => $_GET['nombre_activista'] ?? '',
            'grupo_id' => $_GET['grupo_id'] ?? '',
            'lider_id' => $_GET['lider_id'] ?? ''
        ];
        
        // Obtener detalle de la tarea
        $taskDetails = $this->activityModel->getTaskDetailReport($titulo, $tipoActividadId, $filters);
        
        // Aplicar filtro de estado si se especificó (filtro adicional en PHP)
        if (!empty($filters['estado'])) {
            $taskDetails = array_filter($taskDetails, function($detail) use ($filters) {
                return $detail['estado'] === $filters['estado'];
            });
            $taskDetails = array_values($taskDetails); // Reindexar array
        }
        
        if (empty($taskDetails)) {
            error_log("showTaskDetail - No se encontraron resultados");
            // Mostrar información de debug antes de redirigir
            $debugMsg = 'No se encontraron resultados. Debug: Titulo="' . htmlspecialchars($titulo) . '", Tipo=' . $tipoActividadId . ', Fecha=' . $filters['fecha_desde'] . ' a ' . $filters['fecha_hasta'];
            redirectWithMessage('reports/global-tasks.php?fecha_desde=' . urlencode($filters['fecha_desde']) . '&fecha_hasta=' . urlencode($filters['fecha_hasta']), $debugMsg, 'error');
        }
        
        // Calcular estadísticas
        $totalAsignadas = count($taskDetails);
        $totalCompletadas = 0;
        $tiemposCompletado = [];
        
        foreach ($taskDetails as $detail) {
            if ($detail['estado'] === 'completada') {
                $totalCompletadas++;
                if ($detail['horas_para_completar'] !== null) {
                    $tiemposCompletado[] = $detail['horas_para_completar'];
                }
            }
        }
        
        $porcentajeCumplimiento = $totalAsignadas > 0 ? round(($totalCompletadas / $totalAsignadas) * 100, 2) : 0;
        $tiempoPromedio = !empty($tiemposCompletado) ? round(array_sum($tiemposCompletado) / count($tiemposCompletado), 2) : 0;
        
        $stats = [
            'total_asignadas' => $totalAsignadas,
            'total_completadas' => $totalCompletadas,
            'total_pendientes' => $totalAsignadas - $totalCompletadas,
            'porcentaje_cumplimiento' => $porcentajeCumplimiento,
            'tiempo_promedio_horas' => $tiempoPromedio
        ];
        
        // Obtener listas para los filtros
        require_once __DIR__ . '/../models/group.php';
        require_once __DIR__ . '/../models/user.php';
        
        $groupModel = new Group();
        $userModel = new User();
        
        $grupos = $groupModel->getAllGroups();
        $lideres = $userModel->getActiveLiders();
        
        include __DIR__ . '/../views/reports/task_detail.php';
    }
}
?>
