<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Pendientes - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-stats {
            transition: transform 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <h4><i class="fas fa-users me-2"></i>Activistas</h4>
                        <small>SuperAdmin</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/admin.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('admin/users.php') ?>">
                                <i class="fas fa-users me-2"></i>Gestión de Usuarios
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('admin/pending_users.php') ?>">
                                <i class="fas fa-user-clock me-2"></i>Usuarios Pendientes
                                <?php if (!empty($pendingUsers)): ?>
                                    <span class="badge bg-warning text-dark"><?= count($pendingUsers) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('profile.php') ?>">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= url('logout.php') ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Usuarios Pendientes de Aprobación</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-warning text-dark fs-6 py-2">
                                <?= count($pendingUsers ?? []) ?> pendientes
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

                <!-- Lista de usuarios pendientes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Solicitudes de Registro</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingUsers)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">¡Excelente!</h5>
                                <p class="text-muted">No hay usuarios pendientes de aprobación en este momento.</p>
                                <a href="<?= url('admin/users.php') ?>" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i>Ver todos los usuarios
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($pendingUsers as $user): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 border-warning">
                                        <div class="card-header bg-warning bg-opacity-10">
                                            <h6 class="card-title mb-0 text-warning">
                                                <i class="fas fa-clock me-2"></i>Pendiente de Aprobación
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <?php if (!empty($user['foto_perfil'])): ?>
                                                    <img src="<?= url('assets/uploads/profiles/' . $user['foto_perfil']) ?>" 
                                                         class="rounded-circle" width="80" height="80" alt="Foto de perfil">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                                         style="width: 80px; height: 80px;">
                                                        <i class="fas fa-user fa-2x text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <h6 class="card-title text-center"><?= htmlspecialchars($user['nombre_completo']) ?></h6>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?= htmlspecialchars($user['email']) ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?= htmlspecialchars($user['telefono']) ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars($user['direccion']) ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <span class="badge bg-info"><?= htmlspecialchars($user['rol']) ?></span>
                                                <?php if (!empty($user['lider_nombre'])): ?>
                                                    <br><small class="text-muted">
                                                        Líder: <?= htmlspecialchars($user['lider_nombre']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Registrado: <?= formatDate($user['fecha_registro']) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-success btn-sm" 
                                                        onclick="processUser(<?= $user['id'] ?>, 'approve')">
                                                    <i class="fas fa-check me-1"></i>Aprobar
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="processUser(<?= $user['id'] ?>, 'reject')">
                                                    <i class="fas fa-times me-1"></i>Rechazar
                                                </button>
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

    <!-- Form para procesamiento de usuarios -->
    <form id="processForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="user_id" id="processUserId">
        <input type="hidden" name="action" id="processAction">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processUser(userId, action) {
            const actionText = action === 'approve' ? 'aprobar' : 'rechazar';
            if (confirm(`¿Estás seguro de que quieres ${actionText} este usuario?`)) {
                document.getElementById('processUserId').value = userId;
                document.getElementById('processAction').value = action;
                document.getElementById('processForm').submit();
            }
        }
    </script>
</body>
</html>