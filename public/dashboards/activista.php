<?php
require_once __DIR__ . '/../../controllers/dashboardController.php';

$controller = new DashboardController();
$controller->activistaDashboard();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Activista - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .card-stats {
            transition: transform 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .metric-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
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
                        <h4><i class="fas fa-user me-2"></i>Activista</h4>
                        <small><?= htmlspecialchars($_SESSION['user_name']) ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('dashboards/activista.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Mis Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/create.php') ?>">
                                <i class="fas fa-plus me-2"></i>Nueva Actividad
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('tasks/') ?>">
                                <i class="fas fa-clipboard-list me-2"></i>Tareas
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
                    <h1 class="h2">Mi Dashboard</h1>
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

                <!-- Métricas personales -->
                <div class="row mb-4">
                    <?php 
                    $totalMyActivities = $myStats['total_actividades'] ?? 0;
                    $completedMyActivities = $myStats['completadas'] ?? 0;
                    $myReach = $myStats['alcance_total'] ?? 0;
                    $completionRate = $totalMyActivities > 0 ? round(($completedMyActivities / $totalMyActivities) * 100) : 0;
                    ?>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Mis Actividades</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($totalMyActivities) ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon text-white-50">
                                            <i class="fas fa-tasks fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-success text-white card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Completadas</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($completedMyActivities) ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon text-white-50">
                                            <i class="fas fa-check fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-info text-white card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Mi Alcance</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($myReach) ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon text-white-50">
                                            <i class="fas fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-warning text-white card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">% Completado</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $completionRate ?>%</span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon text-white-50">
                                            <i class="fas fa-percentage fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Información del líder -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mi Líder</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($lider): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($lider['foto_perfil']): ?>
                                                <img src="<?= url('assets/uploads/profiles/' . htmlspecialchars($lider['foto_perfil'])) ?>" 
                                                     class="rounded-circle" width="60" height="60" alt="Foto líder">
                                            <?php else: ?>
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-user text-white fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0"><?= htmlspecialchars($lider['nombre_completo']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($lider['email']) ?></small>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($lider['telefono']) ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No tienes líder asignado</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Compañeros de equipo -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mi Equipo</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($teammates)): ?>
                                    <?php foreach (array_slice($teammates, 0, 5) as $teammate): ?>
                                        <?php if ($teammate['id'] != $_SESSION['user_id']): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="flex-shrink-0">
                                                    <?php if ($teammate['foto_perfil']): ?>
                                                        <img src="<?= url('assets/uploads/profiles/' . htmlspecialchars($teammate['foto_perfil'])) ?>" 
                                                             class="rounded-circle" width="40" height="40" alt="Foto">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0"><?= htmlspecialchars($teammate['nombre_completo']) ?></h6>
                                                    <small class="text-muted">Activista</small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No hay otros miembros en tu equipo</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividades recientes -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis Actividades Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentActivities)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Actividad</th>
                                                    <th>Tipo</th>
                                                    <th>Fecha</th>
                                                    <th>Estado</th>
                                                    <th>Alcance</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($recentActivities, 0, 10) as $activity): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($activity['titulo']) ?></td>
                                                        <td><?= htmlspecialchars($activity['tipo_nombre']) ?></td>
                                                        <td><?= formatDate($activity['fecha_actividad'], 'd/m/Y') ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $activity['estado'] === 'completada' ? 'success' : ($activity['estado'] === 'en_progreso' ? 'warning' : 'primary') ?>">
                                                                <?= ucfirst($activity['estado']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= number_format($activity['alcance_estimado']) ?></td>
                                                        <td>
                                                            <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                Ver
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="<?= url('activities/') ?>" class="btn btn-primary">Ver todas mis actividades</a>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No tienes actividades registradas</h5>
                                        <p class="text-muted">Comienza registrando tu primera actividad</p>
                                        <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Crear Primera Actividad
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Motivación y sistema funcionando -->
                <div class="alert alert-success">
                    <h5><i class="fas fa-star me-2"></i>¡Excelente trabajo!</h5>
                    <p class="mb-0">
                        Estás contribuyendo al movimiento de activistas digitales. Cada actividad que registres suma al impacto colectivo.
                        El sistema está completamente funcional y listo para usar.
                    </p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>