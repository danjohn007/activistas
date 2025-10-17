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
        .group-card {
            transition: transform 0.2s;
            margin-bottom: 2rem;
        }
        .group-card:hover {
            transform: translateY(-5px);
        }
        .leader-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: inline-block;
        }
        .performer-row {
            padding: 0.8rem;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        .performer-row:hover {
            background-color: #f8f9fa;
        }
        .performer-row:last-child {
            border-bottom: none;
        }
        .medal {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        .progress-custom {
            height: 25px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('best_by_group_report'); 
            ?>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-trophy me-2"></i><?= $title ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" 
                           class="btn btn-success" title="Exportar a Excel">
                            <i class="fas fa-file-excel me-1"></i>Exportar a Excel
                        </a>
                    </div>
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
                            <div class="col-md-4">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($fechaDesde) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($fechaHasta) ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Groups and Best Performers -->
                <?php if (empty($reportData['groups'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No se encontraron grupos activos en el sistema.
                    </div>
                <?php else: ?>
                    <?php foreach ($reportData['groups'] as $group): ?>
                        <div class="card group-card">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users me-2"></i><?= htmlspecialchars($group['nombre']) ?>
                                    </h5>
                                    <span class="badge bg-light text-dark">
                                        Cumplimiento Promedio: <?= $group['avg_compliance'] ?>%
                                    </span>
                                </div>
                                <?php if (!empty($group['descripcion'])): ?>
                                    <small><?= htmlspecialchars($group['descripcion']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <!-- Leader Section -->
                                <?php if (!empty($group['leader'])): ?>
                                    <div class="leader-badge">
                                        <i class="fas fa-crown me-2"></i>Líder del Grupo
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-user-tie me-2"></i>
                                                        <?= htmlspecialchars($group['leader']['nombre_completo']) ?>
                                                    </h6>
                                                    <small class="text-muted"><?= htmlspecialchars($group['leader']['email']) ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="mb-1">
                                                        <span class="badge bg-success">
                                                            <?= $group['leader']['tareas_completadas'] ?> completadas
                                                        </span>
                                                        <span class="badge bg-info">
                                                            <?= $group['leader']['tareas_asignadas'] ?> asignadas
                                                        </span>
                                                    </div>
                                                    <div class="progress progress-custom" style="width: 200px;">
                                                        <div class="progress-bar <?= getProgressBarClass($group['leader']['porcentaje_cumplimiento']) ?>" 
                                                             role="progressbar" 
                                                             style="width: <?= $group['leader']['porcentaje_cumplimiento'] ?>%">
                                                            <?= $group['leader']['porcentaje_cumplimiento'] ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Best Activists Section -->
                                <h6 class="mb-3">
                                    <i class="fas fa-star me-2"></i>Top 5 Activistas del Grupo
                                </h6>
                                <?php if (empty($group['best_performers'])): ?>
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No hay activistas registrados en este grupo.
                                    </div>
                                <?php else: ?>
                                    <div class="border rounded">
                                        <?php foreach ($group['best_performers'] as $index => $performer): ?>
                                            <div class="performer-row">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <span class="medal">
                                                            <?php
                                                            if ($index === 0) echo '🥇';
                                                            elseif ($index === 1) echo '🥈';
                                                            elseif ($index === 2) echo '🥉';
                                                            else echo '<i class="fas fa-user-circle text-muted"></i>';
                                                            ?>
                                                        </span>
                                                        <div>
                                                            <strong><?= htmlspecialchars($performer['nombre_completo']) ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($performer['email']) ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="mb-1">
                                                            <span class="badge bg-success me-1">
                                                                <i class="fas fa-check me-1"></i><?= $performer['tareas_completadas'] ?>
                                                            </span>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-tasks me-1"></i><?= $performer['tareas_asignadas'] ?>
                                                            </span>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-star me-1"></i><?= $performer['ranking_puntos'] ?> pts
                                                            </span>
                                                        </div>
                                                        <div class="progress progress-custom" style="width: 200px;">
                                                            <div class="progress-bar <?= getProgressBarClass($performer['porcentaje_cumplimiento']) ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?= $performer['porcentaje_cumplimiento'] ?>%">
                                                                <?= $performer['porcentaje_cumplimiento'] ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
