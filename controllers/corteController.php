<?php
/**
 * Controlador de Cortes de Periodo
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/corte.php';
require_once __DIR__ . '/../includes/functions.php';

class CorteController {
    private $auth;
    private $corteModel;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->corteModel = new Corte();
    }
    
    /**
     * Listar todos los cortes
     */
    public function listCortes() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin y Gestor pueden ver cortes
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            redirectWithMessage('dashboard/', 'No tienes permisos para acceder a esta sección', 'error');
        }
        
        // Aplicar filtros
        $filters = [];
        if (!empty($_GET['estado'])) {
            $filters['estado'] = cleanInput($_GET['estado']);
        }
        if (!empty($_GET['fecha_desde'])) {
            $filters['fecha_desde'] = cleanInput($_GET['fecha_desde']);
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filters['fecha_hasta'] = cleanInput($_GET['fecha_hasta']);
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = cleanInput($_GET['search']);
        }
        
        // Paginación
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        $allCortes = $this->corteModel->getCortes($filters);
        
        // Agrupar cortes que fueron creados al mismo tiempo (cortes masivos)
        $cortesAgrupados = $this->agruparCortesMasivos($allCortes);
        
        $totalCortes = count($cortesAgrupados);
        $totalPages = ceil($totalCortes / $perPage);
        $cortes = array_slice($cortesAgrupados, $offset, $perPage);
        
        include __DIR__ . '/../views/cortes/list.php';
    }
    
    /**
     * Listar cortes del líder actual
     */
    public function listMisCortes() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo Líderes
        if ($currentUser['rol'] !== 'Líder') {
            redirectWithMessage('dashboard/', 'No tienes permisos para acceder a esta sección', 'error');
        }
        
        // Obtener cortes del líder
        $filters = ['usuario_id' => $currentUser['id']];
        
        if (!empty($_GET['estado'])) {
            $filters['estado'] = cleanInput($_GET['estado']);
        }
        if (!empty($_GET['fecha_desde'])) {
            $filters['fecha_desde'] = cleanInput($_GET['fecha_desde']);
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filters['fecha_hasta'] = cleanInput($_GET['fecha_hasta']);
        }
        
        $cortes = $this->corteModel->getCortes($filters);
        
        include __DIR__ . '/../views/cortes/mis_cortes.php';
    }
    
    /**
     * Agrupa cortes que fueron creados como parte de cortes masivos
     */
    private function agruparCortesMasivos($cortes) {
        $grupos = [];
        $procesados = [];
        
        foreach ($cortes as $corte) {
            if (in_array($corte['id'], $procesados)) {
                continue;
            }
            
            // Buscar cortes relacionados (creados en el mismo minuto y con nombre similar)
            $cortesRelacionados = [$corte];
            $nombreBase = $this->extraerNombreBase($corte['nombre']);
            $fechaCreacion = strtotime($corte['fecha_creacion']);
            
            foreach ($cortes as $otroCorte) {
                if ($otroCorte['id'] === $corte['id'] || in_array($otroCorte['id'], $procesados)) {
                    continue;
                }
                
                $otroNombreBase = $this->extraerNombreBase($otroCorte['nombre']);
                $otraFechaCreacion = strtotime($otroCorte['fecha_creacion']);
                
                // Si tienen el mismo nombre base, fechas iguales y fueron creados en el mismo minuto
                if ($nombreBase === $otroNombreBase && 
                    $corte['fecha_inicio'] === $otroCorte['fecha_inicio'] &&
                    $corte['fecha_fin'] === $otroCorte['fecha_fin'] &&
                    abs($fechaCreacion - $otraFechaCreacion) < 120) { // 2 minutos de diferencia
                    
                    $cortesRelacionados[] = $otroCorte;
                    $procesados[] = $otroCorte['id'];
                }
            }
            
            $procesados[] = $corte['id'];
            
            // Si hay más de un corte, es un grupo
            if (count($cortesRelacionados) > 1) {
                $grupos[] = [
                    'es_grupo' => true,
                    'nombre_grupo' => $nombreBase,
                    'cantidad' => count($cortesRelacionados),
                    'cortes' => $cortesRelacionados,
                    'fecha_inicio' => $corte['fecha_inicio'],
                    'fecha_fin' => $corte['fecha_fin'],
                    'fecha_creacion' => $corte['fecha_creacion'],
                    'estado' => $corte['estado'],
                    'creador_nombre' => $corte['creador_nombre'],
                    // Calcular totales del grupo
                    'total_activistas' => array_sum(array_column($cortesRelacionados, 'total_activistas')),
                    'promedio_cumplimiento' => $this->calcularPromedioGrupo($cortesRelacionados)
                ];
            } else {
                $grupos[] = [
                    'es_grupo' => false,
                    'corte' => $corte
                ];
            }
        }
        
        return $grupos;
    }
    
    /**
     * Extrae el nombre base de un corte (sin el sufijo del líder/grupo)
     */
    private function extraerNombreBase($nombreCompleto) {
        // Buscar el último " - " y tomar lo que está antes
        $pos = strrpos($nombreCompleto, ' - ');
        if ($pos !== false) {
            return substr($nombreCompleto, 0, $pos);
        }
        return $nombreCompleto;
    }
    
    /**
     * Calcula el promedio ponderado del grupo
     */
    private function calcularPromedioGrupo($cortes) {
        $totalActivistas = 0;
        $sumaProductos = 0;
        
        foreach ($cortes as $corte) {
            $activistas = $corte['total_activistas'] ?? 0;
            $promedio = $corte['promedio_cumplimiento'] ?? 0;
            
            $totalActivistas += $activistas;
            $sumaProductos += ($promedio * $activistas);
        }
        
        return $totalActivistas > 0 ? round($sumaProductos / $totalActivistas, 2) : 0;
    }
    
    /**
     * Mostrar formulario de crear corte
     */
    public function showCreateForm() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        // SuperAdmin, Gestor y Líder pueden crear cortes
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para crear cortes', 'error');
        }
        
        // Cargar grupos
        require_once __DIR__ . '/../models/group.php';
        $groupModel = new Group();
        $groups = [];
        if ($currentUser['rol'] === 'Líder') {
            if (!empty($currentUser['grupo_id'])) {
                $allGroups = $groupModel->getAllGroups();
                foreach ($allGroups as $group) {
                    if ((int)$group['id'] === (int)$currentUser['grupo_id']) {
                        $groups[] = $group;
                        break;
                    }
                }
            }
        } else {
            $groups = $groupModel->getAllGroups();
        }
        
        // Cargar tipos de actividades
        require_once __DIR__ . '/../models/activityType.php';
        $activityTypeModel = new ActivityType();
        $activityTypes = $activityTypeModel->getAllActivityTypes();
        
        // Cargar activistas
        require_once __DIR__ . '/../models/user.php';
        $userModel = new User();
        $activistaFilters = ['rol' => 'Activista', 'estado' => 'activo'];
        if ($currentUser['rol'] === 'Líder') {
            $activistaFilters['lider_id'] = $currentUser['id'];
        }
        $activistas = $userModel->getAllUsers($activistaFilters);
        
        include __DIR__ . '/../views/cortes/create.php';
    }
    
    /**
     * Crear nuevo corte
     */
    public function createCorte() {
        error_log("=== INICIO createCorte ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("ERROR: No es POST, redirigiendo");
            redirectWithMessage('cortes/create.php', 'Método no permitido', 'error');
        }
        
        error_log("CSRF Token POST: " . ($_POST['csrf_token'] ?? 'NO ENVIADO'));
        error_log("CSRF Token SESSION: " . ($_SESSION['csrf_token'] ?? 'NO EN SESSION'));
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            error_log("ERROR: CSRF inválido");
            redirectWithMessage('cortes/create.php', 'Token de seguridad inválido', 'error');
        }
        
        error_log("CSRF válido, continuando...");
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin, Gestor y Líder
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para crear cortes', 'error');
        }
        
        // Validar datos
        $errors = $this->validateCorteData($_POST);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirectWithMessage('cortes/create.php', 'Por favor corrige los errores', 'error');
        }
        
        $isLeader = ($currentUser['rol'] === 'Líder');

        $data = [
            'nombre' => cleanInput($_POST['nombre']),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'fecha_inicio' => cleanInput($_POST['fecha_inicio']),
            'fecha_fin' => cleanInput($_POST['fecha_fin']),
            'creado_por' => $currentUser['id'],
            'grupo_id' => $isLeader
                ? (!empty($currentUser['grupo_id']) ? intval($currentUser['grupo_id']) : null)
                : (!empty($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null),
            'actividad_id' => !empty($_POST['actividad_id']) ? intval($_POST['actividad_id']) : null,
            'usuario_id' => $isLeader
                ? intval($currentUser['id'])
                : (!empty($_POST['usuario_id']) ? intval($_POST['usuario_id']) : null)
        ];
        
        $corteId = $this->corteModel->crearCorte($data);
        
        if ($corteId) {
            redirectWithMessage('cortes/detail.php?id=' . $corteId, 
                'Corte creado exitosamente. Datos congelados para el periodo seleccionado.', 'success');
        } else {
            $errorMsg = 'Error al crear el corte';
            if (isset($_SESSION['corte_error'])) {
                $errorMsg .= ': ' . $_SESSION['corte_error'];
                unset($_SESSION['corte_error']);
            }
            redirectWithMessage('cortes/create.php', $errorMsg, 'error');
        }
    }
    
    /**
     * Ver detalle de un corte
     */
    public function showDetail() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        // SuperAdmin, Gestor y Líder
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para ver cortes', 'error');
        }
        
        $corteId = intval($_GET['id'] ?? 0);
        if ($corteId <= 0) {
            redirectWithMessage('cortes/', 'Corte no encontrado', 'error');
        }
        
        $corte = $this->corteModel->getCorteById($corteId);
        if (!$corte) {
            redirectWithMessage('cortes/', 'Corte no encontrado', 'error');
        }

        // Líder solo puede ver detalles de sus propios cortes
        if ($currentUser['rol'] === 'Líder' && (int)($corte['usuario_id'] ?? 0) !== (int)$currentUser['id']) {
            redirectWithMessage('cortes/mis_cortes.php', 'No tienes permisos para ver este corte', 'error');
        }
        
        // Filtro de búsqueda
        $filters = [];
        if (!empty($_GET['search'])) {
            $filters['search'] = cleanInput($_GET['search']);
        }
        
        $detalle = $this->corteModel->getDetalleCorte($corteId, $filters);
        
        include __DIR__ . '/../views/cortes/detail.php';
    }

    /**
     * Exportar detalle de corte a CSV
     */
    public function exportCorte() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();

        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para exportar cortes', 'error');
        }

        $corteId = intval($_GET['id'] ?? 0);
        if ($corteId <= 0) {
            redirectWithMessage('cortes/', 'Corte no encontrado', 'error');
        }

        $corte = $this->corteModel->getCorteById($corteId);
        if (!$corte) {
            redirectWithMessage('cortes/', 'Corte no encontrado', 'error');
        }

        if ($currentUser['rol'] === 'Líder' && (int)($corte['usuario_id'] ?? 0) !== (int)$currentUser['id']) {
            redirectWithMessage('cortes/mis_cortes.php', 'No tienes permisos para exportar este corte', 'error');
        }

        $detalle = $this->corteModel->getDetalleCorte($corteId, []);

        $filename = 'corte_' . $corteId . '_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'Corte',
            'Fecha Inicio',
            'Fecha Fin',
            'Ranking',
            'Activista',
            'Tareas Asignadas',
            'Tareas Entregadas',
            'Cumplimiento (%)',
            'Fecha Cálculo'
        ]);

        foreach ($detalle as $row) {
            fputcsv($output, [
                $corte['nombre'],
                $corte['fecha_inicio'],
                $corte['fecha_fin'],
                $row['ranking_posicion'] ?? '',
                $row['nombre_completo'] ?? '',
                $row['tareas_asignadas'] ?? 0,
                $row['tareas_entregadas'] ?? 0,
                $row['porcentaje_cumplimiento'] ?? 0,
                $row['fecha_calculo'] ?? ''
            ]);
        }

        fclose($output);
        exit();
    }
    
    /**
     * Cerrar un corte
     */
    public function cerrarCorte() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('cortes/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('cortes/', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin
        if ($currentUser['rol'] !== 'SuperAdmin') {
            redirectWithMessage('cortes/', 'No tienes permisos para cerrar cortes', 'error');
        }
        
        $corteId = intval($_POST['corte_id'] ?? 0);
        
        $result = $this->corteModel->cerrarCorte($corteId);
        
        if ($result) {
            redirectWithMessage('cortes/detail.php?id=' . $corteId, 'Corte cerrado exitosamente', 'success');
        } else {
            redirectWithMessage('cortes/detail.php?id=' . $corteId, 'Error al cerrar el corte', 'error');
        }
    }
    
    /**
     * Ver tareas de un activista en el corte
     */
    public function showTareasActivista() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        // SuperAdmin, Gestor y Líder
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para ver tareas', 'error');
        }
        
        $corteId = intval($_GET['corte_id'] ?? 0);
        $usuarioId = intval($_GET['usuario_id'] ?? 0);
        
        if ($corteId <= 0 || $usuarioId <= 0) {
            redirectWithMessage('cortes/', 'Datos inválidos', 'error');
        }
        
        $corte = $this->corteModel->getCorteById($corteId);
        if (!$corte) {
            redirectWithMessage('cortes/', 'Corte no encontrado', 'error');
        }
        
        // Para líderes, verificar que el usuario pertenece a su equipo
        if ($currentUser['rol'] === 'Líder') {
            require_once __DIR__ . '/../models/user.php';
            $userModel = new User();
            $usuario = $userModel->getUserById($usuarioId);
            if (!$usuario || $usuario['lider_id'] != $currentUser['id']) {
                redirectWithMessage('cortes/mis_cortes.php', 'No tienes permisos para ver este usuario', 'error');
            }
        }
        
        // Obtener info del activista del detalle del corte
        $detalleActivista = $this->corteModel->getDetalleCorte($corteId, ['usuario_id' => $usuarioId]);
        if (empty($detalleActivista)) {
            redirectWithMessage('cortes/detail.php?id=' . $corteId, 'Activista no encontrado en este corte', 'error');
        }
        $activista = $detalleActivista[0];
        
        // Obtener tareas
        $tareas = $this->corteModel->getTareasActivista($corteId, $usuarioId);
        
        include __DIR__ . '/../views/cortes/tareas_activista.php';
    }
    
    /**
     * Eliminar un corte
     */
    public function deleteCorte() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('cortes/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('cortes/', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin
        if ($currentUser['rol'] !== 'SuperAdmin') {
            redirectWithMessage('cortes/', 'No tienes permisos para eliminar cortes', 'error');
        }
        
        $corteId = intval($_POST['corte_id'] ?? 0);
        
        $result = $this->corteModel->deleteCorte($corteId);
        
        if ($result) {
            redirectWithMessage('cortes/', 'Corte eliminado exitosamente', 'success');
        } else {
            redirectWithMessage('cortes/', 'Error al eliminar el corte', 'error');
        }
    }
    
    /**
     * Eliminar múltiples cortes
     */
    public function deleteMultipleCortes() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('cortes/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('cortes/', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin
        if ($currentUser['rol'] !== 'SuperAdmin') {
            redirectWithMessage('cortes/', 'No tienes permisos para eliminar cortes', 'error');
        }
        
        $corteIds = $_POST['corte_ids'] ?? [];
        
        if (empty($corteIds) || !is_array($corteIds)) {
            redirectWithMessage('cortes/', 'No se seleccionaron cortes para eliminar', 'warning');
        }
        
        $deleted = 0;
        $errors = 0;
        
        foreach ($corteIds as $corteId) {
            $corteId = intval($corteId);
            if ($corteId > 0) {
                $result = $this->corteModel->deleteCorte($corteId);
                if ($result) {
                    $deleted++;
                } else {
                    $errors++;
                }
            }
        }
        
        if ($deleted > 0) {
            $message = "Se eliminaron $deleted corte(s) exitosamente";
            if ($errors > 0) {
                $message .= ", pero hubo $errors error(es)";
            }
            redirectWithMessage('cortes/', $message, 'success');
        } else {
            redirectWithMessage('cortes/', 'No se pudieron eliminar los cortes seleccionados', 'error');
        }
    }
    
    /**
     * Crear cortes masivos para todos los líderes
     */
    public function createMassiveCortes() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('reports/activists.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('reports/activists.php', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin
        if ($currentUser['rol'] !== 'SuperAdmin') {
            redirectWithMessage('reports/activists.php', 'No tienes permisos para esta acción', 'error');
        }
        
        // Validar datos
        $errors = $this->validateCorteData($_POST);
        if (!empty($errors)) {
            $_SESSION['message'] = implode(', ', $errors);
            $_SESSION['message_type'] = 'error';
            redirectWithMessage('reports/activists.php', implode(', ', $errors), 'error');
        }
        
        // Obtener todos los líderes activos
        require_once __DIR__ . '/../models/user.php';
        $userModel = new User();
        $leaders = $userModel->getActiveLiders();
        
        if (empty($leaders)) {
            redirectWithMessage('reports/activists.php', 'No hay líderes activos', 'error');
        }
        
        $createdCount = 0;
        $errors = [];
        
        // Crear un corte por cada líder
        foreach ($leaders as $leader) {
            try {
                $data = [
                    'nombre' => cleanInput($_POST['nombre']) . ' - ' . $leader['nombre_completo'],
                    'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
                    'fecha_inicio' => cleanInput($_POST['fecha_inicio']),
                    'fecha_fin' => cleanInput($_POST['fecha_fin']),
                    'creado_por' => $currentUser['id'],
                    'grupo_id' => $leader['grupo_id'] ?? null,
                    'actividad_id' => null,
                    'usuario_id' => $leader['id']
                ];
                
                $corteId = $this->corteModel->crearCorte($data);
                
                if ($corteId) {
                    $createdCount++;
                } else {
                    $errors[] = 'Error al crear corte para líder: ' . $leader['nombre_completo'];
                }
            } catch (Exception $e) {
                $errors[] = 'Error para líder ' . $leader['nombre_completo'] . ': ' . $e->getMessage();
            }
        }
        
        if ($createdCount > 0) {
            $message = "Se crearon exitosamente $createdCount cortes (uno por cada líder)";
            if (!empty($errors)) {
                $message .= '. Algunos cortes fallaron: ' . implode(', ', $errors);
            }
            redirectWithMessage('cortes/', $message, 'success');
        } else {
            redirectWithMessage('reports/activists.php', 'Error al crear los cortes: ' . implode(', ', $errors), 'error');
        }
    }
    
    /**
     * Validar datos del corte
     */
    private function validateCorteData($data) {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre del corte es obligatorio';
        }
        
        if (empty($data['fecha_inicio'])) {
            $errors[] = 'La fecha de inicio es obligatoria';
        }
        
        if (empty($data['fecha_fin'])) {
            $errors[] = 'La fecha de fin es obligatoria';
        }
        
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            if (strtotime($data['fecha_inicio']) > strtotime($data['fecha_fin'])) {
                $errors[] = 'La fecha de inicio no puede ser posterior a la fecha de fin';
            }
        }
        
        return $errors;
    }
}
