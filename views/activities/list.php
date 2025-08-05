<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Actividades - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-activity {
            transition: transform 0.2s;
        }
        .card-activity:hover {
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
                        <h4><i class="fas fa-tasks me-2"></i>Actividades</h4>
                        <small><?= htmlspecialchars($currentUser['rol']) ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/' . strtolower($currentUser['rol']) . '.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Mis Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/create.php') ?>">
                                <i class="fas fa-plus me-2"></i>Nueva Actividad
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
                    <h1 class="h2">Mis Actividades</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Nueva Actividad
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
                                <label for="tipo" class="form-label">Tipo de Actividad</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach ($activityTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>" <?= ($_GET['tipo'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="programada" <?= ($_GET['estado'] ?? '') === 'programada' ? 'selected' : '' ?>>Programada</option>
                                    <option value="en_progreso" <?= ($_GET['estado'] ?? '') === 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                    <option value="completada" <?= ($_GET['estado'] ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                                    <option value="cancelada" <?= ($_GET['estado'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                                <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de actividades -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lista de Actividades</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes actividades registradas</h5>
                                <p class="text-muted">Comienza registrando tu primera actividad.</p>
                                <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Crear Primera Actividad
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Alcance</th>
                                            <th>Lugar</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($activity['titulo']) ?></strong>
                                                <?php if (!empty($activity['descripcion'])): ?>
                                                    <br><small class="text-muted">
                                                        <?= htmlspecialchars(substr($activity['descripcion'], 0, 100)) ?>
                                                        <?= strlen($activity['descripcion']) > 100 ? '...' : '' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($activity['tipo_nombre']) ?></td>
                                            <td><?= formatDate($activity['fecha_actividad'], 'd/m/Y') ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'completada' => 'success',
                                                    'en_progreso' => 'warning',
                                                    'programada' => 'primary',
                                                    'cancelada' => 'danger'
                                                ][$activity['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $activity['estado'])) ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($activity['alcance_estimado']) ?></td>
                                            <td><?= htmlspecialchars($activity['lugar'] ?? 'N/A') ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= url('activities/edit.php?id=' . $activity['id']) ?>" 
                                                       class="btn btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>