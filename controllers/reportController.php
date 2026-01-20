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
            'fecha_desde' => $_GET['fecha_desde'] ?? '', // Sin fecha por defecto
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',   // Sin fecha por defecto
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
        
        // Hacer $currentUser disponible para la vista
        $GLOBALS['currentUser'] = $currentUser;
        
        include __DIR__ . '/../views/reports/task_detail.php';
    }
    
    // Eliminar actividad global (para todos los activistas)
    public function deleteGlobalTask() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin y Gestor pueden eliminar actividades globales
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            redirectWithMessage('reports/global-tasks.php', 'No tienes permisos para eliminar actividades', 'error');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('reports/global-tasks.php', 'Método no permitido', 'error');
        }
        
        $titulo = $_POST['titulo'] ?? '';
        $tipoActividadId = $_POST['tipo_actividad_id'] ?? '';
        
        if (empty($titulo) || empty($tipoActividadId)) {
            redirectWithMessage('reports/global-tasks.php', 'Datos incompletos', 'error');
        }
        
        try {
            $result = $this->activityModel->deleteGlobalTask($titulo, $tipoActividadId);
            
            if ($result['success']) {
                redirectWithMessage('reports/global-tasks.php', 
                    "Actividad eliminada exitosamente. Se eliminaron {$result['deleted_count']} asignaciones.", 
                    'success');
            } else {
                redirectWithMessage('reports/global-tasks.php', 
                    'Error al eliminar la actividad: ' . $result['message'], 
                    'error');
            }
        } catch (Exception $e) {
            error_log("Error al eliminar actividad global: " . $e->getMessage());
            redirectWithMessage('reports/global-tasks.php', 'Error al eliminar la actividad', 'error');
        }
    }
    
    // Editar actividad global (para todos los activistas)
    public function editGlobalTask() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin, Gestor y Líder pueden editar actividades globales
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('reports/global-tasks.php', 'No tienes permisos para editar actividades', 'error');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('reports/global-tasks.php', 'Método no permitido', 'error');
        }
        
        $tituloOriginal = $_POST['titulo_original'] ?? '';
        $tipoActividadId = $_POST['tipo_actividad_id'] ?? '';
        $nuevoTitulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fechaActividad = $_POST['fecha_actividad'] ?? '';
        $fechaPublicacion = $_POST['fecha_publicacion'] ?? null;
        $horaPublicacion = $_POST['hora_publicacion'] ?? null;
        $fechaCierre = $_POST['fecha_cierre'] ?? null;
        $horaCierre = $_POST['hora_cierre'] ?? null;
        
        if (empty($tituloOriginal) || empty($tipoActividadId) || empty($nuevoTitulo) || empty($fechaActividad)) {
            redirectWithMessage('reports/global-tasks.php', 'Datos incompletos', 'error');
        }
        
        try {
            // Combinar fecha y hora de publicación si ambos están presentes
            $fechaPublicacionCompleta = null;
            if (!empty($fechaPublicacion) && !empty($horaPublicacion)) {
                $fechaPublicacionCompleta = $fechaPublicacion . ' ' . $horaPublicacion;
            } elseif (!empty($fechaPublicacion)) {
                $fechaPublicacionCompleta = $fechaPublicacion;
            }
            
            $updateData = [
                'titulo' => $nuevoTitulo,
                'descripcion' => $descripcion,
                'fecha_actividad' => $fechaActividad,
                'fecha_publicacion' => $fechaPublicacionCompleta,
                'hora_publicacion' => $horaPublicacion,
                'fecha_cierre' => $fechaCierre,
                'hora_cierre' => $horaCierre
            ];
            
            $result = $this->activityModel->updateGlobalTask($tituloOriginal, $tipoActividadId, $updateData);
            
            if ($result['success']) {
                redirectWithMessage('reports/global-tasks.php', 
                    "Actividad actualizada exitosamente. Se actualizaron {$result['updated_count']} asignaciones.", 
                    'success');
            } else {
                redirectWithMessage('reports/global-tasks.php', 
                    'Error al actualizar la actividad: ' . $result['message'], 
                    'error');
            }
        } catch (Exception $e) {
            error_log("Error al editar actividad global: " . $e->getMessage());
            redirectWithMessage('reports/global-tasks.php', 'Error al actualizar la actividad', 'error');
        }
    }
    
    // Eliminar múltiples actividades globales a la vez
    public function deleteMultipleTasks() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin y Gestor pueden eliminar actividades globales
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            redirectWithMessage('reports/global-tasks.php', 'No tienes permisos para eliminar actividades', 'error');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('reports/global-tasks.php', 'Método no permitido', 'error');
        }
        
        $actividadesJson = $_POST['actividades'] ?? '';
        
        if (empty($actividadesJson)) {
            redirectWithMessage('reports/global-tasks.php', 'No se especificaron actividades a eliminar', 'error');
        }
        
        try {
            $actividades = json_decode($actividadesJson, true);
            
            if (!is_array($actividades) || empty($actividades)) {
                redirectWithMessage('reports/global-tasks.php', 'Datos inválidos', 'error');
            }
            
            $result = $this->activityModel->deleteMultipleGlobalTasks($actividades);
            
            if ($result['success']) {
                redirectWithMessage('reports/global-tasks.php', 
                    "Se eliminaron {$result['total_activities']} actividades ({$result['total_deleted']} asignaciones en total).", 
                    'success');
            } else {
                redirectWithMessage('reports/global-tasks.php', 
                    'Error al eliminar las actividades: ' . $result['message'], 
                    'error');
            }
        } catch (Exception $e) {
            error_log("Error al eliminar múltiples actividades: " . $e->getMessage());
            redirectWithMessage('reports/global-tasks.php', 'Error al eliminar las actividades', 'error');
        }
    }
}
?>
