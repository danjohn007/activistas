<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Global de Tareas - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .progress-bar-custom {
            transition: width 0.6s ease;
        }
        .task-row:hover {
            background-color: #f8f9fa;
        }
        .badge-cumplimiento {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
        .btn-group .btn {
            margin: 0;
        }
        .task-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        #bulk-actions-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('reports'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-bar text-primary me-2"></i>Informe Global de Tareas
                    </h1>
                </div>

                <!-- Barra de acciones masivas -->
                <div id="bulk-actions-bar" class="alert alert-info mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-square me-2"></i>
                            <strong><span id="selected-count">0</span> actividad(es) seleccionada(s)</strong>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmarEliminarMultiples()">
                                <i class="fas fa-trash me-1"></i>Eliminar Seleccionadas
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="desseleccionarTodas()">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
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

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha Desde
                                </label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($filters['fecha_desde']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha Hasta
                                </label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($filters['fecha_hasta']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="nombre_actividad" class="form-label">
                                    <i class="fas fa-tasks me-1"></i>Nombre de Actividad
                                </label>
                                <input type="text" class="form-control" id="nombre_actividad" name="nombre_actividad" 
                                       value="<?= htmlspecialchars($filters['nombre_actividad'] ?? '') ?>" 
                                       placeholder="Buscar por titulo...">
                            </div>
                            <div class="col-md-3">
                                <label for="nombre_activista" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nombre de Activista
                                </label>
                                <input type="text" class="form-control" id="nombre_activista" name="nombre_activista" 
                                       value="<?= htmlspecialchars($filters['nombre_activista'] ?? '') ?>" 
                                       placeholder="Buscar por nombre...">
                            </div>
                            <div class="col-md-4">
                                <label for="grupo_id" class="form-label">
                                    <i class="fas fa-users me-1"></i>Grupo
                                </label>
                                <select class="form-select" id="grupo_id" name="grupo_id">
                                    <option value="">Todos los grupos</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?= $grupo['id'] ?>" <?= ($filters['grupo_id'] ?? '') == $grupo['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($grupo['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="lider_id" class="form-label">
                                    <i class="fas fa-user-tie me-1"></i>Lider
                                </label>
                                <select class="form-select" id="lider_id" name="lider_id">
                                    <option value="">Todos los lideres</option>
                                    <?php foreach ($lideres as $lider): ?>
                                        <option value="<?= $lider['id'] ?>" <?= ($filters['lider_id'] ?? '') == $lider['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lider['nombre_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Filtrar
                                </button>
                                <?php if (!empty($filters['nombre_actividad']) || !empty($filters['nombre_activista']) || !empty($filters['grupo_id']) || !empty($filters['lider_id'])): ?>
                                    <a href="?fecha_desde=<?= urlencode($filters['fecha_desde']) ?>&fecha_hasta=<?= urlencode($filters['fecha_hasta']) ?>" class="btn btn-secondary w-100 mt-2">
                                        <i class="fas fa-times me-1"></i>Limpiar Filtros
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resumen estadístico -->
                <?php if (!empty($tasks)): ?>
                    <?php
                    $totalTareas = count($tasks);
                    $totalAsignaciones = array_sum(array_column($tasks, 'total_asignadas'));
                    $totalCompletadas = array_sum(array_column($tasks, 'total_completadas'));
                    $promedioGeneral = $totalAsignaciones > 0 ? round(($totalCompletadas / $totalAsignaciones) * 100, 2) : 0;
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center border-primary">
                                <div class="card-body">
                                    <h3 class="text-primary mb-0"><?= $totalTareas ?></h3>
                                    <p class="text-muted mb-0" style="white-space: nowrap;">Tareas Unicas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center border-info">
                                <div class="card-body">
                                    <h3 class="text-info mb-0"><?= $totalAsignaciones ?></h3>
                                    <p class="text-muted mb-0" style="white-space: nowrap;">Total Asignadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center border-success">
                                <div class="card-body">
                                    <h3 class="text-success mb-0"><?= $totalCompletadas ?></h3>
                                    <p class="text-muted mb-0">Completadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center border-warning">
                                <div class="card-body">
                                    <h3 class="text-warning mb-0"><?= $promedioGeneral ?>%</h3>
                                    <p class="text-muted mb-0" style="white-space: nowrap;">Promedio Cumplimiento</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tabla de tareas -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Tareas Asignadas
                            <small class="text-muted">(Click en una fila para ver detalle)</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay tareas en el rango de fechas seleccionado</h5>
                                <p class="text-muted">Intenta cambiar los filtros de fecha</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="task-checkbox" id="select-all" onchange="toggleSelectAll(this)">
                                            </th>
                                            <th>Tarea</th>
                                            <th>Tipo</th>
                                            <th class="text-center">Asignadas</th>
                                            <th class="text-center">Completadas</th>
                                            <th class="text-center">Pendientes</th>
                                            <th style="width: 300px;">% Cumplimiento</th>
                                            <th class="text-center" style="width: 200px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                            <?php
                                            $pendientes = $task['total_asignadas'] - $task['total_completadas'];
                                            $porcentaje = $task['porcentaje_cumplimiento'];
                                            $colorClass = $porcentaje >= 80 ? 'success' : ($porcentaje >= 50 ? 'warning' : 'danger');
                                            
                                            // Construir URL con filtros
                                            $detailUrl = url('reports/task-detail.php?titulo=' . urlencode($task['titulo']) . 
                                                           '&tipo_actividad_id=' . urlencode($task['tipo_actividad_id']) .
                                                           '&fecha_desde=' . urlencode($filters['fecha_desde']) . 
                                                           '&fecha_hasta=' . urlencode($filters['fecha_hasta']));
                                            ?>
                                            <tr class="task-row">
                                                <td class="text-center" onclick="event.stopPropagation();">
                                                    <input type="checkbox" class="task-checkbox task-select" 
                                                           data-titulo="<?= htmlspecialchars($task['titulo'], ENT_QUOTES | ENT_HTML5) ?>" 
                                                           data-tipo="<?= $task['tipo_actividad_id'] ?>"
                                                           data-total="<?= $task['total_asignadas'] ?>"
                                                           onchange="updateBulkActions()">
                                                </td>
                                                <td onclick="window.location.href='<?= $detailUrl ?>'" style="cursor:pointer;">
                                                    <strong><?= htmlspecialchars($task['titulo']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Primera: <?= date('d/m/Y', strtotime($task['primera_asignacion'])) ?>
                                                    </small>
                                                </td>
                                                <td onclick="window.location.href='<?= $detailUrl ?>'" style="cursor:pointer;">
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($task['tipo_actividad']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center" onclick="window.location.href='<?= $detailUrl ?>'" style="cursor:pointer;">
                                                    <h5 class="mb-0"><?= $task['total_asignadas'] ?></h5>
                                                </td>
                                                <td class="text-center" onclick="window.location.href='<?= $detailUrl ?>'" style="cursor:pointer;">
                                                    <h5 class="mb-0 text-success"><?= $task['total_completadas'] ?></h5>
                                                </td>
                                                <td class="text-center" onclick="window.location.href='<?= $detailUrl ?>'" style="cursor:pointer;">
                                                    <h5 class="mb-0 text-warning"><?= $pendientes ?></h5>
                                                </td>
                                                <td onclick="window.location.href='<?= $detailUrl ?>'" style="cursor:pointer;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 25px;">
                                                            <div class="progress-bar progress-bar-custom bg-<?= $colorClass ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?= $porcentaje ?>%"
                                                                 aria-valuenow="<?= $porcentaje ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                                <?= $porcentaje ?>%
                                                            </div>
                                                        </div>
                                                        <span class="badge badge-cumplimiento bg-<?= $colorClass ?>">
                                                            <?= $porcentaje ?>%
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="<?= $detailUrl ?>" class="btn btn-sm btn-primary" title="Ver Detalle">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning btn-edit-task" 
                                                                data-titulo="<?= htmlspecialchars($task['titulo'], ENT_QUOTES) ?>"
                                                                data-tipo-id="<?= $task['tipo_actividad_id'] ?>"
                                                                data-tipo-nombre="<?= htmlspecialchars($task['tipo_actividad'], ENT_QUOTES) ?>"
                                                                data-descripcion="<?= htmlspecialchars($task['descripcion'] ?? '', ENT_QUOTES) ?>"
                                                                data-fecha-actividad="<?= htmlspecialchars($task['fecha_actividad'] ?? '', ENT_QUOTES) ?>"
                                                                data-fecha-publicacion="<?= isset($task['fecha_publicacion']) ? date('Y-m-d', strtotime($task['fecha_publicacion'])) : '' ?>"
                                                                data-hora-publicacion="<?= isset($task['hora_publicacion']) ? date('H:i', strtotime($task['hora_publicacion'])) : '' ?>"
                                                                data-fecha-cierre="<?= htmlspecialchars($task['fecha_cierre'] ?? '', ENT_QUOTES) ?>"
                                                                data-hora-cierre="<?= htmlspecialchars($task['hora_cierre'] ?? '', ENT_QUOTES) ?>"
                                                                onclick="event.stopPropagation();" 
                                                                title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger btn-delete-task" 
                                                                data-titulo="<?= htmlspecialchars($task['titulo'], ENT_QUOTES) ?>"
                                                                data-tipo-id="<?= $task['tipo_actividad_id'] ?>"
                                                                data-total="<?= $task['total_asignadas'] ?>"
                                                                onclick="event.stopPropagation();" 
                                                                title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Editar Actividad -->
    <div class="modal fade" id="editarActividadModal" tabindex="-1" aria-labelledby="editarActividadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formEditarActividad" method="POST" action="<?= url('reports/edit-global-task.php') ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarActividadModalLabel">
                            <i class="fas fa-edit text-warning me-2"></i>Editar Actividad Global
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="titulo_original" id="edit_titulo_original">
                        <input type="hidden" name="tipo_actividad_id" id="edit_tipo_actividad_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Importante:</strong> Los cambios se aplicarán a todas las asignaciones de esta actividad para todos los activistas.
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título de la Actividad</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_fecha_actividad" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Fecha de la Actividad
                            </label>
                            <input type="date" class="form-control" id="edit_fecha_actividad" name="fecha_actividad" required>
                            <small class="text-muted">Fecha en que se realizará la actividad</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_fecha_publicacion" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Fecha de Publicación
                                </label>
                                <input type="date" class="form-control" id="edit_fecha_publicacion" name="fecha_publicacion">
                                <small class="text-muted">Cuándo se publicará para los usuarios</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_hora_publicacion" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Hora de Publicación
                                </label>
                                <input type="time" class="form-control" id="edit_hora_publicacion" name="hora_publicacion">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_fecha_cierre" class="form-label">
                                    <i class="fas fa-calendar-times me-1"></i>Fecha de Entrega/Cierre
                                </label>
                                <input type="date" class="form-control" id="edit_fecha_cierre" name="fecha_cierre">
                                <small class="text-muted">Fecha límite para entregar evidencia</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_hora_cierre" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Hora de Cierre
                                </label>
                                <input type="time" class="form-control" id="edit_hora_cierre" name="hora_cierre">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Actividad</label>
                            <input type="text" class="form-control" id="edit_tipo_nombre" disabled>
                            <small class="text-muted">El tipo de actividad no se puede cambiar</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Confirmar Eliminación Múltiple -->
    <div class="modal fade" id="confirmarEliminarMultiplesModal" tabindex="-1" aria-labelledby="confirmarEliminarMultiplesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formEliminarMultiples" method="POST" action="<?= url('reports/delete-multiple-tasks.php') ?>">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="confirmarEliminarMultiplesModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación Múltiple
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="actividades" id="delete_multiple_actividades">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>¡ADVERTENCIA!</strong> Esta acción no se puede deshacer.
                        </div>
                        
                        <p>¿Estás seguro de que deseas eliminar las <strong id="delete_multiple_count"></strong> actividades seleccionadas?</p>
                        
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong>Actividades a eliminar:</strong>
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                <ul id="delete_multiple_list" class="list-unstyled mb-0"></ul>
                            </div>
                        </div>
                        
                        <p class="text-danger mt-3">
                            <strong>Total de asignaciones que se eliminarán: <span id="delete_multiple_total"></span></strong>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Eliminar Todas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Confirmar Eliminación -->
    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('reports/delete-global-task.php') ?>">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="confirmarEliminarModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="titulo" id="delete_titulo">
                        <input type="hidden" name="tipo_actividad_id" id="delete_tipo_actividad_id">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>¡ADVERTENCIA!</strong> Esta acción no se puede deshacer.
                        </div>
                        
                        <p>¿Estás seguro de que deseas eliminar la actividad <strong id="delete_titulo_display"></strong>?</p>
                        <p class="text-danger">Se eliminarán <strong id="delete_total_asignadas"></strong> asignaciones de esta actividad para todos los activistas.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Eliminar Definitivamente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Event listeners para botones de editar y eliminar usando event delegation
        document.addEventListener('DOMContentLoaded', function() {
            // Botones de editar
            document.querySelectorAll('.btn-edit-task').forEach(btn => {
                btn.addEventListener('click', function() {
                    const titulo = this.dataset.titulo;
                    const tipoId = this.dataset.tipoId;
                    const tipoNombre = this.dataset.tipoNombre;
                    const descripcion = this.dataset.descripcion;
                    const fechaActividad = this.dataset.fechaActividad;
                    const fechaPublicacion = this.dataset.fechaPublicacion;
                    const horaPublicacion = this.dataset.horaPublicacion;
                    const fechaCierre = this.dataset.fechaCierre;
                    const horaCierre = this.dataset.horaCierre;
                    
                    editarActividad(titulo, tipoId, tipoNombre, descripcion, fechaActividad, fechaPublicacion, horaPublicacion, fechaCierre, horaCierre);
                });
            });
            
            // Botones de eliminar
            document.querySelectorAll('.btn-delete-task').forEach(btn => {
                btn.addEventListener('click', function() {
                    const titulo = this.dataset.titulo;
                    const tipoId = this.dataset.tipoId;
                    const total = this.dataset.total;
                    
                    confirmarEliminar(titulo, tipoId, total);
                });
            });
        });
        
        function editarActividad(titulo, tipoActividadId, tipoActividad, descripcion, fechaActividad, fechaPublicacion, horaPublicacion, fechaCierre, horaCierre) {
            // Cargar todos los datos directamente
            document.getElementById('edit_titulo_original').value = titulo;
            document.getElementById('edit_tipo_actividad_id').value = tipoActividadId;
            document.getElementById('edit_titulo').value = titulo;
            document.getElementById('edit_tipo_nombre').value = tipoActividad;
            document.getElementById('edit_descripcion').value = descripcion || '';
            document.getElementById('edit_fecha_actividad').value = fechaActividad || '';
            document.getElementById('edit_fecha_publicacion').value = fechaPublicacion || '';
            document.getElementById('edit_hora_publicacion').value = horaPublicacion || '';
            document.getElementById('edit_fecha_cierre').value = fechaCierre || '';
            document.getElementById('edit_hora_cierre').value = horaCierre || '';
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('editarActividadModal'));
            modal.show();
        }
        
        function confirmarEliminar(titulo, tipoActividadId, totalAsignadas) {
            document.getElementById('delete_titulo').value = titulo;
            document.getElementById('delete_tipo_actividad_id').value = tipoActividadId;
            document.getElementById('delete_titulo_display').textContent = titulo;
            document.getElementById('delete_total_asignadas').textContent = totalAsignadas;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
            modal.show();
        }
        
        // Funciones para selección múltiple
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.task-select');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.task-select:checked');
            const count = checkboxes.length;
            const bulkBar = document.getElementById('bulk-actions-bar');
            const selectedCount = document.getElementById('selected-count');
            const selectAll = document.getElementById('select-all');
            
            selectedCount.textContent = count;
            
            if (count > 0) {
                bulkBar.style.display = 'block';
            } else {
                bulkBar.style.display = 'none';
            }
            
            // Actualizar checkbox de "seleccionar todo"
            const totalCheckboxes = document.querySelectorAll('.task-select').length;
            selectAll.checked = count === totalCheckboxes && count > 0;
        }
        
        function desseleccionarTodas() {
            const checkboxes = document.querySelectorAll('.task-select');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
            updateBulkActions();
        }
        
        function confirmarEliminarMultiples() {
            const checkboxes = document.querySelectorAll('.task-select:checked');
            
            if (checkboxes.length === 0) {
                alert('Por favor selecciona al menos una actividad');
                return;
            }
            
            const actividades = [];
            const listItems = [];
            let totalAsignaciones = 0;
            
            checkboxes.forEach(cb => {
                const titulo = cb.dataset.titulo;
                const tipo = cb.dataset.tipo;
                const total = parseInt(cb.dataset.total);
                
                actividades.push({ titulo: titulo, tipo_actividad_id: tipo });
                listItems.push(`<li class="mb-2"><i class="fas fa-circle-notch fa-xs text-danger me-2"></i><strong>${titulo}</strong> (${total} asignaciones)</li>`);
                totalAsignaciones += total;
            });
            
            document.getElementById('delete_multiple_actividades').value = JSON.stringify(actividades);
            document.getElementById('delete_multiple_count').textContent = actividades.length;
            document.getElementById('delete_multiple_list').innerHTML = listItems.join('');
            document.getElementById('delete_multiple_total').textContent = totalAsignaciones;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmarEliminarMultiplesModal'));
            modal.show();
        }
    </script>
</body>
</html>
