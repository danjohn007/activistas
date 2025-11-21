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
            cursor: pointer;
        }
        .badge-cumplimiento {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
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
                                            <th>Tarea</th>
                                            <th>Tipo</th>
                                            <th class="text-center">Asignadas</th>
                                            <th class="text-center">Completadas</th>
                                            <th class="text-center">Pendientes</th>
                                            <th style="width: 300px;">% Cumplimiento</th>
                                            <th class="text-center">Accion</th>
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
                                            <tr class="task-row" onclick="window.location.href='<?= $detailUrl ?>'">
                                                <td>
                                                    <strong><?= htmlspecialchars($task['titulo']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Primera: <?= date('d/m/Y', strtotime($task['primera_asignacion'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($task['tipo_actividad']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <h5 class="mb-0"><?= $task['total_asignadas'] ?></h5>
                                                </td>
                                                <td class="text-center">
                                                    <h5 class="mb-0 text-success"><?= $task['total_completadas'] ?></h5>
                                                </td>
                                                <td class="text-center">
                                                    <h5 class="mb-0 text-warning"><?= $pendientes ?></h5>
                                                </td>
                                                <td>
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
                                                    <a href="<?= $detailUrl ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i>Ver Detalle
                                                    </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
