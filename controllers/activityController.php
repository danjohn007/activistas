<?php
/**
 * Controlador de Actividades
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/activity.php';
require_once __DIR__ . '/../models/user.php';

class ActivityController {
    private $auth;
    private $activityModel;
    private $userModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->activityModel = new Activity();
        $this->userModel = new User();
    }
    
    // Mostrar lista de actividades
    public function listActivities() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        $filters = [];
        
        // Aplicar filtros según el rol
        switch ($currentUser['rol']) {
            case 'Activista':
                $filters['usuario_id'] = $currentUser['id'];
                break;
            case 'Líder':
                $filters['lider_id'] = $currentUser['id'];
                break;
        }
        
        // Aplicar filtros de la URL
        if (!empty($_GET['tipo'])) {
            $filters['tipo_actividad_id'] = intval($_GET['tipo']);
        }
        if (!empty($_GET['estado'])) {
            $filters['estado'] = cleanInput($_GET['estado']);
        }
        if (!empty($_GET['fecha_desde'])) {
            $filters['fecha_desde'] = cleanInput($_GET['fecha_desde']);
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filters['fecha_hasta'] = cleanInput($_GET['fecha_hasta']);
        }
        
        $activities = $this->activityModel->getActivities($filters);
        $activityTypes = $this->activityModel->getActivityTypes();
        
        include __DIR__ . '/../views/activities/list.php';
    }
    
    // Mostrar formulario de nueva actividad
    public function showCreateForm() {
        $this->auth->requireAuth();
        
        $activityTypes = $this->activityModel->getActivityTypes();
        
        include __DIR__ . '/../views/activities/create.php';
    }
    
    // Crear nueva actividad
    public function createActivity() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/create.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/create.php', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        $activityData = [
            'usuario_id' => $currentUser['id'],
            'user_role' => $currentUser['rol'], // Add user role for pending task logic
            'tipo_actividad_id' => intval($_POST['tipo_actividad_id'] ?? 0),
            'titulo' => cleanInput($_POST['titulo'] ?? ''),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'fecha_actividad' => cleanInput($_POST['fecha_actividad'] ?? '')
        ];
        
        // Validar datos
        $errors = $this->validateActivityData($activityData, $currentUser['rol']);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirectWithMessage('activities/create.php', 'Por favor corrige los errores', 'error');
        }
        
        $activityId = $this->activityModel->createActivity($activityData);
        
        if ($activityId) {
            // Procesar evidencias si se subieron
            $this->processEvidenceFiles($activityId);
            
            redirectWithMessage('activities/', 'Actividad creada exitosamente', 'success');
        } else {
            redirectWithMessage('activities/create.php', 'Error al crear actividad', 'error');
        }
    }
    
    // Mostrar detalle de actividad
    public function showActivity() {
        $this->auth->requireAuth();
        
        $activityId = intval($_GET['id'] ?? 0);
        if ($activityId <= 0) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        $activity = $this->activityModel->getActivityById($activityId);
        if (!$activity) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        // Verificar permisos
        $currentUser = $this->auth->getCurrentUser();
        if (!$this->canViewActivity($currentUser, $activity)) {
            redirectWithMessage('activities/', 'No tiene permisos para ver esta actividad', 'error');
        }
        
        $evidence = $this->activityModel->getActivityEvidence($activityId);
        
        include __DIR__ . '/../views/activities/detail.php';
    }
    
    // Mostrar formulario de edición
    public function showEditForm() {
        $this->auth->requireAuth();
        
        $activityId = intval($_GET['id'] ?? 0);
        if ($activityId <= 0) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        $activity = $this->activityModel->getActivityById($activityId);
        if (!$activity) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        // Verificar permisos
        $currentUser = $this->auth->getCurrentUser();
        if (!$this->canEditActivity($currentUser, $activity)) {
            redirectWithMessage('activities/', 'No tiene permisos para editar esta actividad', 'error');
        }
        
        $activityTypes = $this->activityModel->getActivityTypes();
        
        include __DIR__ . '/../views/activities/edit.php';
    }
    
    // Actualizar actividad
    public function updateActivity() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/', 'Token de seguridad inválido', 'error');
        }
        
        $activityId = intval($_POST['activity_id'] ?? 0);
        if ($activityId <= 0) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        $activity = $this->activityModel->getActivityById($activityId);
        if (!$activity) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        // Verificar permisos
        $currentUser = $this->auth->getCurrentUser();
        if (!$this->canEditActivity($currentUser, $activity)) {
            redirectWithMessage('activities/', 'No tiene permisos para editar esta actividad', 'error');
        }
        
        $updateData = [
            'titulo' => cleanInput($_POST['titulo'] ?? ''),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'fecha_actividad' => cleanInput($_POST['fecha_actividad'] ?? ''),
            'lugar' => cleanInput($_POST['lugar'] ?? ''),
            'alcance_estimado' => intval($_POST['alcance_estimado'] ?? 0),
            'estado' => cleanInput($_POST['estado'] ?? '')
        ];
        
        $result = $this->activityModel->updateActivity($activityId, $updateData);
        
        if ($result) {
            // Procesar nuevas evidencias si se subieron
            $this->processEvidenceFiles($activityId);
            
            redirectWithMessage("activities/detail.php?id=$activityId", 'Actividad actualizada exitosamente', 'success');
        } else {
            redirectWithMessage("activities/edit.php?id=$activityId", 'Error al actualizar actividad', 'error');
        }
    }
    
    // Agregar evidencia
    public function addEvidence() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/', 'Token de seguridad inválido', 'error');
        }
        
        $activityId = intval($_POST['activity_id'] ?? 0);
        $evidenceType = cleanInput($_POST['evidence_type'] ?? '');
        $content = cleanInput($_POST['content'] ?? '');
        
        if ($activityId <= 0) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        $activity = $this->activityModel->getActivityById($activityId);
        if (!$activity) {
            redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
        }
        
        // Verificar permisos
        $currentUser = $this->auth->getCurrentUser();
        if (!$this->canEditActivity($currentUser, $activity)) {
            redirectWithMessage('activities/', 'No tiene permisos para agregar evidencia', 'error');
        }
        
        $fileName = null;
        
        // Procesar archivo si se subió
        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mp3', 'wav', 'pdf', 'doc', 'docx'];
            $uploadResult = uploadFile($_FILES['evidence_file'], __DIR__ . '/../public/assets/uploads/evidence', $allowedTypes);
            
            if ($uploadResult['success']) {
                $fileName = $uploadResult['filename'];
            } else {
                redirectWithMessage("activities/detail.php?id=$activityId", $uploadResult['error'], 'error');
            }
        }
        
        $evidenceId = $this->activityModel->addEvidence($activityId, $evidenceType, $fileName, $content);
        
        if ($evidenceId) {
            redirectWithMessage("activities/detail.php?id=$activityId", 'Evidencia agregada exitosamente', 'success');
        } else {
            redirectWithMessage("activities/detail.php?id=$activityId", 'Error al agregar evidencia', 'error');
        }
    }
    
    // Validar datos de actividad
    private function validateActivityData($data, $userRole = null) {
        $errors = [];
        
        if (empty($data['titulo'])) {
            $errors[] = 'El título es obligatorio';
        }
        
        if ($data['tipo_actividad_id'] <= 0) {
            $errors[] = 'Debe seleccionar un tipo de actividad';
        }
        
        if (empty($data['fecha_actividad'])) {
            $errors[] = 'La fecha de actividad es obligatoria';
        } elseif (!strtotime($data['fecha_actividad'])) {
            $errors[] = 'Formato de fecha inválido';
        }
        
        // Only validate alcance_estimado for SuperAdmin and Gestor roles
        if (in_array($userRole, ['SuperAdmin', 'Gestor']) && isset($data['alcance_estimado']) && $data['alcance_estimado'] < 0) {
            $errors[] = 'El alcance estimado no puede ser negativo';
        }
        
        return $errors;
    }
    
    // Verificar si puede ver la actividad
    private function canViewActivity($user, $activity) {
        // SuperAdmin y Gestor pueden ver todo
        if (in_array($user['rol'], ['SuperAdmin', 'Gestor'])) {
            return true;
        }
        
        // Líder puede ver actividades propias y de sus activistas
        if ($user['rol'] === 'Líder') {
            return $activity['usuario_id'] == $user['id'] || 
                   $this->isUserActivistOfLeader($activity['usuario_id'], $user['id']);
        }
        
        // Activista solo puede ver sus propias actividades
        return $activity['usuario_id'] == $user['id'];
    }
    
    // Verificar si puede editar la actividad
    private function canEditActivity($user, $activity) {
        // SuperAdmin puede editar todo
        if ($user['rol'] === 'SuperAdmin') {
            return true;
        }
        
        // Gestor puede editar actividades de usuarios bajo su gestión
        if ($user['rol'] === 'Gestor') {
            return true; // Por simplicidad, gestor puede editar todo
        }
        
        // Líder puede editar actividades propias y de sus activistas
        if ($user['rol'] === 'Líder') {
            return $activity['usuario_id'] == $user['id'] || 
                   $this->isUserActivistOfLeader($activity['usuario_id'], $user['id']);
        }
        
        // Activista solo puede editar sus propias actividades
        return $activity['usuario_id'] == $user['id'];
    }
    
    // Verificar si un usuario es activista de un líder
    private function isUserActivistOfLeader($userId, $liderId) {
        $user = $this->userModel->getUserById($userId);
        return $user && $user['lider_id'] == $liderId;
    }
    
    // Procesar archivos de evidencia
    private function processEvidenceFiles($activityId) {
        // Procesar múltiples archivos de evidencia si se subieron
        if (isset($_FILES['evidence_files'])) {
            $files = $_FILES['evidence_files'];
            $fileCount = count($files['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mp3', 'wav'];
                    $uploadResult = uploadFile($file, __DIR__ . '/../public/assets/uploads/evidence', $allowedTypes);
                    
                    if ($uploadResult['success']) {
                        // Determinar tipo de evidencia basado en la extensión
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $evidenceType = 'foto'; // Por defecto
                        
                        if (in_array($extension, ['mp4', 'avi'])) {
                            $evidenceType = 'video';
                        } elseif (in_array($extension, ['mp3', 'wav'])) {
                            $evidenceType = 'audio';
                        }
                        
                        $this->activityModel->addEvidence($activityId, $evidenceType, $uploadResult['filename']);
                    }
                }
            }
        }
    }
}
?>