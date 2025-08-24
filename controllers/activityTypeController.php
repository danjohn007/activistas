<?php
/**
 * Controlador de Tipos de Actividades
 * Solo accesible para usuarios SuperAdmin
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/activityType.php';

class ActivityTypeController {
    private $auth;
    private $activityTypeModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->activityTypeModel = new ActivityType();
    }
    
    // Listar tipos de actividades
    public function listActivityTypes() {
        // Solo SuperAdmin puede acceder
        $this->auth->requireRole(['SuperAdmin']);
        
        $activityTypes = $this->activityTypeModel->getAllActivityTypes();
        
        include __DIR__ . '/../views/activity-types/list.php';
    }
    
    // Mostrar formulario de creación
    public function showCreateForm() {
        $this->auth->requireRole(['SuperAdmin']);
        
        include __DIR__ . '/../views/activity-types/create.php';
    }
    
    // Crear nuevo tipo de actividad
    public function createActivityType() {
        $this->auth->requireRole(['SuperAdmin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activity-types/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activity-types/create.php', 'Token de seguridad inválido', 'error');
        }
        
        $data = [
            'nombre' => cleanInput($_POST['nombre'] ?? ''),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Validar datos
        $errors = $this->validateActivityTypeData($data);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirectWithMessage('activity-types/create.php', 'Por favor corrige los errores', 'error');
        }
        
        $result = $this->activityTypeModel->createActivityType($data);
        
        if ($result) {
            redirectWithMessage('activity-types/', 'Tipo de actividad creado exitosamente', 'success');
        } else {
            redirectWithMessage('activity-types/create.php', 'Error al crear el tipo de actividad', 'error');
        }
    }
    
    // Mostrar formulario de edición
    public function showEditForm($id) {
        $this->auth->requireRole(['SuperAdmin']);
        
        $activityType = $this->activityTypeModel->getActivityTypeById($id);
        
        if (!$activityType) {
            redirectWithMessage('activity-types/', 'Tipo de actividad no encontrado', 'error');
        }
        
        include __DIR__ . '/../views/activity-types/edit.php';
    }
    
    // Actualizar tipo de actividad
    public function updateActivityType($id) {
        $this->auth->requireRole(['SuperAdmin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activity-types/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activity-types/edit.php?id=' . $id, 'Token de seguridad inválido', 'error');
        }
        
        $data = [
            'nombre' => cleanInput($_POST['nombre'] ?? ''),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Validar datos
        $errors = $this->validateActivityTypeData($data, $id);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirectWithMessage('activity-types/edit.php?id=' . $id, 'Por favor corrige los errores', 'error');
        }
        
        $result = $this->activityTypeModel->updateActivityType($id, $data);
        
        if ($result) {
            redirectWithMessage('activity-types/', 'Tipo de actividad actualizado exitosamente', 'success');
        } else {
            redirectWithMessage('activity-types/edit.php?id=' . $id, 'Error al actualizar el tipo de actividad', 'error');
        }
    }
    
    // Eliminar tipo de actividad (desactivar)
    public function deleteActivityType($id) {
        $this->auth->requireRole(['SuperAdmin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activity-types/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activity-types/', 'Token de seguridad inválido', 'error');
        }
        
        // En lugar de eliminar, desactivamos el tipo de actividad
        $result = $this->activityTypeModel->deactivateActivityType($id);
        
        if ($result) {
            redirectWithMessage('activity-types/', 'Tipo de actividad desactivado exitosamente', 'success');
        } else {
            redirectWithMessage('activity-types/', 'Error al desactivar el tipo de actividad', 'error');
        }
    }
    
    // Obtener descripción de tipo de actividad para AJAX
    public function getActivityTypeDescription($id) {
        $this->auth->requireAuth();
        
        header('Content-Type: application/json');
        
        $activityType = $this->activityTypeModel->getActivityTypeById($id);
        
        if ($activityType) {
            echo json_encode([
                'success' => true,
                'descripcion' => $activityType['descripcion']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de actividad no encontrado'
            ]);
        }
        exit;
    }
    
    // Validar datos del tipo de actividad
    private function validateActivityTypeData($data, $excludeId = null) {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede exceder 100 caracteres';
        }
        
        if (strlen($data['descripcion']) > 1000) {
            $errors[] = 'La descripción no puede exceder 1000 caracteres';
        }
        
        // Verificar si el nombre ya existe (excluyendo el actual en caso de edición)
        if (!empty($data['nombre'])) {
            $existing = $this->activityTypeModel->getActivityTypeByName($data['nombre']);
            if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
                $errors[] = 'Ya existe un tipo de actividad con ese nombre';
            }
        }
        
        return $errors;
    }
}
?>