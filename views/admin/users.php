<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Activistas Digitales</title>
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
                            <a class="nav-link text-white active" href="<?= url('admin/users.php') ?>">
                                <i class="fas fa-users me-2"></i>Gestión de Usuarios
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('admin/pending_users.php') ?>">
                                <i class="fas fa-user-clock me-2"></i>Usuarios Pendientes
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
                    <h1 class="h2">Gestión de Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('admin/export_users.php?' . http_build_query($_GET)) ?>" 
                               class="btn btn-sm btn-outline-secondary" title="Exportar a Excel">
                                <i class="fas fa-download me-1"></i>Exportar
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

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                                       placeholder="Nombre, correo, teléfono, título actividad...">
                            </div>
                            <div class="col-md-2">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="">Todos los roles</option>
                                    <option value="SuperAdmin" <?= ($_GET['rol'] ?? '') === 'SuperAdmin' ? 'selected' : '' ?>>SuperAdmin</option>
                                    <option value="Gestor" <?= ($_GET['rol'] ?? '') === 'Gestor' ? 'selected' : '' ?>>Gestor</option>
                                    <option value="Líder" <?= ($_GET['rol'] ?? '') === 'Líder' ? 'selected' : '' ?>>Líder</option>
                                    <option value="Activista" <?= ($_GET['rol'] ?? '') === 'Activista' ? 'selected' : '' ?>>Activista</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="pendiente" <?= ($_GET['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="suspendido" <?= ($_GET['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                    <option value="desactivado" <?= ($_GET['estado'] ?? '') === 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="cumplimiento" class="form-label">Cumplimiento</label>
                                <select class="form-select" id="cumplimiento" name="cumplimiento">
                                    <option value="">Todos los niveles</option>
                                    <option value="alto" <?= ($_GET['cumplimiento'] ?? '') === 'alto' ? 'selected' : '' ?>>🟢 Alto (&gt;60%)</option>
                                    <option value="medio" <?= ($_GET['cumplimiento'] ?? '') === 'medio' ? 'selected' : '' ?>>🟡 Medio (20-60%)</option>
                                    <option value="bajo" <?= ($_GET['cumplimiento'] ?? '') === 'bajo' ? 'selected' : '' ?>>🔴 Bajo (&lt;20%)</option>
                                    <option value="sin_tareas" <?= ($_GET['cumplimiento'] ?? '') === 'sin_tareas' ? 'selected' : '' ?>>⚫ Sin tareas</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                                <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas -->
                <?php if (!empty($stats)): ?>
                <div class="row mb-4">
                    <?php foreach ($stats as $rol => $data): ?>
                    <div class="col-md-3">
                        <div class="card text-center card-stats">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($rol) ?></h5>
                                <h3 class="text-primary"><?= $data['total'] ?></h3>
                                <small class="text-muted">
                                    Activos: <?= $data['activos'] ?> | 
                                    Pendientes: <?= $data['pendientes'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Lista de usuarios -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lista de Usuarios</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No se encontraron usuarios</h5>
                                <p class="text-muted">No hay usuarios que coincidan con los filtros seleccionados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Cumplimiento</th>
                                            <th>Líder</th>
                                            <th>Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($user['foto_perfil'])): ?>
                                                        <img src="<?= url('assets/uploads/profiles/' . $user['foto_perfil']) ?>" 
                                                             class="rounded-circle me-2" width="32" height="32" alt="Foto">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($user['nombre_completo']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($user['rol']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'activo' => 'success',
                                                    'pendiente' => 'warning',
                                                    'suspendido' => 'danger',
                                                    'desactivado' => 'secondary'
                                                ][$user['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($user['estado']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $porcentaje = $user['porcentaje_cumplimiento'] ?? 0;
                                                $semaforo = '';
                                                $colorClass = '';
                                                $icono = '';
                                                
                                                if ($porcentaje == 0) {
                                                    $semaforo = '⚫';
                                                    $colorClass = 'secondary';
                                                    $icono = 'circle';
                                                } elseif ($porcentaje > 60) {
                                                    $semaforo = '🟢';
                                                    $colorClass = 'success';
                                                    $icono = 'check-circle';
                                                } elseif ($porcentaje >= 20) {
                                                    $semaforo = '🟡';
                                                    $colorClass = 'warning';
                                                    $icono = 'exclamation-triangle';
                                                } else {
                                                    $semaforo = '🔴';
                                                    $colorClass = 'danger';
                                                    $icono = 'times-circle';
                                                }
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-<?= $icono ?> text-<?= $colorClass ?> me-2"></i>
                                                    <span class="fw-bold text-<?= $colorClass ?>"><?= $porcentaje ?>%</span>
                                                    <small class="text-muted ms-2">(<?= $user['tareas_completadas'] ?? 0 ?>/<?= $user['total_tareas'] ?? 0 ?>)</small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['lider_nombre'] ?? 'N/A') ?></td>
                                            <td><?= formatDate($user['fecha_registro']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('admin/edit_user.php?id=' . $user['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($user['estado'] === 'activo'): ?>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="changeUserStatus(<?= $user['id'] ?>, 'suspendido')" title="Suspender">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    <?php elseif ($user['estado'] === 'suspendido'): ?>
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="changeUserStatus(<?= $user['id'] ?>, 'activo')" title="Activar">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['estado'] !== 'desactivado'): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="changeUserStatus(<?= $user['id'] ?>, 'desactivado')" title="Desactivar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Form para cambio de estado -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeUserStatus(userId, status) {
            if (confirm('¿Estás seguro de que quieres cambiar el estado de este usuario?')) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusValue').value = status;
                document.getElementById('statusForm').submit();
            }
        }
    </script>
</body>
</html>