<?php
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Acceso directo no permitido');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas de <?php echo htmlspecialchars($activista['nombre_completo']); ?> - Corte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .task-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .task-completada {
            border-left-color: #198754;
            background-color: #d1e7dd;
        }
        .task-pendiente {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .evidence-image {
            max-width: 100px;
            max-height: 100px;
            cursor: pointer;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('cortes'); 
            ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="content-wrapper" style="padding: 2rem 0;">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h2>
                        <i class="fas fa-tasks me-2"></i>
                        Tareas de <?php echo htmlspecialchars($activista['nombre_completo']); ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Corte: <?php echo htmlspecialchars($corte['nombre']); ?> 
                        (<?php echo date('d/m/Y', strtotime($corte['fecha_inicio'])); ?> - 
                        <?php echo date('d/m/Y', strtotime($corte['fecha_fin'])); ?>)
                    </p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="detail.php?id=<?php echo $corte['id']; ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Corte
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Ranking</h6>
                            <h2>#<?php echo $activista['ranking_posicion']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Asignadas</h6>
                            <h2 class="text-info"><?php echo $activista['tareas_asignadas']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Entregadas</h6>
                            <h2 class="text-success"><?php echo $activista['tareas_entregadas']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Cumplimiento</h6>
                            <h2 class="text-primary"><?php echo number_format($activista['porcentaje_cumplimiento'], 1); ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tareas -->
            <?php if (empty($tareas)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay tareas registradas en este periodo para este activista.
                </div>
            <?php else: ?>
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#todas">
                            Todas (<?php echo count($tareas); ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#completadas">
                            Completadas (<?php echo count(array_filter($tareas, fn($t) => $t['estado'] === 'completada')); ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#pendientes">
                            Pendientes (<?php echo count(array_filter($tareas, fn($t) => $t['estado'] !== 'completada')); ?>)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Todas -->
                    <div class="tab-pane fade show active" id="todas">
                        <?php foreach ($tareas as $tarea): ?>
                            <?php include __DIR__ . '/../partials/tarea_card.php'; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Completadas -->
                    <div class="tab-pane fade" id="completadas">
                        <?php 
                        $completadas = array_filter($tareas, fn($t) => $t['estado'] === 'completada');
                        if (empty($completadas)): ?>
                            <p class="text-muted">No hay tareas completadas</p>
                        <?php else: ?>
                            <?php foreach ($completadas as $tarea): ?>
                                <?php include __DIR__ . '/../partials/tarea_card.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pendientes -->
                    <div class="tab-pane fade" id="pendientes">
                        <?php 
                        $pendientes = array_filter($tareas, fn($t) => $t['estado'] !== 'completada');
                        if (empty($pendientes)): ?>
                            <p class="text-muted">No hay tareas pendientes</p>
                        <?php else: ?>
                            <?php foreach ($pendientes as $tarea): ?>
                                <?php include __DIR__ . '/../partials/tarea_card.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
