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
        
        $cortes = $this->corteModel->getCortes($filters);
        
        include __DIR__ . '/../views/cortes/list.php';
    }
    
    /**
     * Mostrar formulario de crear corte
     */
    public function showCreateForm() {
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin y Gestor pueden crear cortes
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para crear cortes', 'error');
        }
        
        // Cargar grupos
        require_once __DIR__ . '/../models/group.php';
        $groupModel = new Group();
        $groups = $groupModel->getAllGroups();
        
        // Cargar tipos de actividades
        require_once __DIR__ . '/../models/activityType.php';
        $activityTypeModel = new ActivityType();
        $activityTypes = $activityTypeModel->getAllActivityTypes();
        
        // Cargar activistas
        require_once __DIR__ . '/../models/user.php';
        $userModel = new User();
        $activistas = $userModel->getAllUsers(['rol' => 'Activista', 'estado' => 'activo']);
        
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
        
        // Solo SuperAdmin y Gestor
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
            redirectWithMessage('cortes/', 'No tienes permisos para crear cortes', 'error');
        }
        
        // Validar datos
        $errors = $this->validateCorteData($_POST);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirectWithMessage('cortes/create.php', 'Por favor corrige los errores', 'error');
        }
        
        $data = [
            'nombre' => cleanInput($_POST['nombre']),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'fecha_inicio' => cleanInput($_POST['fecha_inicio']),
            'fecha_fin' => cleanInput($_POST['fecha_fin']),
            'creado_por' => $currentUser['id'],
            'grupo_id' => !empty($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null,
            'actividad_id' => !empty($_POST['actividad_id']) ? intval($_POST['actividad_id']) : null,
            'usuario_id' => !empty($_POST['usuario_id']) ? intval($_POST['usuario_id']) : null
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
        
        // Solo SuperAdmin y Gestor
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
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
        
        // Filtro de búsqueda
        $filters = [];
        if (!empty($_GET['search'])) {
            $filters['search'] = cleanInput($_GET['search']);
        }
        
        $detalle = $this->corteModel->getDetalleCorte($corteId, $filters);
        
        include __DIR__ . '/../views/cortes/detail.php';
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
        
        // Solo SuperAdmin y Gestor
        if (!in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
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
     * Crear cortes masivos para todos los grupos
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
        
        // Obtener todos los grupos activos
        require_once __DIR__ . '/../models/group.php';
        $groupModel = new Group();
        $groups = $groupModel->getAllGroups(['activo' => 1]);
        
        if (empty($groups)) {
            redirectWithMessage('reports/activists.php', 'No hay grupos activos', 'error');
        }
        
        $createdCount = 0;
        $errors = [];
        
        // Crear un corte por cada grupo
        foreach ($groups as $group) {
            try {
                $data = [
                    'nombre' => cleanInput($_POST['nombre']) . ' - ' . $group['nombre'],
                    'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
                    'fecha_inicio' => cleanInput($_POST['fecha_inicio']),
                    'fecha_fin' => cleanInput($_POST['fecha_fin']),
                    'creado_por' => $currentUser['id'],
                    'grupo_id' => $group['id'],
                    'actividad_id' => null,
                    'usuario_id' => null
                ];
                
                $corteId = $this->corteModel->crearCorte($data);
                
                if ($corteId) {
                    $createdCount++;
                } else {
                    $errors[] = 'Error al crear corte para grupo: ' . $group['nombre'];
                }
            } catch (Exception $e) {
                $errors[] = 'Error en grupo ' . $group['nombre'] . ': ' . $e->getMessage();
            }
        }
        
        if ($createdCount > 0) {
            $message = "Se crearon exitosamente $createdCount cortes (uno por cada grupo)";
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
