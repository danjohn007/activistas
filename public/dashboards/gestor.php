<?php
require_once __DIR__ . '/../../controllers/dashboardController.php';

$controller = new DashboardController();
$controller->gestorDashboard();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gestor - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
        }
        .card-stats {
            transition: transform 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .metric-card {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
                        <h4><i class="fas fa-user-tie me-2"></i>Gestor</h4>
                        <small><?= htmlspecialchars($_SESSION['user_name']) ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('dashboards/gestor.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('admin/users.php') ?>">
                                <i class="fas fa-users me-2"></i>Gestión de Usuarios
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('admin/pending_users.php') ?>">
                                <i class="fas fa-user-clock me-2"></i>Usuarios Pendientes
                                <?php 
                                $pendingUsers = $GLOBALS['pendingUsers'] ?? [];
                                if (is_array($pendingUsers) && count($pendingUsers) > 0): ?>
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
                            <a class="nav-link text-white" href="<?= url('ranking/') ?>">
                                <i class="fas fa-trophy me-2"></i>Ranking General
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
                    <h1 class="h2">Dashboard Gestor</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>Exportar PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-excel me-1"></i>Exportar Excel
                            </button>
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

                <!-- Métricas principales -->
                <div class="row mb-4">
                    <?php 
                    $userStats = $GLOBALS['userStats'] ?? [];
                    $activityStats = $GLOBALS['activityStats'] ?? [];
                    $pendingUsers = $GLOBALS['pendingUsers'] ?? [];
                    
                    $totalUsers = is_array($userStats) ? array_sum(array_column($userStats, 'total')) : 0;
                    $totalActivities = isset($activityStats['total_actividades']) ? $activityStats['total_actividades'] : 0;
                    $completedActivities = isset($activityStats['completadas']) ? $activityStats['completadas'] : 0;
                    $totalReach = isset($activityStats['alcance_total']) ? $activityStats['alcance_total'] : 0;
                    
                    // Calcular porcentaje de atención (actividades completadas)
                    $attentionPercentage = $totalActivities > 0 ? round(($completedActivities / $totalActivities) * 100, 1) : 0;
                    ?>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Total Usuarios</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($totalUsers) ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon text-white-50">
                                            <i class="fas fa-users fa-2x"></i>
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
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Actividades</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($totalActivities) ?></span>
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
                        <div class="card bg-info text-white card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Completadas</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($completedActivities) ?></span>
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
                        <div class="card bg-warning text-white card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">% Atención</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $attentionPercentage ?>%</span>
                                        <small class="text-white-50"><?= number_format($completedActivities) ?>/<?= number_format($totalActivities) ?> completadas</small>
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
                    <!-- Ranking de equipos -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Ranking de Equipos</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $teamRanking = $GLOBALS['teamRanking'] ?? [];
                                if (!empty($teamRanking)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Posición</th>
                                                    <th>Líder</th>
                                                    <th>Miembros</th>
                                                    <th>Total Actividades</th>
                                                    <th>Completadas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teamRanking as $index => $team): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-primary"><?= $index + 1 ?></span>
                                                        </td>
                                                        <td><?= htmlspecialchars($team['lider_nombre']) ?></td>
                                                        <td><?= number_format($team['miembros_equipo']) ?></td>
                                                        <td><?= number_format($team['total_actividades']) ?></td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <?= number_format($team['completadas']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No hay equipos para mostrar</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usuarios pendientes -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Usuarios Pendientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($pendingUsers)): ?>
                                    <?php foreach (array_slice($pendingUsers, 0, 5) as $user): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?= htmlspecialchars($user['nombre_completo']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                <br>
                                                <small class="text-muted">
                                                    <span class="badge bg-warning">Pendiente</span>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($pendingUsers) > 5): ?>
                                        <a href="<?= url('admin/pending_users.php') ?>" class="btn btn-sm btn-outline-primary">
                                            Ver todos (<?= count($pendingUsers) ?>)
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">No hay usuarios pendientes</p>
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
                                <h5 class="card-title mb-0">Actividades Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $recentActivities = $GLOBALS['recentActivities'] ?? [];
                                if (!empty($recentActivities)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Actividad</th>
                                                    <th>Responsable</th>
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
                                                        <td><?= htmlspecialchars($activity['usuario_nombre']) ?></td>
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
                                    <a href="<?= url('activities/') ?>" class="btn btn-primary">Ver todas las actividades</a>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No hay actividades registradas</h5>
                                        <p class="text-muted">El sistema aún no tiene actividades registradas</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestión efectiva -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-chart-bar me-2"></i>Gestión Efectiva</h5>
                    <p class="mb-0">
                        Como gestor, tienes acceso a herramientas para administrar usuarios y supervisar el progreso general 
                        del sistema. Mantén un seguimiento regular de las métricas clave.
                    </p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>