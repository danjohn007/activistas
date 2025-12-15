<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Actividades - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-activity {
            transition: transform 0.2s;
        }
        .card-activity:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('activities'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Actividades</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Nueva Actividad
                            </a>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-success" onclick="exportCurrentMonth()" title="Exportar reporte del mes actual">
                                <i class="fas fa-file-excel me-1"></i>Exportar Mes Actual
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <!-- Primera fila: Filtros básicos -->
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo de Actividad</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach ($activityTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>" <?= ($_GET['tipo'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="programada" <?= ($_GET['estado'] ?? '') === 'programada' ? 'selected' : '' ?>>Programada</option>
                                    <option value="en_progreso" <?= ($_GET['estado'] ?? '') === 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                    <option value="completada" <?= ($_GET['estado'] ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                                    <option value="cancelada" <?= ($_GET['estado'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                            </div>
                            
                            <?php 
                            // Check if current user is Líder or SuperAdmin
                            $auth = getAuth();
                            $currentUser = $auth->getCurrentUser();
                            $isLiderOrAdmin = isset($currentUser) && in_array($currentUser['rol'], ['Líder', 'SuperAdmin', 'Gestor']);
                            ?>
                            
                            <?php if ($isLiderOrAdmin): ?>
                            <!-- Segunda fila: Búsquedas avanzadas para Líder/Admin -->
                            <div class="col-md-3">
                                <label for="search_title" class="form-label">Título de Actividad</label>
                                <input type="text" class="form-control" id="search_title" name="search_title" 
                                       placeholder="Buscar por título..." 
                                       value="<?= htmlspecialchars($_GET['search_title'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_name" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="search_name" name="search_name" 
                                       placeholder="Buscar por nombre..." 
                                       value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="search_email" name="search_email" 
                                       placeholder="Buscar por correo..." 
                                       value="<?= htmlspecialchars($_GET['search_email'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="search_phone" name="search_phone" 
                                       placeholder="Buscar por teléfono..." 
                                       value="<?= htmlspecialchars($_GET['search_phone'] ?? '') ?>">
                            </div>
                            <?php endif; ?>
                            
                            <!-- Botones -->
                            <div class="col-12 d-flex justify-content-start gap-2 mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                                <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Limpiar
                                </a>
                            </div>
                            
                            <?php 
                            // Check if current user is SuperAdmin for advanced search
                            $auth = getAuth();
                            $currentUser = $auth->getCurrentUser();
                            ?>
                            <?php if (isset($currentUser) && $currentUser['rol'] === 'SuperAdmin'): ?>
                            <!-- Advanced Search for SuperAdmin -->
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-search-plus me-1"></i>Búsqueda Avanzada (SuperAdmin)
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <label for="search_title" class="form-label">Título de Actividad</label>
                                <input type="text" class="form-control" id="search_title" name="search_title" 
                                       placeholder="Buscar por título..." 
                                       value="<?= htmlspecialchars($_GET['search_title'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_name" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="search_name" name="search_name" 
                                       placeholder="Buscar por nombre..." 
                                       value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="search_email" name="search_email" 
                                       placeholder="Buscar por correo..." 
                                       value="<?= htmlspecialchars($_GET['search_email'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="search_phone" name="search_phone" 
                                       placeholder="Buscar por teléfono..." 
                                       value="<?= htmlspecialchars($_GET['search_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_lider_id" class="form-label">Filtrar por Líder</label>
                                <select class="form-select" id="filter_lider_id" name="filter_lider_id">
                                    <option value="">Todos los líderes</option>
                                    <?php if (isset($leaders) && !empty($leaders)): ?>
                                        <?php foreach ($leaders as $leader): ?>
                                            <option value="<?= $leader['id'] ?>" <?= ($_GET['filter_lider_id'] ?? '') == $leader['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($leader['nombre_completo']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Incluye actividades del líder y su equipo</small>
                            </div>
                            <div class="col-md-3">
                                <label for="grupo_id" class="form-label">Filtrar por Grupos</label>
                                <select class="form-select" id="grupo_id" name="grupo_id">
                                    <option value="">Todos los grupos</option>
                                    <?php if (isset($groups) && !empty($groups)): ?>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?= $group['id'] ?>" <?= ($_GET['grupo_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($group['nombre']) ?>
                                                <small>(<?= $group['miembros_count'] ?? 0 ?> miembros)</small>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Muestra actividades de usuarios en el grupo seleccionado</small>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Monthly Export Section for Admin/Leaders -->
                <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-excel me-2 text-success"></i>Exportar Reporte Mensual
                                </h6>
                                <small class="text-muted">Selecciona el mes para exportar el reporte en Excel</small>
                            </div>
                            <div class="col-md-6">
                                <div class="row align-items-end">
                                    <div class="col-md-8">
                                        <label for="export_month" class="form-label">Mes</label>
                                        <input type="month" class="form-control" id="export_month" 
                                               value="<?= date('Y-m') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-success w-100" onclick="exportMonth()">
                                            <i class="fas fa-download me-1"></i>Exportar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function exportMonth() {
                    const month = document.getElementById('export_month').value;
                    const currentUrl = new URL(window.location);
                    const params = new URLSearchParams(currentUrl.search);
                    params.set('month', month);
                    
                    const exportUrl = '<?= url('activities/export_monthly.php') ?>?' + params.toString();
                    window.location.href = exportUrl;
                }
                
                function exportCurrentMonth() {
                    const currentUrl = new URL(window.location);
                    const params = new URLSearchParams(currentUrl.search);
                    params.set('month', '<?= date('Y-m') ?>');
                    
                    const exportUrl = '<?= url('activities/export_monthly.php') ?>?' + params.toString();
                    window.location.href = exportUrl;
                }
                </script>
                <?php endif; ?>

                <!-- Lista de actividades -->
                
                <!-- Completion Percentage Display -->
                <?php if (!empty($activities)): ?>
                    <?php
                    // Use real completion percentage for current month (calculated in controller)
                    $completionPercentage = $realCompletionPercentage;
                    $percentageClass = $completionPercentage >= 80 ? 'success' : ($completionPercentage >= 60 ? 'warning' : 'danger');
                    ?>
                    <div class="card mb-3 border-<?= $percentageClass ?>">
                        <div class="card-body text-center">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2 text-<?= $percentageClass ?>"></i>
                                        Porcentaje de Cumplimiento (Mes Actual)
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <?= $completedMonthlyActivities ?> de <?= $totalMonthlyActivities ?> actividades completadas este mes
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <div class="display-6 text-<?= $percentageClass ?> fw-bold">
                                        <?= $completionPercentage ?>%
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-<?= $percentageClass ?>" role="progressbar" 
                                             style="width: <?= $completionPercentage ?>%" 
                                             aria-valuenow="<?= $completionPercentage ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Lista de Actividades</h5>
                            <?php if ($_SESSION['user_role'] === 'SuperAdmin' && !empty($activities)): ?>
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary" onclick="selectAllActivities()">
                                    <i class="fas fa-check-square me-1"></i>Seleccionar Todo
                                </button>
                                <button class="btn btn-outline-secondary" onclick="deselectAllActivities()">
                                    <i class="fas fa-square me-1"></i>Deseleccionar Todo
                                </button>
                                <button type="button" class="btn btn-danger" id="deleteActivities" style="display: none;" onclick="deleteSelectedActivities()">
                                    <i class="fas fa-trash me-1"></i>Borrar (<span id="selectedCount">0</span>)
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes actividades registradas</h5>
                                <p class="text-muted">
                                    <?php if ($_SESSION['user_role'] === 'Activista'): ?>
                                        Las actividades te serán asignadas por tu líder o los administradores.
                                    <?php else: ?>
                                        Comienza registrando tu primera actividad.
                                    <?php endif; ?>
                                </p>
                                <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor'])): ?>
                                    <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Crear Primera Actividad
                                    </a>
                                <?php elseif ($_SESSION['user_role'] === 'Activista'): ?>
                                    <a href="<?= url('activities/propose.php') ?>" class="btn btn-primary">
                                        <i class="fas fa-lightbulb me-2"></i>Proponer Actividad
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()" style="cursor: pointer;">
                                            </th>
                                            <?php endif; ?>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                                            <th>Usuario</th>
                                            <?php endif; ?>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Evidencias</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
                                            <td>
                                                <input type="checkbox" class="form-check-input activity-checkbox" 
                                                       value="<?= $activity['id'] ?>" 
                                                       onchange="updateDeleteButton()" 
                                                       style="cursor: pointer;">
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <strong><?= htmlspecialchars($activity['titulo']) ?></strong>
                                                <?php if (!empty($activity['descripcion'])): ?>
                                                    <br><small class="text-muted">
                                                        <?= htmlspecialchars(substr($activity['descripcion'], 0, 100)) ?>
                                                        <?= strlen($activity['descripcion']) > 100 ? '...' : '' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($activity['tipo_nombre']) ?></td>
                                            <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                                            <td>
                                                <span class="text-primary">
                                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($activity['usuario_nombre']) ?>
                                                </span>
                                            </td>
                                            <?php endif; ?>
                                            <td><?= formatDate($activity['fecha_actividad'], 'd/m/Y') ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'completada' => 'success',
                                                    'en_progreso' => 'warning',
                                                    'programada' => 'primary',
                                                    'cancelada' => 'danger'
                                                ][$activity['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $activity['estado'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($activity['estado'] === 'completada' && !empty($activity['evidences'])): ?>
                                                    <div class="evidence-summary">
                                                        <div class="mb-2">
                                                            <strong class="text-primary">
                                                                <i class="fas fa-file-alt me-1"></i>
                                                                <?= count($activity['evidences']) ?> evidencia<?= count($activity['evidences']) != 1 ? 's' : '' ?>
                                                            </strong>
                                                        </div>
                                                        <?php foreach ($activity['evidences'] as $evidence): ?>
                                                            <div class="evidence-item mb-2">
                                                                <?php if ($evidence['tipo_evidencia'] === 'foto' && !empty($evidence['archivo'])): ?>
                                                                    <?php 
                                                                    // Check if archivo already contains full URL
                                                                    $imageUrl = (strpos($evidence['archivo'], 'http://') === 0 || strpos($evidence['archivo'], 'https://') === 0) 
                                                                        ? $evidence['archivo'] 
                                                                        : url('assets/uploads/evidencias/' . $evidence['archivo']);
                                                                    ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                                                 class="evidence-thumbnail me-2" 
                                                                                 alt="Evidencia"
                                                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                                            <small class="text-muted">
                                                                                <i class="fas fa-image me-1"></i>Foto
                                                                            </small>
                                                                        </div>
                                                                        <a href="<?= htmlspecialchars($imageUrl) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Ver imagen">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif ($evidence['tipo_evidencia'] === 'video' && !empty($evidence['archivo'])): ?>
                                                                    <?php 
                                                                    // Check if archivo already contains full URL
                                                                    $videoUrl = (strpos($evidence['archivo'], 'http://') === 0 || strpos($evidence['archivo'], 'https://') === 0) 
                                                                        ? $evidence['archivo'] 
                                                                        : url('assets/uploads/evidencias/' . $evidence['archivo']);
                                                                    ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-video me-1"></i>Video: <?= htmlspecialchars(basename($evidence['archivo'])) ?>
                                                                        </small>
                                                                        <a href="<?= htmlspecialchars($videoUrl) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Ver/Descargar video">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif ($evidence['tipo_evidencia'] === 'audio' && !empty($evidence['archivo'])): ?>
                                                                    <?php 
                                                                    // Check if archivo already contains full URL
                                                                    $audioUrl = (strpos($evidence['archivo'], 'http://') === 0 || strpos($evidence['archivo'], 'https://') === 0) 
                                                                        ? $evidence['archivo'] 
                                                                        : url('assets/uploads/evidencias/' . $evidence['archivo']);
                                                                    ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-music me-1"></i>Audio: <?= htmlspecialchars(basename($evidence['archivo'])) ?>
                                                                        </small>
                                                                        <a href="<?= htmlspecialchars($audioUrl) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Escuchar/Descargar audio">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif ($evidence['tipo_evidencia'] === 'comentario' && !empty($evidence['contenido'])): ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-comment me-1"></i>
                                                                            <?= htmlspecialchars(substr($evidence['contenido'], 0, 50)) ?>
                                                                            <?= strlen($evidence['contenido']) > 50 ? '...' : '' ?>
                                                                        </small>
                                                                        <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" 
                                                                           class="btn btn-sm btn-outline-info" 
                                                                           title="Ver comentario completo">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif (!empty($evidence['archivo'])): ?>
                                                                    <?php 
                                                                    // Check if archivo already contains full URL
                                                                    $fileUrl = (strpos($evidence['archivo'], 'http://') === 0 || strpos($evidence['archivo'], 'https://') === 0) 
                                                                        ? $evidence['archivo'] 
                                                                        : url('assets/uploads/evidencias/' . $evidence['archivo']);
                                                                    ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-file me-1"></i><?= ucfirst($evidence['tipo_evidencia']) ?>: <?= htmlspecialchars(basename($evidence['archivo'])) ?>
                                                                        </small>
                                                                        <a href="<?= htmlspecialchars($fileUrl) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Ver/Descargar archivo">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-file me-1"></i><?= ucfirst($evidence['tipo_evidencia']) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($activity['estado'] === 'completada'): ?>
                                                    <small class="text-muted">Sin evidencias</small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $activity['id'] ?>, '<?= htmlspecialchars(addslashes($activity['titulo'])) ?>')" 
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pagination -->
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                            <div class="mt-4">
                                <nav aria-label="Navegación de páginas">
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous button -->
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">
                                                    <i class="fas fa-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Page numbers -->
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        ?>
                                        
                                        <?php if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => 1]))) ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $totalPages]))) ?>"><?= $totalPages ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Next button -->
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">
                                                    Siguiente <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <!-- Page info -->
                                <div class="text-center text-muted mt-2">
                                    Mostrando página <?= $page ?> de <?= $totalPages ?> 
                                    (<?= $totalActivities ?> actividades en total)
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
    <script>
        function updateDeleteButton() {
            const checkboxes = document.querySelectorAll('.activity-checkbox:checked');
            const deleteBtn = document.getElementById('deleteActivities');
            const countSpan = document.getElementById('selectedCount');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            if (checkboxes.length > 0) {
                deleteBtn.style.display = 'block';
                countSpan.textContent = checkboxes.length;
            } else {
                deleteBtn.style.display = 'none';
            }
            
            // Update select all checkbox state
            const totalCheckboxes = document.querySelectorAll('.activity-checkbox').length;
            if (checkboxes.length === totalCheckboxes && totalCheckboxes > 0) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkboxes.length > 0) {
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.activity-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateDeleteButton();
        }
        
        function selectAllActivities() {
            document.querySelectorAll('.activity-checkbox').forEach(cb => cb.checked = true);
            document.getElementById('selectAll').checked = true;
            updateDeleteButton();
        }
        
        function deselectAllActivities() {
            document.querySelectorAll('.activity-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateDeleteButton();
        }
        
        function deleteSelectedActivities() {
            const checkboxes = document.querySelectorAll('.activity-checkbox:checked');
            const activityIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (activityIds.length === 0) {
                alert('No hay actividades seleccionadas');
                return;
            }
            
            const activityCount = activityIds.length;
            const confirmMsg = activityCount === 1 
                ? '¿Estás seguro de que deseas eliminar esta actividad?' 
                : `¿Estás seguro de que deseas eliminar ${activityCount} actividades?`;
            
            if (!confirm(confirmMsg + '\n\nEsta acción no se puede deshacer y eliminará todas las evidencias asociadas.')) {
                return;
            }
            
            // Crear formulario y enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete.php';
            
            // Token CSRF
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= generateCSRFToken() ?>';
            form.appendChild(csrfInput);
            
            // IDs de actividades
            activityIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activity_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <?php endif; ?>
    
    <script>
    function confirmDelete(activityId, activityTitle) {
        if (confirm('¿Estás seguro de que deseas eliminar la actividad "' + activityTitle + '"?\n\nEsta acción no se puede deshacer.')) {
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('activities/delete.php') ?>';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= generateCSRFToken() ?>';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'activity_id';
            idInput.value = activityId;
            
            form.appendChild(csrfInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>