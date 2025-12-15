<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .task-card {
            transition: transform 0.2s;
            border-left: 4px solid #007bff;
        }
        .task-card:hover {
            transform: translateY(-2px);
        }
        .task-completed {
            border-left-color: #28a745;
        }
        .task-pending {
            border-left-color: #ffc107;
        }
        .task-expired {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('activist_report'); 
            ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-check text-primary me-2"></i><?= htmlspecialchars($title) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('reports/activists.php') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Reporte
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Information Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Información del Activista
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Nombre:</strong></p>
                                <p><?= htmlspecialchars($user['nombre_completo']) ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Rol:</strong></p>
                                <p><span class="badge bg-info"><?= htmlspecialchars($user['rol']) ?></span></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Email:</strong></p>
                                <p><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Teléfono:</strong></p>
                                <p><?= htmlspecialchars($user['telefono']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Snapshot Mode Alert -->
                <?php if (isset($isSnapshot) && $isSnapshot): ?>
                <div class="alert alert-warning mb-4">
                    <h5 class="alert-heading">
                        <i class="fas fa-camera me-2"></i>Viendo Snapshot Congelado
                    </h5>
                    <hr>
                    <p class="mb-2">
                        <strong>Corte:</strong> <?= htmlspecialchars($snapshotInfo['nombre']) ?>
                        <?php if (!empty($snapshotInfo['descripcion'])): ?>
                            - <?= htmlspecialchars($snapshotInfo['descripcion']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="mb-2">
                        <strong>Periodo:</strong> 
                        <?= date('d/m/Y', strtotime($snapshotInfo['fecha_inicio'])) ?> 
                        al 
                        <?= date('d/m/Y', strtotime($snapshotInfo['fecha_fin'])) ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Los datos mostrados son un <strong>snapshot congelado</strong> del momento en que se creó el corte 
                        (<?= date('d/m/Y H:i', strtotime($snapshotInfo['fecha_creacion'])) ?>).
                        Estas estadísticas NO cambian aunque se completen nuevas tareas.
                    </p>
                </div>
                <?php elseif (!empty($_GET['fecha_desde']) || !empty($_GET['fecha_hasta'])): ?>
                <div class="alert alert-primary mb-4">
                    <i class="fas fa-calendar me-2"></i><strong>Periodo Filtrado (Tiempo Real):</strong>
                    <?php if (!empty($_GET['fecha_desde'])): ?>
                        Desde <strong><?= date('d/m/Y', strtotime($_GET['fecha_desde'])) ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($_GET['fecha_hasta'])): ?>
                        Hasta <strong><?= date('d/m/Y', strtotime($_GET['fecha_hasta'])) ?></strong>
                    <?php endif; ?>
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-sync-alt me-1"></i>
                        Los datos se actualizan en tiempo real y pueden cambiar si el activista completa nuevas tareas.
                    </small>
                </div>
                <?php endif; ?>

                <!-- Statistics Summary -->
                <div class="row mb-4">
                    <?php
                    // Use frozen stats if viewing a snapshot, otherwise calculate from tasks
                    if (isset($isSnapshot) && $isSnapshot && isset($frozenStatistics) && $frozenStatistics) {
                        $totalAssigned = $frozenStatistics['tareas_asignadas'];
                        $totalCompleted = $frozenStatistics['tareas_entregadas'];
                        $completionPercentage = $frozenStatistics['porcentaje_cumplimiento'];
                    } else {
                        $totalAssigned = count($assignedTasks) + count($completedTasks);
                        $totalCompleted = count($completedTasks);
                        $completionPercentage = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) * 100 : 0;
                    }
                    ?>
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                                <h3 class="text-primary"><?= $totalAssigned ?></h3>
                                <p class="mb-0">Total Tareas Asignadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="text-success"><?= $totalCompleted ?></h3>
                                <p class="mb-0">Tareas Completadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i>
                                <h3 class="text-warning"><?= count($assignedTasks) ?></h3>
                                <p class="mb-0">Tareas Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                                <h3 class="text-info"><?= number_format($completionPercentage, 1) ?>%</h3>
                                <p class="mb-0">% Cumplimiento</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calculation Explanation -->
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading">
                        <i class="fas fa-calculator me-2"></i>Cómo se calcula el rendimiento
                    </h5>
                    <hr>
                    <?php if (isset($isSnapshot) && $isSnapshot): ?>
                    <div class="alert alert-warning mb-2">
                        <i class="fas fa-camera me-1"></i>
                        <strong>Estas son estadísticas CONGELADAS del corte.</strong>
                        Los números se calcularon cuando se creó el snapshot y no cambian.
                    </div>
                    <?php endif; ?>
                    <p class="mb-2"><strong>Fórmula del Porcentaje de Cumplimiento:</strong></p>
                    <p class="mb-2">
                        <code>(Tareas Entregadas/Completadas / Total de Tareas Asignadas) × 100</code>
                    </p>
                    <p class="mb-2">
                        <strong>En este caso:</strong> (<?= $totalCompleted ?> entregadas / <?= $totalAssigned ?> asignadas) × 100 = <strong class="text-primary"><?= number_format($completionPercentage, 1) ?>%</strong>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>El sistema cuenta:</strong>
                    </p>
                    <ul class="mb-2">
                        <li><strong>Tareas Asignadas:</strong> Todas las tareas que se le han asignado a este activista (<?= $totalAssigned ?>)</li>
                        <li><strong>Tareas Entregadas:</strong> Solo las tareas que el activista completó y entregó evidencia (<?= $totalCompleted ?>)</li>
                        <li><strong>Tareas Pendientes:</strong> Las que aún no ha entregado (<?= count($assignedTasks) ?>)</li>
                    </ul>
                    <p class="mb-0 small text-muted">
                        <strong>Nota:</strong> Solo se cuentan las tareas asignadas (tarea_pendiente = 1). Las actividades regulares creadas por el usuario NO afectan este cálculo.
                    </p>
                </div>

                <!-- Pending Tasks Section -->
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="fas fa-hourglass-half me-2"></i>Tareas Pendientes
                            <span class="badge bg-dark ms-2"><?= count($assignedTasks) ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignedTasks)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-double fa-3x text-success mb-3"></i>
                                <p class="text-success">¡Excelente! No hay tareas pendientes</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($assignedTasks as $task): ?>
                                    <?php
                                    // Check if task is expired
                                    $isExpired = false;
                                    if (!empty($task['fecha_cierre'])) {
                                        $today = new DateTime();
                                        $closeDate = new DateTime($task['fecha_cierre']);
                                        if (!empty($task['hora_cierre'])) {
                                            $closeDate->setTime(...explode(':', $task['hora_cierre']));
                                        }
                                        $isExpired = $closeDate <= $today;
                                    }
                                    $taskClass = $isExpired ? 'task-expired' : 'task-pending';
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card task-card <?= $taskClass ?> h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <?php if ($isExpired): ?>
                                                        <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock text-warning me-1"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($task['titulo']) ?>
                                                </h6>
                                                <?php if ($isExpired): ?>
                                                    <span class="badge bg-danger mb-2">Vencida</span>
                                                <?php endif; ?>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($task['tipo_nombre']) ?>
                                                    </small>
                                                </p>
                                                <div class="mb-2">
                                                    <small>
                                                        <i class="fas fa-calendar text-primary me-1"></i>
                                                        <strong>Fecha actividad:</strong> <?= date('d/m/Y', strtotime($task['fecha_actividad'])) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($task['fecha_cierre'])): ?>
                                                    <div class="mb-2">
                                                        <small class="<?= $isExpired ? 'text-danger' : '' ?>">
                                                            <i class="fas fa-clock <?= $isExpired ? 'text-danger' : 'text-warning' ?> me-1"></i>
                                                            <strong>Vigencia:</strong> <?= date('d/m/Y', strtotime($task['fecha_cierre'])) ?>
                                                            <?php if (!empty($task['hora_cierre'])): ?>
                                                                <?= date('H:i', strtotime($task['hora_cierre'])) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mb-2">
                                                    <small>
                                                        <i class="fas fa-calendar-plus text-info me-1"></i>
                                                        <strong>Asignada:</strong> <?= date('d/m/Y H:i', strtotime($task['fecha_creacion'])) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($task['solicitante_nombre'])): ?>
                                                    <div class="mb-2">
                                                        <small>
                                                            <i class="fas fa-user-tie text-info me-1"></i>
                                                            <strong>Asignado por:</strong> <?= htmlspecialchars($task['solicitante_nombre']) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <a href="<?= url('activities/detail.php?id=' . $task['id']) ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="fas fa-eye me-1"></i>Ver Detalle
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed Tasks Section -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Tareas Completadas
                            <span class="badge bg-light text-dark ms-2"><?= count($completedTasks) ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($completedTasks)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay tareas completadas aún</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($completedTasks as $task): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card task-card task-completed h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-check-circle text-success me-1"></i>
                                                    <?= htmlspecialchars($task['titulo']) ?>
                                                </h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($task['tipo_nombre']) ?>
                                                    </small>
                                                </p>
                                                <div class="mb-2">
                                                    <small>
                                                        <i class="fas fa-calendar text-primary me-1"></i>
                                                        <strong>Fecha actividad:</strong> <?= date('d/m/Y', strtotime($task['fecha_actividad'])) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($task['fecha_cierre'])): ?>
                                                    <div class="mb-2">
                                                        <small>
                                                            <i class="fas fa-clock text-danger me-1"></i>
                                                            <strong>Vigencia:</strong> <?= date('d/m/Y', strtotime($task['fecha_cierre'])) ?>
                                                            <?php if (!empty($task['hora_cierre'])): ?>
                                                                <?= date('H:i', strtotime($task['hora_cierre'])) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mb-2">
                                                    <small>
                                                        <i class="fas fa-check text-success me-1"></i>
                                                        <strong>Completada:</strong> <?= date('d/m/Y H:i', strtotime($task['fecha_actualizacion'])) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($task['solicitante_nombre'])): ?>
                                                    <div class="mb-2">
                                                        <small>
                                                            <i class="fas fa-user-tie text-info me-1"></i>
                                                            <strong>Asignado por:</strong> <?= htmlspecialchars($task['solicitante_nombre']) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <a href="<?= url('activities/detail.php?id=' . $task['id']) ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="fas fa-eye me-1"></i>Ver Detalle
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
