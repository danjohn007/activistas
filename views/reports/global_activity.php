<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-stats {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .progress {
            height: 25px;
        }
        .badge-status {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('global_activity_report'); 
            ?>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-pie me-2"></i><?= $title ?></h1>
                </div>

                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($fechaDesde) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($fechaHasta) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar Actividad</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>" 
                                       placeholder="Nombre o descripción...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Stats -->
                <?php if (!empty($reportData['activities'])): ?>
                    <?php 
                    $totalTareas = array_sum(array_column($reportData['activities'], 'total_tareas'));
                    $totalCompletadas = array_sum(array_column($reportData['activities'], 'tareas_completadas'));
                    $totalPendientes = array_sum(array_column($reportData['activities'], 'tareas_pendientes'));
                    $promedioGeneral = $totalTareas > 0 ? round(($totalCompletadas / $totalTareas) * 100, 2) : 0;
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-tasks me-2"></i>Total Tareas</h5>
                                    <h2><?= number_format($totalTareas) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-check-circle me-2"></i>Completadas</h5>
                                    <h2><?= number_format($totalCompletadas) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-clock me-2"></i>Pendientes</h5>
                                    <h2><?= number_format($totalPendientes) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-percentage me-2"></i>Cumplimiento</h5>
                                    <h2><?= $promedioGeneral ?>%</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Activities Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Actividades por Tipo
                            <span class="badge bg-secondary"><?= $reportData['total_items'] ?> tipos</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reportData['activities'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No se encontraron actividades para el período seleccionado.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tipo de Actividad</th>
                                            <th class="text-center">Total Tareas</th>
                                            <th class="text-center">Completadas</th>
                                            <th class="text-center">Pendientes</th>
                                            <th class="text-center">Cumplimiento</th>
                                            <th>Última Actividad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData['activities'] as $activity): ?>
                                            <tr class="card-stats">
                                                <td>
                                                    <strong><?= htmlspecialchars($activity['tipo_actividad']) ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= number_format($activity['total_tareas']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?= number_format($activity['tareas_completadas']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning"><?= number_format($activity['tareas_pendientes']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="progress">
                                                        <div class="progress-bar <?= getProgressBarClass($activity['porcentaje_cumplimiento']) ?>" 
                                                             role="progressbar" 
                                                             style="width: <?= $activity['porcentaje_cumplimiento'] ?>%"
                                                             aria-valuenow="<?= $activity['porcentaje_cumplimiento'] ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?= $activity['porcentaje_cumplimiento'] ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($activity['ultima_actividad']): ?>
                                                        <small><?= date('d/m/Y H:i', strtotime($activity['ultima_actividad'])) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">N/A</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($reportData['total_pages'] > 1): ?>
                                <nav aria-label="Paginación" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous button -->
                                        <?php if ($reportData['current_page'] > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $reportData['current_page'] - 1])) ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Page numbers -->
                                        <?php
                                        $startPage = max(1, $reportData['current_page'] - 2);
                                        $endPage = min($reportData['total_pages'], $reportData['current_page'] + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?= $i === $reportData['current_page'] ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Next button -->
                                        <?php if ($reportData['current_page'] < $reportData['total_pages']): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $reportData['current_page'] + 1])) ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
