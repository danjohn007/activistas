<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Actividad - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('create_activity'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nueva Actividad</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver a la lista
                            </a>
                        </div>
                    </div>
                </div>

                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['form_errors'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Errores de validación:</strong>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['form_errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['form_errors']); ?>
                <?php endif; ?>

                <!-- Formulario de nueva actividad -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos de la Actividad</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['titulo'] ?? '') ?>" 
                                               required maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tipo_actividad_id" class="form-label">Tipo de Actividad *</label>
                                        <select class="form-select" id="tipo_actividad_id" name="tipo_actividad_id" required>
                                            <option value="">Seleccionar tipo</option>
                                            <?php foreach ($activityTypes as $type): ?>
                                                <option value="<?= $type['id'] ?>" 
                                                        <?= ($_SESSION['form_data']['tipo_actividad_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                          maxlength="1000"><?= htmlspecialchars($_SESSION['form_data']['descripcion'] ?? '') ?></textarea>
                                <div class="form-text">Describe brevemente los objetivos y detalles de la actividad.</div>
                            </div>

                            <!-- Enlaces opcionales de actividad -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="enlace_1" class="form-label">
                                            <i class="fas fa-link me-1"></i>Enlace 1 (Opcional)
                                        </label>
                                        <input type="url" class="form-control" id="enlace_1" name="enlace_1" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['enlace_1'] ?? '') ?>"
                                               placeholder="https://ejemplo.com/enlace-relacionado">
                                        <div class="form-text">Enlace opcional relacionado con la actividad</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="enlace_2" class="form-label">
                                            <i class="fas fa-link me-1"></i>Enlace 2 (Opcional)
                                        </label>
                                        <input type="url" class="form-control" id="enlace_2" name="enlace_2" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['enlace_2'] ?? '') ?>"
                                               placeholder="https://ejemplo.com/otro-enlace-relacionado">
                                        <div class="form-text">Segundo enlace opcional relacionado con la actividad</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fecha_actividad" class="form-label">Fecha de la Actividad *</label>
                                        <input type="date" class="form-control" id="fecha_actividad" name="fecha_actividad" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['fecha_actividad'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor'])): ?>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="fecha_cierre" class="form-label">Fecha de Cierre</label>
                                        <input type="date" class="form-control" id="fecha_cierre" name="fecha_cierre" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['fecha_cierre'] ?? '') ?>">
                                        <div class="form-text">Después de esta fecha la actividad no aparecerá en tareas pendientes</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="hora_cierre" class="form-label">Hora de Cierre</label>
                                        <input type="time" class="form-control" id="hora_cierre" name="hora_cierre" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['hora_cierre'] ?? '') ?>">
                                        <div class="form-text">Hora límite para completar la actividad</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Selección de destinatarios -->
                            <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
                            <div class="mb-3">
                                <label class="form-label">Asignar a</label>
                                
                                <!-- Tab navigation -->
                                <ul class="nav nav-tabs" id="assignmentTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="leaders-tab" data-bs-toggle="tab" data-bs-target="#leaders" type="button" role="tab">
                                            Líderes
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab">
                                            Todos los Usuarios
                                        </button>
                                    </li>
                                </ul>
                                
                                <!-- Tab content -->
                                <div class="tab-content border border-top-0 rounded-bottom p-3" id="assignmentTabContent">
                                    <!-- Leaders tab -->
                                    <div class="tab-pane fade show active" id="leaders" role="tabpanel">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="select_all_leaders">
                                            <label class="form-check-label fw-bold" for="select_all_leaders">
                                                Seleccionar/Deseleccionar todos los líderes
                                            </label>
                                        </div>
                                        <hr>
                                        <?php 
                                        require_once __DIR__ . '/../../models/user.php';
                                        $userModel = new User();
                                        $lideres = $userModel->getActiveLiders();
                                        if (!empty($lideres)): ?>
                                            <?php foreach ($lideres as $lider): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input leader-checkbox" type="checkbox" 
                                                           id="lider_<?= $lider['id'] ?>" name="destinatarios_lideres[]" 
                                                           value="<?= $lider['id'] ?>" checked>
                                                    <label class="form-check-label" for="lider_<?= $lider['id'] ?>">
                                                        <?= htmlspecialchars($lider['nombre_completo']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-muted">No hay líderes disponibles.</div>
                                        <?php endif; ?>
                                        <div class="form-text mt-2">La actividad aparecerá como tarea pendiente para los líderes seleccionados y sus activistas.</div>
                                    </div>
                                    
                                    <!-- All users tab -->
                                    <div class="tab-pane fade" id="all-users" role="tabpanel">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="select_all_users" checked>
                                            <label class="form-check-label fw-bold" for="select_all_users">
                                                Seleccionar/Deseleccionar todos los usuarios
                                            </label>
                                        </div>
                                        <hr>
                                        <?php 
                                        $allUsers = $userModel->getAllActiveUsers();
                                        if (!empty($allUsers)): ?>
                                            <?php foreach ($allUsers as $user): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input all-user-checkbox" type="checkbox" 
                                                           id="user_<?= $user['id'] ?>" name="destinatarios_todos[]" 
                                                           value="<?= $user['id'] ?>" checked>
                                                    <label class="form-check-label" for="user_<?= $user['id'] ?>">
                                                        <?= htmlspecialchars($user['nombre_completo']) ?> 
                                                        <span class="badge bg-secondary ms-1"><?= $user['rol'] ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-muted">No hay usuarios disponibles.</div>
                                        <?php endif; ?>
                                        <div class="form-text mt-2">La actividad aparecerá como tarea pendiente para todos los usuarios seleccionados.</div>
                                    </div>
                                </div>
                            </div>
                            <?php elseif ($_SESSION['user_role'] === 'Líder'): ?>
                            <div class="mb-3">
                                <label class="form-label">Asignar a Activistas</label>
                                <div class="border rounded p-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="select_all_activists">
                                        <label class="form-check-label fw-bold" for="select_all_activists">
                                            Seleccionar/Deseleccionar todos
                                        </label>
                                    </div>
                                    <hr>
                                    <?php 
                                    $userModel = new User();
                                    $activistas = $userModel->getActivistsOfLeader($_SESSION['user_id']);
                                    if (!empty($activistas)): ?>
                                        <?php foreach ($activistas as $activista): ?>
                                            <div class="form-check">
                                                <input class="form-check-input activist-checkbox" type="checkbox" 
                                                       id="activista_<?= $activista['id'] ?>" name="destinatarios_activistas[]" 
                                                       value="<?= $activista['id'] ?>" checked>
                                                <label class="form-check-label" for="activista_<?= $activista['id'] ?>">
                                                    <?= htmlspecialchars($activista['nombre_completo']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted">No tienes activistas asignados.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Si selecciona activistas, la actividad aparecerá como tarea pendiente para ellos.</div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="evidence_files" class="form-label">Evidencias (opcional)</label>
                                <input type="file" class="form-control" id="evidence_files" name="evidence_files[]" 
                                       accept="image/*,video/*,audio/*" multiple>
                                <div class="form-text">Puedes subir fotos, videos o audios relacionados con la actividad.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= url('activities/') ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Crear Actividad
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-llenar descripción cuando se selecciona un tipo de actividad
        document.getElementById('tipo_actividad_id').addEventListener('change', function() {
            const typeId = this.value;
            const descripcionField = document.getElementById('descripcion');
            
            if (typeId) {
                // Hacer petición AJAX para obtener la descripción del tipo
                fetch('<?= url('activity-types/api.php') ?>?id=' + typeId, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.descripcion) {
                        // Solo llenar si el campo está vacío o preguntarle al usuario
                        if (!descripcionField.value.trim() || 
                            confirm('¿Desea cargar la descripción del tipo de actividad seleccionado? Esto reemplazará el contenido actual.')) {
                            descripcionField.value = data.descripcion;
                        }
                    }
                })
                .catch(error => {
                    console.log('Error al cargar descripción del tipo:', error);
                });
            }
        });

        // Funcionalidad para seleccionar/deseleccionar todos los activistas
        const selectAllCheckbox = document.getElementById('select_all_activists');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const activistCheckboxes = document.querySelectorAll('.activist-checkbox');
                activistCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Actualizar el estado del checkbox "seleccionar todos" cuando se cambian los individuales
            const activistCheckboxes = document.querySelectorAll('.activist-checkbox');
            activistCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(activistCheckboxes).every(cb => cb.checked);
                    const anyChecked = Array.from(activistCheckboxes).some(cb => cb.checked);
                    
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = anyChecked && !allChecked;
                });
            });
        }

        // Funcionalidad para seleccionar/deseleccionar todos los líderes (SuperAdmin)
        const selectAllLeadersCheckbox = document.getElementById('select_all_leaders');
        if (selectAllLeadersCheckbox) {
            selectAllLeadersCheckbox.addEventListener('change', function() {
                const leaderCheckboxes = document.querySelectorAll('.leader-checkbox');
                leaderCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Actualizar el estado del checkbox "seleccionar todos" cuando se cambian los individuales
            const leaderCheckboxes = document.querySelectorAll('.leader-checkbox');
            leaderCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(leaderCheckboxes).every(cb => cb.checked);
                    const anyChecked = Array.from(leaderCheckboxes).some(cb => cb.checked);
                    
                    selectAllLeadersCheckbox.checked = allChecked;
                    selectAllLeadersCheckbox.indeterminate = anyChecked && !allChecked;
                });
            });
        }
        
        // Funcionalidad para seleccionar/deseleccionar todos los usuarios (SuperAdmin)
        const selectAllUsersCheckbox = document.getElementById('select_all_users');
        if (selectAllUsersCheckbox) {
            // Pre-seleccionar todos los usuarios por defecto para actividades de admin
            // Implementación del requerimiento: "preseleccionar todos los usuarios en 'Nueva Actividad'"
            const userCheckboxes = document.querySelectorAll('.all-user-checkbox');
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = true; // Pre-seleccionar todos por defecto
            });
            selectAllUsersCheckbox.checked = true; // Marcar el "seleccionar todos" también
            
            selectAllUsersCheckbox.addEventListener('change', function() {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Actualizar el estado del checkbox "seleccionar todos" cuando se cambian los individuales
            userCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
                    const anyChecked = Array.from(userCheckboxes).some(cb => cb.checked);
                    
                    selectAllUsersCheckbox.checked = allChecked;
                    selectAllUsersCheckbox.indeterminate = anyChecked && !allChecked;
                });
            });
        }
        
        // Limpiar datos del formulario después de mostrar
        <?php unset($_SESSION['form_data']); ?>
    </script>
</body>
</html>