<?php
/**
 * Controlador de Actividades
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/activity.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/group.php';

class ActivityController {
    private $auth;
    private $activityModel;
    private $userModel;
    private $groupModel;
    
    public function __construct() {
        $this->auth = getAuth();
        $this->activityModel = new Activity();
        $this->userModel = new User();
        $this->groupModel = new Group();
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
                $filters['exclude_expired'] = true; // Excluir tareas vencidas para activistas
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
        // Título de actividad - disponible para Líder también
        if (!empty($_GET['search_title'])) {
            $filters['search_title'] = cleanInput($_GET['search_title']);
        }
        
        // Advanced search filters for SuperAdmin and Líder
        if (in_array($currentUser['rol'], ['SuperAdmin', 'Líder', 'Gestor'])) {
            if (!empty($_GET['search_name'])) {
                $filters['search_name'] = cleanInput($_GET['search_name']);
            }
            if (!empty($_GET['search_email'])) {
                $filters['search_email'] = cleanInput($_GET['search_email']);
            }
            if (!empty($_GET['search_phone'])) {
                $filters['search_phone'] = cleanInput($_GET['search_phone']);
            }
        }
        
        // Additional filters only for SuperAdmin
        if ($currentUser['rol'] === 'SuperAdmin') {
            // Add leader filter for SuperAdmin - includes leader's activities and their team's activities
            if (!empty($_GET['filter_lider_id'])) {
                $filters['filter_lider_id'] = intval($_GET['filter_lider_id']);
            }
            // Add group filter for SuperAdmin - shows activities from users in the specified group
            if (!empty($_GET['grupo_id'])) {
                $filters['grupo_id'] = intval($_GET['grupo_id']);
            }
        }
        
        // Pagination parameters - OPTIMIZACIÓN: Reducir a 20 por página
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20; // Incrementado de 10 a 20 para mejor UX pero aún manejable
        $filters['page'] = $page;
        $filters['per_page'] = $perPage;
        
        $activities = $this->activityModel->getActivities($filters);
        
        // OPTIMIZACIÓN: Agregar contador de evidencias (solo el número, no el contenido completo)
        foreach ($activities as &$activity) {
            if ($activity['estado'] === 'completada') {
                $activity['evidence_count'] = $this->activityModel->countActivityEvidence($activity['id']);
            } else {
                $activity['evidence_count'] = 0;
            }
        }
        unset($activity); // Liberar referencia
        
        // OPTIMIZACIÓN: Caché del conteo total (pesado con muchos registros)
        $cacheKey = 'activity_count_' . md5(serialize($filters)) . '_' . floor(time() / 300); // 5 min
        $totalActivities = $this->getSimpleCache($cacheKey);
        if ($totalActivities === null) {
            $totalActivities = $this->activityModel->countActivities($filters);
            $this->setSimpleCache($cacheKey, $totalActivities);
        }
        
        $totalPages = ceil($totalActivities / $perPage);
        
        // OPTIMIZACIÓN: Caché de tipos de actividad (casi nunca cambian)
        $cacheKey = 'activity_types_' . floor(time() / 1800); // 30 min
        $activityTypes = $this->getSimpleCache($cacheKey);
        if ($activityTypes === null) {
            $activityTypes = $this->activityModel->getActivityTypes();
            $this->setSimpleCache($cacheKey, $activityTypes);
        }
        
        // Get list of leaders and groups for SuperAdmin filter
        $leaders = [];
        $groups = [];
        if ($currentUser['rol'] === 'SuperAdmin') {
            // OPTIMIZACIÓN: Caché de líderes y grupos
            $cacheKey = 'leaders_list_' . floor(time() / 1800); // 30 min
            $leaders = $this->getSimpleCache($cacheKey);
            if ($leaders === null) {
                $leaders = $this->userModel->getActiveLiders();
                $this->setSimpleCache($cacheKey, $leaders);
            }
            
            $cacheKey = 'groups_list_' . floor(time() / 1800); // 30 min
            $groups = $this->getSimpleCache($cacheKey);
            if ($groups === null) {
                $groups = $this->groupModel->getAllGroups();
                $this->setSimpleCache($cacheKey, $groups);
            }
        }
        
        // OPTIMIZACIÓN: NO cargar evidencias aquí (muy pesado)
        // Las evidencias se cargan solo cuando se abre el detalle de una actividad
        // Esto reduce significativamente el tiempo de carga
        
        // Calculate real completion percentage for current month (not affected by pagination)
        $currentMonthFilters = array_merge($filters, [
            'fecha_desde' => date('Y-m-01'),
            'fecha_hasta' => date('Y-m-t') // Last day of current month
        ]);
        unset($currentMonthFilters['page']);
        unset($currentMonthFilters['per_page']);
        
        // OPTIMIZACIÓN: Usar consulta directa de estadísticas en lugar de cargar todas las actividades
        $monthlyStats = $this->activityModel->getActivityStats($currentMonthFilters);
        $totalMonthlyActivities = $monthlyStats['total_actividades'] ?? 0;
        $completedMonthlyActivities = $monthlyStats['completadas'] ?? 0;
        $realCompletionPercentage = $totalMonthlyActivities > 0 ? round(($completedMonthlyActivities / $totalMonthlyActivities) * 100, 1) : 0;
        
        include __DIR__ . '/../views/activities/list.php';
    }
    
    // Mostrar formulario de nueva actividad
    public function showCreateForm() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Prevent leaders and activists from accessing activity creation form
        if (in_array($currentUser['rol'], ['Líder', 'Activista'])) {
            if ($currentUser['rol'] === 'Líder') {
                redirectWithMessage('activities/', 'Los líderes no pueden crear actividades directamente', 'error');
            } else {
                redirectWithMessage('activities/', 'Los activistas no pueden crear actividades directamente. Usa "Proponer Actividad" en su lugar.', 'error');
            }
        }
        
        $activityTypes = $this->activityModel->getActivityTypes();
        
        // Load groups for SuperAdmin
        $groups = [];
        $teamMembersData = [];
        $groupMembersData = [];
        if ($currentUser['rol'] === 'SuperAdmin') {
            require_once __DIR__ . '/../models/group.php';
            $groupModel = new Group();
            $groups = $groupModel->getActiveGroups();
            
            // Get team members for each leader to support auto-selection
            $leaders = $this->userModel->getActiveLiders();
            foreach ($leaders as $leader) {
                $teamMembers = $this->userModel->getActivistsOfLeader($leader['id']);
                $teamMembersData[$leader['id']] = array_column($teamMembers, 'id');
            }
            
            // Get group members for each group to support auto-selection
            foreach ($groups as $group) {
                $groupMembers = $groupModel->getGroupMembers($group['id']);
                $groupMembersData[$group['id']] = array_column($groupMembers, 'id');
            }
        }
        
        include __DIR__ . '/../views/activities/create.php';
    }
    
    // Crear nueva actividad
    public function createActivity() {
        $this->auth->requireAuth();
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Prevent leaders and activists from creating activities
        if (in_array($currentUser['rol'], ['Líder', 'Activista'])) {
            if ($currentUser['rol'] === 'Líder') {
                redirectWithMessage('activities/', 'Los líderes no pueden crear actividades directamente', 'error');
            } else {
                redirectWithMessage('activities/', 'Los activistas no pueden crear actividades directamente. Usa "Proponer Actividad" en su lugar.', 'error');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/create.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/create.php', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Handle recipient selection
        $recipients = [];
        $shouldCreateForRecipients = false;
        
        if ($currentUser['rol'] === 'SuperAdmin') {
            if (!empty($_POST['destinatarios_lideres'])) {
                // SuperAdmin selected leaders as recipients
                // REQUIREMENT IMPLEMENTATION: Include both leaders AND all activists under those leaders
                // This addresses the requirement: "Al generar una actividad desde el admin, debe enviarse 
                // tanto a los líderes como a todos los activistas de cada líder (no solo a líderes)."
                
                $selectedLeaders = array_map('intval', $_POST['destinatarios_lideres']);
                $recipients = $selectedLeaders; // Start with the leaders themselves
                
                // Add all activists under the selected leaders
                // For each selected leader, get their activists and add them to recipients
                foreach ($selectedLeaders as $liderId) {
                    $activists = $this->userModel->getActivistsOfLeader($liderId);
                    foreach ($activists as $activist) {
                        $recipients[] = intval($activist['id']);
                    }
                }
                
                // CRITICAL FIX: Remove duplicates that might come from multiple sources
                // This prevents activities from being created twice for the same person
                $recipients = array_values(array_unique($recipients));
                $shouldCreateForRecipients = true;
            } elseif (!empty($_POST['destinatarios_grupos'])) {
                // SuperAdmin selected groups as recipients
                // Get all members from the selected groups
                $selectedGroups = array_map('intval', $_POST['destinatarios_grupos']);
                $recipients = [];
                
                foreach ($selectedGroups as $groupId) {
                    $groupMembers = $this->groupModel->getGroupMembers($groupId);
                    foreach ($groupMembers as $member) {
                        $recipients[] = intval($member['id']);
                    }
                }
                
                // CRITICAL FIX: Remove duplicates that might come from users in multiple groups
                // This prevents activities from being created twice for the same person
                $recipients = array_values(array_unique($recipients));
                $shouldCreateForRecipients = true;
            } elseif (!empty($_POST['destinatarios_todos'])) {
                // SuperAdmin selected all users as recipients
                $recipients = array_values(array_unique(array_map('intval', $_POST['destinatarios_todos'])));
                $shouldCreateForRecipients = true;
            }
        } elseif ($currentUser['rol'] === 'Líder' && !empty($_POST['destinatarios_activistas'])) {
            // Leader selected activists as recipients
            $recipients = array_values(array_unique(array_map('intval', $_POST['destinatarios_activistas'])));
            $shouldCreateForRecipients = true;
        }
        
        if ($shouldCreateForRecipients && !empty($recipients)) {
            // Process files once before creating activities for recipients
            // REQUIREMENT FIX: Process evidence files once and attach to all recipients
            $processedFiles = $this->processEvidenceFilesOnce();
            
            // Create activity for each recipient
            $successCount = 0;
            foreach ($recipients as $recipientId) {
                $fechaPublicacion = cleanInput($_POST['fecha_publicacion'] ?? '');
                $fechaActividad = !empty($fechaPublicacion) ? $fechaPublicacion : date('Y-m-d');
                
                $activityData = [
                    'usuario_id' => $recipientId,
                    'user_role' => $currentUser['rol'], // Add user role for pending task logic
                    'created_by_id' => $currentUser['id'], // Track who created the activity for authorization
                    'tipo_actividad_id' => intval($_POST['tipo_actividad_id'] ?? 0),
                    'titulo' => cleanInput($_POST['titulo'] ?? ''),
                    'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
                    'enlace_1' => cleanInput($_POST['enlace_1'] ?? ''),
                    'enlace_2' => cleanInput($_POST['enlace_2'] ?? ''),
                    'enlace_3' => cleanInput($_POST['enlace_3'] ?? ''),
                    'enlace_4' => cleanInput($_POST['enlace_4'] ?? ''),
                    'fecha_actividad' => $fechaActividad,
                    'fecha_publicacion' => $fechaPublicacion,
                    'hora_publicacion' => cleanInput($_POST['hora_publicacion'] ?? ''),
                    'fecha_cierre' => cleanInput($_POST['fecha_cierre'] ?? ''),
                    'hora_cierre' => cleanInput($_POST['hora_cierre'] ?? ''),
                    'grupo' => cleanInput($_POST['grupo'] ?? ''),
                    'solicitante_id' => $currentUser['id'] // Track who created the task
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
                    // Attach processed files to this activity
                    $this->attachProcessedFilesToActivity($activityId, $processedFiles);
                    
                    // Send notification to recipient
                    $this->activityModel->notifyNewActivity($activityId, $recipientId, $activityData['titulo']);
                    
                    $successCount++;
                }
            }
            
            if ($successCount > 0) {
                redirectWithMessage('activities/', "Actividad creada exitosamente para $successCount destinatario(s)", 'success');
            } else {
                redirectWithMessage('activities/create.php', 'Error al crear actividad', 'error');
            }
        } else {
            // Create activity for current user (original behavior)
            $fechaPublicacion = cleanInput($_POST['fecha_publicacion'] ?? '');
            $fechaActividad = !empty($fechaPublicacion) ? $fechaPublicacion : date('Y-m-d');
            
            $activityData = [
                'usuario_id' => $currentUser['id'],
                'user_role' => $currentUser['rol'], // Add user role for pending task logic
                'created_by_id' => $currentUser['id'], // Track who created the activity for authorization
                'tipo_actividad_id' => intval($_POST['tipo_actividad_id'] ?? 0),
                'titulo' => cleanInput($_POST['titulo'] ?? ''),
                'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
                'enlace_1' => cleanInput($_POST['enlace_1'] ?? ''),
                'enlace_2' => cleanInput($_POST['enlace_2'] ?? ''),
                'enlace_3' => cleanInput($_POST['enlace_3'] ?? ''),
                'enlace_4' => cleanInput($_POST['enlace_4'] ?? ''),
                'fecha_actividad' => $fechaActividad,
                'fecha_publicacion' => $fechaPublicacion,
                'hora_publicacion' => cleanInput($_POST['hora_publicacion'] ?? ''),
                'fecha_cierre' => cleanInput($_POST['fecha_cierre'] ?? ''),
                'hora_cierre' => cleanInput($_POST['hora_cierre'] ?? ''),
                'grupo' => cleanInput($_POST['grupo'] ?? '')
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
        
        // Load groups for SuperAdmin
        $groups = [];
        if ($currentUser['rol'] === 'SuperAdmin') {
            require_once __DIR__ . '/../models/group.php';
            $groupModel = new Group();
            $groups = $groupModel->getActiveGroups();
        }
        
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
        
        $estadoNuevo = cleanInput($_POST['estado'] ?? '');
        
        // Validar que no se pueda marcar como completada si está vencida
        if ($estadoNuevo === 'completada' && $activity['estado'] !== 'completada') {
            if ($this->isActivityExpired($activity)) {
                redirectWithMessage("activities/edit.php?id=$activityId", 
                    'No se puede marcar como completada una tarea vencida. Solo se puede cancelar.', 'error');
            }
        }
        
        $fechaPublicacion = cleanInput($_POST['fecha_publicacion'] ?? '');
        $fechaActividad = !empty($fechaPublicacion) ? $fechaPublicacion : $activity['fecha_actividad'];
        
        $updateData = [
            'titulo' => cleanInput($_POST['titulo'] ?? ''),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'fecha_actividad' => $fechaActividad,
            'fecha_publicacion' => $fechaPublicacion,
            'hora_publicacion' => cleanInput($_POST['hora_publicacion'] ?? ''),
            'fecha_cierre' => cleanInput($_POST['fecha_cierre'] ?? ''),
            'hora_cierre' => cleanInput($_POST['hora_cierre'] ?? ''),
            'estado' => $estadoNuevo,
            'grupo' => cleanInput($_POST['grupo'] ?? ''),
            'enlace_1' => cleanInput($_POST['enlace_1'] ?? ''),
            'enlace_2' => cleanInput($_POST['enlace_2'] ?? ''),
            'enlace_3' => cleanInput($_POST['enlace_3'] ?? ''),
            'enlace_4' => cleanInput($_POST['enlace_4'] ?? '')
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
    
    // Eliminar actividad (soporta eliminación individual y múltiple)
    public function deleteActivity() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Detectar si es eliminación múltiple o individual
        if (isset($_POST['activity_ids']) && is_array($_POST['activity_ids'])) {
            // ELIMINACIÓN MÚLTIPLE (solo SuperAdmin)
            if ($currentUser['rol'] !== 'SuperAdmin') {
                redirectWithMessage('activities/', 'No tienes permisos para eliminar múltiples actividades', 'error');
            }
            
            $activityIds = $_POST['activity_ids'];
            
            if (empty($activityIds)) {
                redirectWithMessage('activities/', 'No se seleccionaron actividades para eliminar', 'error');
            }
            
            $deletedCount = 0;
            $errors = [];
            
            foreach ($activityIds as $activityId) {
                $activityId = (int)$activityId;
                
                $activity = $this->activityModel->getActivityById($activityId);
                if (!$activity) {
                    $errors[] = "Actividad ID $activityId no encontrada";
                    continue;
                }
                
                if ($this->activityModel->deleteActivity($activityId)) {
                    $deletedCount++;
                    logActivity("SuperAdmin eliminó la actividad ID $activityId: " . $activity['titulo'], 'INFO', $currentUser['id']);
                } else {
                    $errors[] = "Error al eliminar actividad ID $activityId";
                }
            }
            
            if ($deletedCount > 0) {
                $message = "$deletedCount actividad(es) eliminada(s) exitosamente";
                if (!empty($errors)) {
                    $message .= ". Errores: " . implode(', ', $errors);
                }
                redirectWithMessage('activities/', $message, 'success');
            } else {
                redirectWithMessage('activities/', 'No se pudieron eliminar las actividades: ' . implode(', ', $errors), 'error');
            }
            
        } else {
            // ELIMINACIÓN INDIVIDUAL
            $activityId = intval($_POST['activity_id'] ?? 0);
            if ($activityId <= 0) {
                redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
            }
            
            $activity = $this->activityModel->getActivityById($activityId);
            if (!$activity) {
                redirectWithMessage('activities/', 'Actividad no encontrada', 'error');
            }
            
            // Verificar permisos - solo el dueño o SuperAdmin/Gestor pueden eliminar
            $canDelete = false;
            
            if (in_array($currentUser['rol'], ['SuperAdmin', 'Gestor'])) {
                $canDelete = true;
            } elseif ($activity['usuario_id'] == $currentUser['id']) {
                $canDelete = true;
            }
            
            if (!$canDelete) {
                redirectWithMessage('activities/', 'No tienes permisos para eliminar esta actividad', 'error');
            }
            
            // Eliminar la actividad
            $result = $this->activityModel->deleteActivity($activityId);
            
            if ($result) {
                logActivity("Actividad eliminada: ID $activityId - " . $activity['titulo'], 'INFO');
                redirectWithMessage('activities/', 'Actividad eliminada exitosamente', 'success');
            } else {
                redirectWithMessage('activities/', 'Error al eliminar la actividad', 'error');
            }
        }
    }
    
    // Eliminar múltiples actividades (solo SuperAdmin)
    public function deleteMultipleActivities() {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Solo SuperAdmin puede eliminar múltiples actividades
        if ($currentUser['rol'] !== 'SuperAdmin') {
            redirectWithMessage('activities/', 'No tienes permisos para eliminar múltiples actividades', 'error');
        }
        
        $activityIds = $_POST['activity_ids'] ?? [];
        
        if (empty($activityIds) || !is_array($activityIds)) {
            redirectWithMessage('activities/', 'No se seleccionaron actividades para eliminar', 'error');
        }
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($activityIds as $activityId) {
            $activityId = (int)$activityId;
            
            // Verificar que la actividad existe
            $activity = $this->activityModel->getActivityById($activityId);
            if (!$activity) {
                $errors[] = "Actividad ID $activityId no encontrada";
                continue;
            }
            
            // Eliminar la actividad
            if ($this->activityModel->deleteActivity($activityId)) {
                $deletedCount++;
                logActivity("SuperAdmin eliminó la actividad ID $activityId: " . $activity['titulo'], 'INFO', $currentUser['id']);
            } else {
                $errors[] = "Error al eliminar actividad ID $activityId";
            }
        }
        
        if ($deletedCount > 0) {
            $message = "$deletedCount actividad(es) eliminada(s) exitosamente";
            if (!empty($errors)) {
                $message .= ". Errores: " . implode(', ', $errors);
            }
            redirectWithMessage('activities/', $message, 'success');
        } else {
            redirectWithMessage('activities/', 'No se pudieron eliminar las actividades: ' . implode(', ', $errors), 'error');
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
        
        // Verificar si la actividad/tarea está vencida
        if ($this->isActivityExpired($activity)) {
            redirectWithMessage("activities/detail.php?id=$activityId", 'Esta actividad/tarea ya está vencida y no se puede agregar evidencia', 'error');
        }
        
        $fileName = null;
        
        // Procesar archivo si se subió
        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mp3', 'wav', 'pdf', 'doc', 'docx'];
            $uploadResult = uploadFile($_FILES['evidence_file'], __DIR__ . '/../public/assets/uploads/evidencias', $allowedTypes);
            
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
                    
                    // Check if it's a video file for size limits
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $isVideo = in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
                    
                    $uploadResult = uploadFile($file, __DIR__ . '/../public/assets/uploads/evidencias', $allowedTypes, false, $isVideo);
                    
                    if ($uploadResult['success']) {
                        // Determinar tipo de evidencia basado en la extensión
                        $evidenceType = 'foto'; // Por defecto
                        
                        if (in_array($extension, ['mp4', 'avi'])) {
                            $evidenceType = 'video';
                        } elseif (in_array($extension, ['mp3', 'wav'])) {
                            $evidenceType = 'audio';
                        }
                        
                        // Add initial attachment evidence (not blocked yet)
                        // REQUIREMENT IMPLEMENTATION: Store initial attachments with bloqueada=0
                        // This allows them to be displayed in pending tasks but doesn't mark activity as completed
                        $this->activityModel->addEvidence($activityId, $evidenceType, $uploadResult['filename'], null, 0);
                    }
                }
            }
        }
    }
    
    // Process evidence files once and return processed file information
    // REQUIREMENT FIX: Process files once to avoid losing them after first recipient
    private function processEvidenceFilesOnce() {
        $processedFiles = [];
        
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
                    
                    // Check if it's a video file for size limits
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $isVideo = in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
                    
                    $uploadResult = uploadFile($file, __DIR__ . '/../public/assets/uploads/evidencias', $allowedTypes, false, $isVideo);
                    
                    if ($uploadResult['success']) {
                        // Determinar tipo de evidencia basado en la extensión
                        $evidenceType = 'foto'; // Por defecto
                        
                        if (in_array($extension, ['mp4', 'avi'])) {
                            $evidenceType = 'video';
                        } elseif (in_array($extension, ['mp3', 'wav'])) {
                            $evidenceType = 'audio';
                        }
                        
                        $processedFiles[] = [
                            'type' => $evidenceType,
                            'filename' => $uploadResult['filename']
                        ];
                    }
                }
            }
        }
        
        return $processedFiles;
    }
    
    // Attach processed files to a specific activity
    // REQUIREMENT FIX: Attach same files to all recipient activities
    private function attachProcessedFilesToActivity($activityId, $processedFiles) {
        foreach ($processedFiles as $fileInfo) {
            // Add initial attachment evidence (not blocked yet)
            // REQUIREMENT IMPLEMENTATION: Store initial attachments with bloqueada=0
            // This allows them to be displayed in pending tasks but doesn't mark activity as completed
            $this->activityModel->addEvidence($activityId, $fileInfo['type'], $fileInfo['filename'], null, 0);
        }
    }
    
    // Mostrar formulario de propuesta de actividad (solo para activistas)
    public function showProposalForm() {
        $this->auth->requireRole(['Activista']);
        
        $activityTypes = $this->activityModel->getActivityTypes();
        
        include __DIR__ . '/../views/activities/propose.php';
    }
    
    // Crear propuesta de actividad
    public function createProposal() {
        $this->auth->requireRole(['Activista']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/propose.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/propose.php', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        $proposalData = [
            'usuario_id' => $currentUser['id'],
            'tipo_actividad_id' => intval($_POST['tipo_actividad_id'] ?? 0),
            'titulo' => cleanInput($_POST['titulo'] ?? ''),
            'descripcion' => cleanInput($_POST['descripcion'] ?? ''),
            'fecha_actividad' => cleanInput($_POST['fecha_actividad'] ?? ''),
            'grupo' => cleanInput($_POST['grupo'] ?? '')
        ];
        
        // Validar datos
        $errors = $this->validateActivityData($proposalData, 'Activista');
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirectWithMessage('activities/propose.php', 'Por favor corrige los errores', 'error');
        }
        
        $proposalId = $this->activityModel->createProposal($proposalData);
        
        if ($proposalId) {
            redirectWithMessage('activities/', 'Propuesta enviada exitosamente. Será revisada por los administradores.', 'success');
        } else {
            redirectWithMessage('activities/propose.php', 'Error al enviar propuesta', 'error');
        }
    }
    
    // Lista de propuestas pendientes (para SuperAdmin, Gestor, Líder)
    public function listProposals() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor', 'Líder']);
        
        $currentUser = $this->auth->getCurrentUser();
        $filters = [];
        
        // Líder solo ve propuestas de sus activistas
        if ($currentUser['rol'] === 'Líder') {
            $filters['lider_id'] = $currentUser['id'];
        }
        
        $proposals = $this->activityModel->getPendingProposals($filters);
        
        include __DIR__ . '/../views/activities/proposals.php';
    }
    
    // Aprobar o rechazar propuesta
    public function processProposal() {
        $this->auth->requireRole(['SuperAdmin', 'Gestor', 'Líder']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithMessage('activities/proposals.php', 'Método no permitido', 'error');
        }
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWithMessage('activities/proposals.php', 'Token de seguridad inválido', 'error');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        $proposalId = intval($_POST['proposal_id'] ?? 0);
        $action = cleanInput($_POST['action'] ?? '');
        
        if ($proposalId <= 0 || !in_array($action, ['approve', 'reject'])) {
            redirectWithMessage('activities/proposals.php', 'Datos inválidos', 'error');
        }
        
        $approved = ($action === 'approve');
        $result = $this->activityModel->approveProposal($proposalId, $approved, $currentUser['id']);
        
        if ($result) {
            $message = $approved ? 'Propuesta aprobada exitosamente' : 'Propuesta rechazada';
            redirectWithMessage('activities/proposals.php', $message, 'success');
        } else {
            redirectWithMessage('activities/proposals.php', 'Error al procesar propuesta', 'error');
        }
    }
    
    // Verificar si una actividad/tarea está vencida
    private function isActivityExpired($activity) {
        // Si no tiene fecha de cierre, nunca vence
        if (empty($activity['fecha_cierre'])) {
            return false;
        }
        
        $fechaCierre = strtotime($activity['fecha_cierre']);
        $fechaActual = strtotime(date('Y-m-d'));
        
        // Si la fecha de cierre ya pasó
        if ($fechaCierre < $fechaActual) {
            return true;
        }
        
        // Si es el mismo día, verificar la hora de cierre
        if ($fechaCierre == $fechaActual && !empty($activity['hora_cierre'])) {
            $horaCierre = strtotime($activity['hora_cierre']);
            $horaActual = strtotime(date('H:i:s'));
            
            if ($horaCierre < $horaActual) {
                return true;
            }
        }
        
        return false;
    }
    
    // ============================================
    // MÉTODOS DE CACHÉ (OPTIMIZACIÓN)
    // ============================================
    
    /**
     * Obtener datos del caché simple
     */
    private function getSimpleCache($key) {
        $cacheDir = $this->getCacheDir();
        $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            $data = @file_get_contents($cacheFile);
            if ($data !== false) {
                return unserialize($data);
            }
        }
        
        return null;
    }
    
    /**
     * Guardar datos en caché simple
     */
    private function setSimpleCache($key, $data) {
        $cacheDir = $this->getCacheDir();
        $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
        @file_put_contents($cacheFile, serialize($data));
    }
    
    /**
     * Obtener directorio de caché con fallback
     */
    private function getCacheDir() {
        // Opción 1: cache/activities
        $cacheDir = __DIR__ . '/../cache/activities';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            return $cacheDir;
        }
        
        // Opción 2: Directorio temporal
        $tmpDir = sys_get_temp_dir() . '/activistas_cache';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            return $tmpDir;
        }
        
        // Opción 3: /tmp directamente
        return sys_get_temp_dir();
    }
}
?>