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
        
        // Verificar si la tarea está vencida
        if ($this->isTaskExpired($task)) {
            redirectWithMessage('tasks/', 'Esta tarea ya está vencida y no se puede completar', 'error');
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
        
        // Verificar si la tarea está vencida
        if ($this->isTaskExpired($task)) {
            redirectWithMessage('tasks/', 'Esta tarea ya está vencida y no se puede completar', 'error');
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
        
        // DIAGNÓSTICO 1: Verificar que $_FILES['archivo'] existe
        if (!isset($_FILES['archivo'])) {
            error_log("ERROR CARGA: No se encontró \$_FILES['archivo']. POST size: " . strlen(file_get_contents('php://input')));
            error_log("Configuración PHP - upload_max_filesize: " . ini_get('upload_max_filesize') . ", post_max_size: " . ini_get('post_max_size'));
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'ERROR: No se encontró tu archivo. Verifica que: 1) Seleccionaste un archivo, 2) El archivo no es muy grande (máx 20MB), 3) Tu conexión a internet es estable. Código: NO_FILES', 'error');
        }
        
        // DIAGNÓSTICO 2: Verificar estructura del array
        if (!is_array($_FILES['archivo']['name'])) {
            error_log("ERROR CARGA: \$_FILES['archivo']['name'] no es un array");
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'ERROR: Formato de archivo incorrecto. Intenta seleccionar el archivo nuevamente. Código: INVALID_FORMAT', 'error');
        }
        
        // DIAGNÓSTICO 3: Verificar que se proporcionó al menos un nombre de archivo
        if (empty($_FILES['archivo']['name'][0])) {
            error_log("ERROR CARGA: \$_FILES['archivo']['name'][0] está vacío. Error code: " . ($_FILES['archivo']['error'][0] ?? 'N/A'));
            
            // Verificar si es problema de tamaño
            if (isset($_FILES['archivo']['error'][0]) && $_FILES['archivo']['error'][0] == UPLOAD_ERR_INI_SIZE) {
                redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                    'ERROR: El archivo es demasiado grande. Tamaño máximo permitido: ' . ini_get('upload_max_filesize') . '. Código: FILE_TOO_LARGE', 'error');
            }
            
            redirectWithMessage('tasks/complete.php?id=' . $taskId, 
                'ERROR: No se encontró tu archivo. Asegúrate de seleccionar al menos un archivo antes de enviar el formulario. Código: EMPTY_FILE', 'error');
        }
        
        // Validar que al menos un archivo fue seleccionado correctamente
        $hasValidFile = false;
        $uploadErrors = [];
        
        for ($i = 0; $i < count($_FILES['archivo']['name']); $i++) {
            if (!empty($_FILES['archivo']['name'][$i]) && $_FILES['archivo']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                // Verificar errores específicos
                if ($_FILES['archivo']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errorMsg = 'Archivo ' . ($i+1) . ': ';
                    switch ($_FILES['archivo']['error'][$i]) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMsg .= 'Demasiado grande (máx 20MB)';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMsg .= 'Se subió parcialmente (verifica tu conexión)';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMsg .= 'Falta directorio temporal (error del servidor)';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMsg .= 'No se puede escribir en disco (error del servidor)';
                            break;
                        default:
                            $errorMsg .= 'Error desconocido (código: ' . $_FILES['archivo']['error'][$i] . ')';
                    }
                    $uploadErrors[] = $errorMsg;
                    error_log("ERROR CARGA archivo $i: " . $errorMsg);
                } else {
                    $hasValidFile = true;
                }
            }
        }
        
        if (!$hasValidFile) {
            $errorMessage = 'No se puede completar la tarea: No se encontró ningún archivo válido.';
            if (!empty($uploadErrors)) {
                $errorMessage .= ' Errores: ' . implode(', ', $uploadErrors);
            } else {
                $errorMessage .= ' Debe subir al menos una foto/archivo como evidencia (obligatorio). Código: NO_VALID_FILES';
            }
            error_log("ERROR CARGA: Sin archivos válidos. Detalles: " . json_encode($_FILES));
            redirectWithMessage('tasks/complete.php?id=' . $taskId, $errorMessage, 'error');
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
        
        // Log para diagnóstico
        error_log("Procesando archivo: {$file['name']}, tipo: {$file['type']}, tamaño: {$file['size']}");
        
        if (!in_array($file['type'], $allowedTypes)) {
            error_log("RECHAZO: Tipo de archivo no permitido: " . $file['type']);
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            error_log("RECHAZO: Archivo demasiado grande: " . $file['size'] . " bytes (máx: $maxSize)");
            return false;
        }
        
        $uploadDir = UPLOADS_DIR . '/evidencias/';
        error_log("Directorio de subida: $uploadDir");
        
        if (!is_dir($uploadDir)) {
            error_log("Directorio no existe, intentando crear: $uploadDir");
            if (!@mkdir($uploadDir, 0755, true)) {
                error_log("CRÍTICO: No se pudo crear el directorio: $uploadDir. Verifica permisos del servidor.");
                return false;
            }
            error_log("Directorio creado exitosamente: $uploadDir");
        }
        
        // Verificar que el directorio sea escribible
        if (!is_writable($uploadDir)) {
            error_log("CRÍTICO: El directorio no es escribible: $uploadDir. Permisos actuales: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
            // Intentar cambiar permisos
            @chmod($uploadDir, 0777);
            if (!is_writable($uploadDir)) {
                error_log("CRÍTICO: No se pudieron establecer permisos de escritura en: $uploadDir");
                return false;
            }
            error_log("Permisos corregidos para: $uploadDir");
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'task_' . $taskId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Return only the filename - path construction will be handled in display
            return $filename;
        }
        
        error_log("Error al mover archivo: " . $file['tmp_name'] . " a " . $uploadPath);
        return false;
    }
    
    // Verificar si una tarea está vencida
    private function isTaskExpired($task) {
        // Si no tiene fecha de cierre, nunca vence
        if (empty($task['fecha_cierre'])) {
            return false;
        }
        
        // Si tiene hora de cierre, validar fecha Y hora
        if (!empty($task['hora_cierre'])) {
            // Combinar fecha y hora para comparación exacta
            $fechaHoraCierre = strtotime($task['fecha_cierre'] . ' ' . $task['hora_cierre']);
            $fechaHoraActual = time(); // Timestamp actual con fecha y hora
            
            // La tarea está vencida si la fecha-hora de cierre ya pasó
            return ($fechaHoraCierre < $fechaHoraActual);
        }
        
        // Si solo tiene fecha (sin hora), vence al final del día
        $fechaCierre = strtotime($task['fecha_cierre'] . ' 23:59:59');
        $fechaHoraActual = time();
        
        return ($fechaCierre < $fechaHoraActual);
    }
    
    // Eliminar múltiples tareas (solo SuperAdmin)
    public function deleteMultipleTasks() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('tasks/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('tasks/', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin puede eliminar múltiples tareas
        if ($currentUser['rol'] !== 'SuperAdmin') {
            redirectWithMessage('tasks/', 'No tienes permisos para eliminar tareas', 'error');
        }
        
        $taskIds = $_POST['task_ids'] ?? [];
        
        if (empty($taskIds) || !is_array($taskIds)) {
            redirectWithMessage('tasks/', 'No se seleccionaron tareas para eliminar', 'error');
        }
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($taskIds as $taskId) {
            $taskId = (int)$taskId;
            
            // Verificar que la tarea existe
            $task = $this->activityModel->getActivityById($taskId);
            if (!$task) {
                $errors[] = "Tarea ID $taskId no encontrada";
                continue;
            }
            
            // Eliminar la tarea
            if ($this->activityModel->deleteActivity($taskId)) {
                $deletedCount++;
                logActivity("SuperAdmin eliminó la tarea ID $taskId: " . $task['titulo'], 'INFO', $currentUser['id']);
            } else {
                $errors[] = "Error al eliminar tarea ID $taskId";
            }
        }
        
        if ($deletedCount > 0) {
            $message = "$deletedCount tarea(s) eliminada(s) exitosamente";
            if (!empty($errors)) {
                $message .= ". Errores: " . implode(', ', $errors);
            }
            redirectWithMessage('tasks/', $message, 'success');
        } else {
            redirectWithMessage('tasks/', 'No se pudieron eliminar las tareas: ' . implode(', ', $errors), 'error');
        }
    }
}
?>