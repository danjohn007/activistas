<?php
/**
 * Dashboard Líder - Sistema de Activistas Digitales
 * Página principal para usuarios con rol de Líder
 */

// Configurar manejo de errores para producción
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Cargar dependencias
    require_once __DIR__ . '/../../controllers/dashboardController.php';
    
    // Asegurar que la sesión esté iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar que el usuario esté logueado antes de continuar
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . (function_exists('url') ? url('login.php') : '../login.php'));
        exit();
    }
    
    // Registrar acceso al dashboard
    if (function_exists('logActivity')) {
        logActivity("Usuario {$_SESSION['user_id']} accedió al dashboard de líder", 'INFO');
    }
    
    // Inicializar el controlador y ejecutar el dashboard
    $controller = new DashboardController();
    $controller->liderDashboard();
    
    // Inicializar variables por defecto para evitar errores undefined
    $teamStats = $GLOBALS['teamStats'] ?? [];
    $teamMembers = $GLOBALS['teamMembers'] ?? [];
    $memberMetrics = $GLOBALS['memberMetrics'] ?? [];
    $recentActivities = $GLOBALS['recentActivities'] ?? [];
    
    // Registrar éxito en la carga
    if (function_exists('logActivity')) {
        logActivity("Dashboard de líder cargado exitosamente para usuario {$_SESSION['user_id']}", 'INFO');
    }
    
} catch (Exception $e) {
    // Registrar el error detalladamente
    $userId = $_SESSION['user_id'] ?? 'desconocido';
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    
    error_log("Error crítico en lider.php - Usuario: $userId, Error: $errorMsg en $errorFile:$errorLine");
    
    if (function_exists('logDashboardError')) {
        logDashboardError('lider', $userId, "$errorMsg en $errorFile:$errorLine");
    }
    
    // Inicializar variables vacías para evitar errores
    $teamStats = [];
    $teamMembers = [];
    $memberMetrics = [];
    $recentActivities = [];
    
    // Verificar si aún podemos mostrar la página
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . (function_exists('url') ? url('login.php') : '../login.php'));
        exit();
    }
    
    // Mostrar mensaje de error al usuario
    $_SESSION['flash_message'] = 'Ha ocurrido un error al cargar el dashboard. Los datos pueden estar incompletos.';
    $_SESSION['flash_type'] = 'warning';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Líder - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #fd7e14 0%, #f8b500 100%);
        }
        .card-stats {
            transition: transform 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .metric-card {
            background: linear-gradient(135deg, #fd7e14 0%, #f8b500 100%);
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
                        <h4><i class="fas fa-users-cog me-2"></i>Líder</h4>
                        <small><?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuario' ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= function_exists('url') ? url('dashboards/lider.php') : 'lider.php' ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= function_exists('url') ? url('activities/') : '../activities/' ?>">
                                <i class="fas fa-tasks me-2"></i>Actividades del Equipo
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= function_exists('url') ? url('activities/create.php') : '../activities/create.php' ?>">
                                <i class="fas fa-plus me-2"></i>Nueva Actividad
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= function_exists('url') ? url('tasks/') : '../tasks/' ?>">
                                <i class="fas fa-clipboard-list me-2"></i>Tareas
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= function_exists('url') ? url('profile.php') : '../profile.php' ?>">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= function_exists('url') ? url('logout.php') : '../logout.php' ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Líder</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= function_exists('url') ? url('activities/create.php') : '../activities/create.php' ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Nueva Actividad
                            </a>
                        </div>
                    </div>
                </div>

                <?php $flash = function_exists('getFlashMessage') ? getFlashMessage() : null; ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Métricas del equipo -->
                <div class="row mb-4">
                    <?php 
                    $totalTeamActivities = isset($teamStats['total_actividades']) ? $teamStats['total_actividades'] : 0;
                    $completedTeamActivities = isset($teamStats['completadas']) ? $teamStats['completadas'] : 0;
                    $teamReach = isset($teamStats['alcance_total']) ? $teamStats['alcance_total'] : 0;
                    $teamSize = is_array($teamMembers) ? count($teamMembers) : 0;
                    ?>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Miembros del Equipo</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($teamSize) ?></span>
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
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Actividades del Equipo</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($totalTeamActivities) ?></span>
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
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($completedTeamActivities) ?></span>
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
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Alcance Total</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($teamReach) ?></span>
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
                </div>

                <div class="row mb-4">
                    <!-- Métricas por miembro del equipo -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Rendimiento del Equipo</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($memberMetrics) && is_array($memberMetrics)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Miembro</th>
                                                    <th>Actividades</th>
                                                    <th>Completadas</th>
                                                    <th>Evidencias</th>
                                                    <th>Alcance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($memberMetrics as $member): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($member['nombre_completo'] ?? 'Sin nombre') ?></td>
                                                        <td><?= number_format($member['total_actividades'] ?? 0) ?></td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <?= number_format($member['completadas'] ?? 0) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= number_format($member['evidencias'] ?? 0) ?></td>
                                                        <td><?= number_format($member['alcance_total'] ?? 0) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No hay métricas disponibles para el equipo</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Miembros del equipo -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mi Equipo</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($teamMembers) && is_array($teamMembers)): ?>
                                    <?php foreach ($teamMembers as $member): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($member['foto_perfil'])): ?>
                                                    <img src="<?= function_exists('url') ? url('assets/uploads/profiles/' . htmlspecialchars($member['foto_perfil'])) : htmlspecialchars($member['foto_perfil']) ?>" 
                                                         class="rounded-circle" width="50" height="50" alt="Foto">
                                                <?php else: ?>
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 50px; height: 50px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?= htmlspecialchars($member['nombre_completo'] ?? 'Sin nombre') ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($member['email'] ?? 'Sin email') ?></small>
                                                <br>
                                                <small class="text-muted">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($member['rol'] ?? 'Sin rol') ?></span>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No tienes miembros asignados a tu equipo</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividades recientes del equipo -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actividades Recientes del Equipo</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentActivities) && is_array($recentActivities)): ?>
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
                                                        <td><?= htmlspecialchars($activity['titulo'] ?? 'Sin título') ?></td>
                                                        <td><?= htmlspecialchars($activity['usuario_nombre'] ?? 'Sin responsable') ?></td>
                                                        <td><?= htmlspecialchars($activity['tipo_nombre'] ?? 'Sin tipo') ?></td>
                                                        <td><?= function_exists('formatDate') && !empty($activity['fecha_actividad']) ? formatDate($activity['fecha_actividad'], 'd/m/Y') : 'Sin fecha' ?></td>
                                                        <td>
                                                            <?php 
                                                            $estado = $activity['estado'] ?? 'planificada';
                                                            $badgeClass = $estado === 'completada' ? 'success' : ($estado === 'en_progreso' ? 'warning' : 'primary');
                                                            ?>
                                                            <span class="badge bg-<?= $badgeClass ?>">
                                                                <?= ucfirst(str_replace('_', ' ', $estado)) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= number_format($activity['alcance_estimado'] ?? 0) ?></td>
                                                        <td>
                                                            <a href="<?= function_exists('url') ? url('activities/detail.php?id=' . ($activity['id'] ?? '')) : '../activities/detail.php?id=' . ($activity['id'] ?? '') ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                Ver
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="<?= function_exists('url') ? url('activities/') : '../activities/' ?>" class="btn btn-primary">Ver todas las actividades</a>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No hay actividades registradas</h5>
                                        <p class="text-muted">Tu equipo aún no ha registrado actividades</p>
                                        <a href="<?= function_exists('url') ? url('activities/create.php') : '../activities/create.php' ?>" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Crear Primera Actividad
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liderazgo efectivo -->
                <div class="alert alert-warning">
                    <h5><i class="fas fa-lightbulb me-2"></i>Liderazgo Efectivo</h5>
                    <p class="mb-0">
                        Como líder, tu rol es motivar y coordinar a tu equipo. Asegúrate de revisar regularmente el progreso 
                        de las actividades y brindar apoyo cuando sea necesario.
                    </p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>