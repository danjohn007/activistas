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
        .card-img-container img {
            transition: transform 0.2s ease-in-out;
        }
        .card-img-container img:hover {
            transform: scale(1.02);
        }
        .activity-image-overlay {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 0.375rem;
        }
        .image-zoom-cursor {
            cursor: zoom-in;
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
                            // Calcular urgencia basada en fecha de cierre (vigencia)
                            $isUrgent = false;
                            $urgencyClass = 'task-pending';
                            $urgencyDays = null;
                            $urgencyText = '';
                            
                            if (!empty($task['fecha_cierre'])) {
                                $today = new DateTime();
                                $closeDate = new DateTime($task['fecha_cierre']);
                                
                                // Si tiene hora de cierre, ajustarla
                                if (!empty($task['hora_cierre'])) {
                                    $closeDate->setTime(
                                        ...(explode(':', $task['hora_cierre']))
                                    );
                                }
                                
                                $diff = $today->diff($closeDate);
                                $urgencyDays = $closeDate > $today ? $diff->days : -$diff->days;
                                
                                // Marcar como urgente si queda 1 día o menos
                                if ($urgencyDays <= 1 && $closeDate > $today) {
                                    $isUrgent = true;
                                    $urgencyClass = 'task-urgent';
                                    $urgencyText = $urgencyDays == 0 ? 'Vence hoy' : 'Vence mañana';
                                } elseif ($closeDate <= $today) {
                                    $isUrgent = true;
                                    $urgencyClass = 'task-urgent';
                                    $urgencyText = 'Vencida';
                                } else {
                                    $urgencyText = "Vence en {$urgencyDays} días";
                                }
                            } else {
                                // Fallback: usar fecha de actividad para urgencia si no hay fecha de cierre
                                $isUrgent = strtotime($task['fecha_actividad']) <= strtotime('+3 days');
                                $urgencyClass = $isUrgent ? 'task-urgent' : 'task-pending';
                            }
                            ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card card-task <?= $urgencyClass ?> h-100">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="card-title mb-0 fw-bold">
                                                <?= htmlspecialchars($task['titulo']) ?>
                                            </h6>
                                            <?php if ($isUrgent): ?>
                                                <span class="badge bg-danger">
                                                    <?= $urgencyText ?: 'Urgente' ?>
                                                </span>
                                            <?php elseif (!empty($urgencyText)): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $urgencyText ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($task['tipo_nombre']) ?>
                                        </small>
                                    </div>
                                    <!-- REQUIREMENT IMPLEMENTATION: Enhanced image display for activity -->
                                    <!-- Display primary activity image prominently if available -->
                                    <?php
                                    $primaryImage = null;
                                    $otherAttachments = [];
                                    
                                    // Find the first image attachment to display as primary
                                    if (!empty($task['initial_attachments'])) {
                                        foreach ($task['initial_attachments'] as $attachment) {
                                            if (!empty($attachment['archivo']) && $attachment['tipo_evidencia'] === 'foto') {
                                                if ($primaryImage === null) {
                                                    $primaryImage = $attachment;
                                                } else {
                                                    $otherAttachments[] = $attachment;
                                                }
                                            } else {
                                                $otherAttachments[] = $attachment;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <?php if ($primaryImage): ?>
                                        <div class="card-img-container position-relative mb-3">
                                            <img src="<?= url('assets/uploads/evidencias/' . htmlspecialchars($primaryImage['archivo'])) ?>" 
                                                 class="card-img-top rounded image-zoom-cursor" 
                                                 alt="Imagen de actividad: <?= htmlspecialchars($task['titulo']) ?>" 
                                                 style="height: 200px; object-fit: cover;"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#imageModal<?= $task['id'] ?>"
                                                 loading="lazy">
                                            <div class="position-absolute top-0 end-0 p-2">
                                                <span class="badge activity-image-overlay">
                                                    <i class="fas fa-search-plus me-1"></i>Click para ampliar
                                                </span>
                                            </div>
                                            <?php if (!empty($primaryImage['contenido'])): ?>
                                                <div class="position-absolute bottom-0 start-0 end-0 p-2">
                                                    <div class="activity-image-overlay p-2 rounded">
                                                        <small class="text-white">
                                                            <i class="fas fa-quote-left me-1"></i>
                                                            <?= htmlspecialchars(substr($primaryImage['contenido'], 0, 80)) ?>
                                                            <?= strlen($primaryImage['contenido']) > 80 ? '...' : '' ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Modal for full-size image -->
                                        <div class="modal fade" id="imageModal<?= $task['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-image me-2"></i><?= htmlspecialchars($task['titulo']) ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="<?= url('assets/uploads/evidencias/' . htmlspecialchars($primaryImage['archivo'])) ?>" 
                                                             class="img-fluid rounded" 
                                                             alt="Imagen de actividad completa">
                                                        <?php if (!empty($primaryImage['contenido'])): ?>
                                                            <div class="mt-3">
                                                                <h6 class="text-muted">Descripción:</h6>
                                                                <p><?= htmlspecialchars($primaryImage['contenido']) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <?php if (!empty($task['descripcion'])): ?>
                                            <p class="card-text"><?= nl2br(htmlspecialchars($task['descripcion'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <!-- Display additional attachments if any -->
                                        <?php if (!empty($otherAttachments)): ?>
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">
                                                    <i class="fas fa-paperclip me-1"></i>Archivos adicionales:
                                                </h6>
                                                <div class="row">
                                                    <?php foreach ($otherAttachments as $attachment): ?>
                                                        <?php if (!empty($attachment['archivo'])): ?>
                                                            <div class="col-md-6 mb-2">
                                                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                    <?php 
                                                                    $iconClass = 'fas fa-file';
                                                                    switch ($attachment['tipo_evidencia']) {
                                                                        case 'foto':
                                                                            $iconClass = 'fas fa-image text-primary';
                                                                            break;
                                                                        case 'video':
                                                                            $iconClass = 'fas fa-video text-danger';
                                                                            break;
                                                                        case 'audio':
                                                                            $iconClass = 'fas fa-music text-success';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <i class="<?= $iconClass ?> me-2"></i>
                                                                    <small>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . htmlspecialchars($attachment['archivo'])) ?>" 
                                                                           target="_blank" class="text-decoration-none">
                                                                            <?= htmlspecialchars(basename($attachment['archivo'])) ?>
                                                                        </a>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($attachment['contenido'])): ?>
                                                            <div class="col-12 mb-2">
                                                                <div class="p-2 bg-light rounded">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-comment me-1"></i>Comentario: 
                                                                        <?= htmlspecialchars($attachment['contenido']) ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Show message if no image but has other attachments -->
                                        <?php if (!$primaryImage && !empty($task['initial_attachments'])): ?>
                                            <div class="mb-3">
                                                <h6 class="text-muted mb-2">
                                                    <i class="fas fa-paperclip me-1"></i>Archivos adjuntos:
                                                </h6>
                                                <div class="row">
                                                    <?php foreach ($task['initial_attachments'] as $attachment): ?>
                                                        <?php if (!empty($attachment['archivo'])): ?>
                                                            <div class="col-md-6 mb-2">
                                                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                    <?php 
                                                                    $iconClass = 'fas fa-file';
                                                                    switch ($attachment['tipo_evidencia']) {
                                                                        case 'foto':
                                                                            $iconClass = 'fas fa-image text-primary';
                                                                            break;
                                                                        case 'video':
                                                                            $iconClass = 'fas fa-video text-danger';
                                                                            break;
                                                                        case 'audio':
                                                                            $iconClass = 'fas fa-music text-success';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <i class="<?= $iconClass ?> me-2"></i>
                                                                    <small>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . htmlspecialchars($attachment['archivo'])) ?>" 
                                                                           target="_blank" class="text-decoration-none">
                                                                            <?= htmlspecialchars(basename($attachment['archivo'])) ?>
                                                                        </a>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($attachment['contenido'])): ?>
                                                            <div class="col-12 mb-2">
                                                                <div class="p-2 bg-light rounded">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-comment me-1"></i>Comentario inicial: 
                                                                        <?= htmlspecialchars($attachment['contenido']) ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
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
                                            <?php if (!empty($task['fecha_cierre'])): ?>
                                                <div class="mb-1">
                                                    <i class="fas fa-clock text-danger me-1"></i>
                                                    <strong>Vigencia:</strong> 
                                                    <?= date('d/m/Y', strtotime($task['fecha_cierre'])) ?>
                                                    <?php if (!empty($task['hora_cierre'])): ?>
                                                        <?= date('H:i', strtotime($task['hora_cierre'])) ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($urgencyText)): ?>
                                                        <span class="text-<?= $isUrgent ? 'danger' : 'warning' ?> fw-bold">
                                                            (<?= $urgencyText ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-1">
                                                <i class="fas fa-clock text-warning me-1"></i>
                                                <strong>Asignada:</strong> 
                                                <?= date('d/m/Y H:i', strtotime($task['fecha_creacion'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <!-- Added VER DETALLE button as requested for all user levels -->
                                        <div class="row g-2">
                                            <div class="col">
                                                <div class="d-grid">
                                                    <a href="<?= url('activities/detail.php?id=' . $task['id']) ?>" 
                                                       class="btn btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>Ver Detalle
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="d-grid">
                                                    <a href="<?= url('tasks/complete.php?id=' . $task['id']) ?>" 
                                                       class="btn btn-success">
                                                        <i class="fas fa-upload me-1"></i>Completar Tarea
                                                    </a>
                                                </div>
                                            </div>
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