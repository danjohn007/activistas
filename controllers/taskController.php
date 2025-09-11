<?php
/**
 * Controlador de Tareas
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/activity.php';
require_once __DIR__ . '/../models/user.php';

class TaskController {
    private $auth;
    private $activityModel;
    private $userModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->activityModel = new Activity();
        $this->userModel = new User();
    }
    
    // Mostrar lista de tareas pendientes
    public function listPendingTasks() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // All authenticated users can view and manage their pending tasks
        // Removed role restriction to allow all users to see their tasks
        
        // Obtener tareas pendientes para el usuario actual
        $pendingTasks = $this->activityModel->getPendingTasks($currentUser['id']);
        
        include __DIR__ . '/../views/tasks/list.php';
    }
    
    // Mostrar formulario para completar tarea
    public function showCompleteForm($taskId) {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // All authenticated users can complete tasks independently
        // Removed role restriction to allow all users to complete tasks
        
        // Verificar que la tarea existe y pertenece al usuario
        $task = $this->activityModel->getActivityById($taskId);
        if (!$task || $task['usuario_id'] != $currentUser['id'] || !$task['tarea_pendiente']) {
            redirectWithMessage('tasks/', 'Tarea no encontrada o no autorizada', 'error');
        }
        
        // Verificar si la tarea ya está completada
        if ($task['estado'] === 'completada') {
            redirectWithMessage('tasks/', 'Esta tarea ya está completada', 'info');
        }
        
        // Verificar si ya tiene evidencias bloqueadas
        if (!$this->activityModel->canModifyEvidence($taskId)) {
            redirectWithMessage('tasks/', 'Esta tarea ya tiene evidencias bloqueadas', 'warning');
        }
        
        include __DIR__ . '/../views/tasks/complete.php';
    }
    
    // Completar tarea subiendo evidencia
    public function completeTask($taskId) {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('tasks/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // All authenticated users can complete tasks independently
        // Removed role restriction to allow all users to complete tasks
        
        // Verificar que la tarea existe y pertenece al usuario
        $task = $this->activityModel->getActivityById($taskId);
        if (!$task || $task['usuario_id'] != $currentUser['id'] || !$task['tarea_pendiente']) {
            redirectWithMessage('tasks/', 'Tarea no encontrada o no autorizada', 'error');
        }
        
        // Verificar si la tarea ya está completada
        if ($task['estado'] === 'completada') {
            redirectWithMessage('tasks/', 'Esta tarea ya está completada', 'info');
        }
        
        // Procesar evidencia
        $evidenceType = cleanInput($_POST['tipo_evidencia'] ?? '');
        $evidenceContent = cleanInput($_POST['contenido'] ?? '');
        
        if (empty($evidenceType)) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'Debe seleccionar un tipo de evidencia', 'error');
        }
        
        $evidenceFile = null;
        
        // Validar que hay archivo obligatorio (según requisitos)
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'Debe subir un archivo como evidencia (obligatorio)', 'error');
        }
        
        // Procesar archivo obligatorio
        $evidenceFile = $this->processEvidenceFile($_FILES['archivo'], $taskId);
        if (!$evidenceFile) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'Error al procesar el archivo de evidencia', 'error');
        }
        
        // El contenido sigue siendo requerido además del archivo
        if (empty($evidenceContent)) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'Debe proporcionar una descripción de la evidencia', 'error');
        }
        
        // Agregar evidencia (esto automáticamente marca la tarea como completada)
        $result = $this->activityModel->addEvidence($taskId, $evidenceType, $evidenceFile, $evidenceContent);
        
        if ($result['success']) {
            redirectWithMessage('tasks/', 'Tarea completada exitosamente. Se han actualizado los rankings.', 'success');
        } else {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                $result['error'] ?? 'Error al completar la tarea', 'error');
        }
    }
    
    // Procesar archivo de evidencia
    private function processEvidenceFile($file, $taskId) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mpeg', 'audio/wav'];
        $maxSize = 20 * 1024 * 1024; // 20MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        $uploadDir = UPLOADS_DIR . '/evidencias/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'task_' . $taskId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Return relative path from public root
            return 'assets/uploads/evidencias/' . $filename;
        }
        
        return false;
    }
}
?>