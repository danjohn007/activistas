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
        
        // REQUISITO CRÍTICO: Validar que hay archivos obligatorios (foto/evidencia)
        // Una tarea NO puede completarse sin subir al menos un archivo de evidencia
        if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo']['name']) || empty($_FILES['archivo']['name'][0])) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'No se puede completar la tarea: Debe subir al menos una foto/archivo como evidencia (obligatorio)', 'error');
        }
        
        // Validar que al menos un archivo fue seleccionado correctamente
        $hasValidFile = false;
        for ($i = 0; $i < count($_FILES['archivo']['name']); $i++) {
            if (!empty($_FILES['archivo']['name'][$i]) && $_FILES['archivo']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $hasValidFile = true;
                break;
            }
        }
        
        if (!$hasValidFile) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'No se puede completar la tarea: Debe subir al menos una foto/archivo como evidencia (obligatorio)', 'error');
        }
        
        // Procesar archivos múltiples
        $uploadedFiles = [];
        for ($i = 0; $i < count($_FILES['archivo']['name']); $i++) {
            if ($_FILES['archivo']['error'][$i] === UPLOAD_ERR_OK) {
                $fileData = [
                    'name' => $_FILES['archivo']['name'][$i],
                    'type' => $_FILES['archivo']['type'][$i],
                    'tmp_name' => $_FILES['archivo']['tmp_name'][$i],
                    'error' => $_FILES['archivo']['error'][$i],
                    'size' => $_FILES['archivo']['size'][$i]
                ];
                
                $evidenceFile = $this->processEvidenceFile($fileData, $taskId);
                if ($evidenceFile) {
                    $uploadedFiles[] = $evidenceFile;
                } else {
                    redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                        'Error al procesar uno de los archivos de evidencia', 'error');
                }
            } else if ($_FILES['archivo']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                // Handle upload errors for files that were attempted but failed
                $errorMessage = 'Error al subir el archivo';
                switch ($_FILES['archivo']['error'][$i]) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage = 'El archivo excede el tamaño máximo permitido';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage = 'El archivo se subió parcialmente';
                        break;
                    default:
                        $errorMessage = 'Error desconocido al subir el archivo';
                }
                redirectWithMessage('tasks/complete.php?id=' . $taskId, $errorMessage, 'error');
            }
        }
        
        // VALIDACIÓN FINAL: Asegurar que al menos un archivo fue procesado exitosamente
        if (empty($uploadedFiles)) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'No se puede completar la tarea: No se pudo procesar ningún archivo de evidencia. Debe subir al menos una foto/archivo.', 'error');
        }
        
        // El contenido sigue siendo requerido además del archivo
        if (empty($evidenceContent)) {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'Debe proporcionar una descripción de la evidencia', 'error');
        }
        
        // Agregar evidencias (una por cada archivo)
        $successCount = 0;
        foreach ($uploadedFiles as $index => $evidenceFile) {
            // Para el primer archivo, usar la descripción completa
            // Para archivos adicionales, agregar un indicador
            $content = $index === 0 ? $evidenceContent : $evidenceContent . " (Archivo " . ($index + 1) . ")";
            
            $result = $this->activityModel->addEvidence($taskId, $evidenceType, $evidenceFile, $content);
            
            if ($result['success']) {
                $successCount++;
            }
        }
        
        if ($successCount > 0) {
            $fileCount = count($uploadedFiles);
            redirectWithMessage('tasks/', "Tarea completada exitosamente con $fileCount archivo(s) de evidencia. Se han actualizado los rankings.", 'success');
        } else {
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'Error al completar la tarea', 'error');
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
            // Return only the filename - path construction will be handled in display
            return $filename;
        }
        
        return false;
    }
}
?>