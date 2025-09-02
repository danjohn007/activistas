<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tareas Pendientes - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .card-task {
            transition: transform 0.2s;
            border-left: 4px solid #ffc107;
        }
        .card-task:hover {
            transform: translateY(-3px);
        }
        .task-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .task-pending {
            border-left-color: #ffc107;
        }
        .task-urgent {
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
            renderSidebar('tasks'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-clipboard-list text-warning me-2"></i>Mis Tareas Pendientes
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-warning text-dark fs-6">
                                <?= count($pendingTasks) ?> tarea(s) pendiente(s)
                            </span>
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

                <!-- Información sobre tareas -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Estas son las tareas que han sido asignadas a ti por tu líder o el administrador. 
                    Para completar una tarea debes subir evidencia (foto, video, comentario, etc.). Una vez subida la evidencia, 
                    la tarea se marcará como completada automáticamente y <strong>no podrás modificar la evidencia</strong>.
                </div>

                <?php if (empty($pendingTasks)): ?>
                    <!-- Sin tareas pendientes -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-muted">¡Excelente trabajo!</h4>
                        <p class="text-muted">No tienes tareas pendientes en este momento.</p>
                        <a href="<?= url('dashboards/activista.php') ?>" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-1"></i>Ir al Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Lista de tareas pendientes -->
                    <div class="row">
                        <?php foreach ($pendingTasks as $task): ?>
                            <?php
                            $isUrgent = strtotime($task['fecha_actividad']) <= strtotime('+3 days');
                            $cardClass = $isUrgent ? 'task-urgent' : 'task-pending';
                            ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card card-task <?= $cardClass ?> h-100">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="card-title mb-0 fw-bold">
                                                <?= htmlspecialchars($task['titulo']) ?>
                                            </h6>
                                            <?php if ($isUrgent): ?>
                                                <span class="badge bg-danger">Urgente</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($task['tipo_nombre']) ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($task['descripcion'])): ?>
                                            <p class="card-text"><?= nl2br(htmlspecialchars($task['descripcion'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="task-meta mb-3">
                                            <div class="mb-1">
                                                <i class="fas fa-user text-primary me-1"></i>
                                                <strong>Asignado por:</strong> <?= htmlspecialchars($task['solicitante_nombre']) ?>
                                            </div>
                                            <div class="mb-1">
                                                <i class="fas fa-calendar text-success me-1"></i>
                                                <strong>Fecha actividad:</strong> 
                                                <?= date('d/m/Y', strtotime($task['fecha_actividad'])) ?>
                                            </div>
                                            <div class="mb-1">
                                                <i class="fas fa-clock text-warning me-1"></i>
                                                <strong>Asignada:</strong> 
                                                <?= date('d/m/Y H:i', strtotime($task['fecha_creacion'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-grid">
                                            <a href="<?= url('tasks/complete.php?id=' . $task['id']) ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-upload me-1"></i>Completar Tarea
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>