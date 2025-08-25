<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuestas de Actividades - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .proposal-card {
            transition: transform 0.2s;
        }
        .proposal-card:hover {
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
            renderSidebar('proposals'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Propuestas de Actividades</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver a actividades
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

                <!-- Información -->
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i>Gestión de Propuestas</h5>
                    <p class="mb-0">
                        Aquí puedes revisar las propuestas de actividades enviadas por los activistas. 
                        Las propuestas aprobadas se convertirán en tareas normales y otorgarán 100 puntos extra al completarse.
                    </p>
                </div>

                <?php if (empty($proposals)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay propuestas pendientes</h5>
                        <p class="text-muted">Todas las propuestas han sido procesadas o no hay propuestas nuevas.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($proposals as $proposal): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card proposal-card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="card-title mb-0"><?= htmlspecialchars($proposal['tipo_nombre']) ?></h6>
                                        <span class="badge bg-warning">Pendiente</span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($proposal['titulo']) ?></h5>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($proposal['usuario_nombre']) ?>
                                        </small><br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Propuesta para: <?= formatDate($proposal['fecha_actividad']) ?>
                                        </small><br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Enviada: <?= formatDate($proposal['fecha_creacion']) ?>
                                        </small>
                                    </div>
                                    
                                    <p class="card-text flex-grow-1">
                                        <?= htmlspecialchars(substr($proposal['descripcion'], 0, 150)) ?>
                                        <?= strlen($proposal['descripcion']) > 150 ? '...' : '' ?>
                                    </p>
                                    
                                    <!-- Descripción completa (colapsable) -->
                                    <?php if (strlen($proposal['descripcion']) > 150): ?>
                                    <div class="collapse" id="desc<?= $proposal['id'] ?>">
                                        <div class="card card-body mb-3 bg-light">
                                            <strong>Descripción completa:</strong><br>
                                            <?= nl2br(htmlspecialchars($proposal['descripcion'])) ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-link btn-sm p-0 mb-3" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#desc<?= $proposal['id'] ?>">
                                        Ver descripción completa
                                    </button>
                                    <?php endif; ?>
                                    
                                    <div class="mt-auto">
                                        <div class="row">
                                            <div class="col-6">
                                                <form method="POST" action="<?= url('activities/process_proposal.php') ?>" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="proposal_id" value="<?= $proposal['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm w-100"
                                                            onclick="return confirm('¿Confirmas que quieres aprobar esta propuesta?')">
                                                        <i class="fas fa-check me-1"></i>Aprobar
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="col-6">
                                                <form method="POST" action="<?= url('activities/process_proposal.php') ?>" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="proposal_id" value="<?= $proposal['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger btn-sm w-100"
                                                            onclick="return confirm('¿Confirmas que quieres rechazar esta propuesta?')">
                                                        <i class="fas fa-times me-1"></i>Rechazar
                                                    </button>
                                                </form>
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