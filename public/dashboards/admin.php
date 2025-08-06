<?php
// Habilitar reporte de errores para debugging
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/../../config/app.php';
}

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

// Incluir funciones básicas primero para tener funciones como getFlashMessage disponibles
if (file_exists(__DIR__ . '/../../includes/functions.php')) {
    require_once __DIR__ . '/../../includes/functions.php';
}

// Fallback para funciones críticas si functions.php no se pudo cargar
if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $base_url = 'https://fix360.app/ad/public';
        $path = ltrim($path, '/');
        return $base_url . ($path ? '/' . $path : '');
    }
}

// Variables para el dashboard con valores por defecto
$userStats = [];
$activityStats = [];
$recentActivities = [];
$activitiesByType = [];
$pendingUsers = [];
$monthlyActivities = [];
$teamRanking = [];
$error_message = null;

try {
    // Verificar que los archivos requeridos existen
    $required_files = [
        __DIR__ . '/../../controllers/dashboardController.php',
        __DIR__ . '/../../includes/auth.php',
        __DIR__ . '/../../models/user.php',
        __DIR__ . '/../../models/activity.php',
        __DIR__ . '/../../config/database.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Archivo requerido no encontrado: " . basename($file));
        }
    }
    
    // Incluir el controlador
    require_once __DIR__ . '/../../controllers/dashboardController.php';
    
    // Verificar que la clase existe
    if (!class_exists('DashboardController')) {
        throw new Exception("La clase DashboardController no fue encontrada");
    }
    
    // Crear instancia del controlador con manejo de errores
    $controller = new DashboardController();
    
    // Verificar que el método existe
    if (!method_exists($controller, 'adminDashboard')) {
        throw new Exception("El método adminDashboard no existe en DashboardController");
    }
    
    // Llamar al método del dashboard
    $controller->adminDashboard();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    
    // Log del error
    if (function_exists('logActivity')) {
        logActivity("Error en admin dashboard: " . $error_message, 'ERROR');
    } else {
        error_log("Admin Dashboard Error: " . $error_message);
    }
    
    // En desarrollo, mostrar detalles del error
    if (APP_ENV === 'development') {
        $error_details = [
            'mensaje' => $error_message,
            'archivo' => $e->getFile(),
            'linea' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    } else {
        $error_details = ['mensaje' => 'Error interno del sistema. Contacte al administrador.'];
    }
} catch (Error $e) {
    $error_message = "Error fatal: " . $e->getMessage();
    
    error_log("Admin Dashboard Fatal Error: " . $error_message);
    
    if (APP_ENV === 'development') {
        $error_details = [
            'mensaje' => $error_message,
            'archivo' => $e->getFile(),
            'linea' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    } else {
        $error_details = ['mensaje' => 'Error crítico del sistema. Contacte al administrador.'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SuperAdmin - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                        <h4><i class="fas fa-users me-2"></i>Activistas</h4>
                        <small>SuperAdmin</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('dashboards/admin.php') ?>">
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
                                if (!$error_message && is_array($pendingUsers) && count($pendingUsers) > 0): ?>
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
                    <h1 class="h2">Dashboard SuperAdmin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-success" id="refreshData" title="Actualizar datos">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar Datos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>Exportar PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-excel me-1"></i>Exportar Excel
                            </button>
                        </div>
                        <div class="text-muted small">
                            <span id="lastUpdate">Última actualización: <?= date('H:i:s') ?></span>
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

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Error del Sistema</h5>
                        <p><strong>Descripción:</strong> <?= htmlspecialchars($error_details['mensaje']) ?></p>
                        
                        <?php if (APP_ENV === 'development' && isset($error_details['archivo'])): ?>
                            <hr>
                            <h6>Información de Debugging:</h6>
                            <p><strong>Archivo:</strong> <?= htmlspecialchars($error_details['archivo']) ?></p>
                            <p><strong>Línea:</strong> <?= htmlspecialchars($error_details['linea']) ?></p>
                            <details>
                                <summary>Stack Trace</summary>
                                <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;"><?= htmlspecialchars($error_details['trace']) ?></pre>
                            </details>
                        <?php endif; ?>
                        
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Métricas principales -->
                <div class="row mb-4">
                    <?php if (!$error_message): ?>
                        <?php 
                        $userStats = $GLOBALS['userStats'] ?? [];
                        $activityStats = $GLOBALS['activityStats'] ?? [];
                        $pendingUsers = $GLOBALS['pendingUsers'] ?? [];
                        
                        $totalUsers = is_array($userStats) ? array_sum(array_column($userStats, 'total')) : 0;
                        $totalActivities = isset($activityStats['total_actividades']) ? $activityStats['total_actividades'] : 0;
                        $completedActivities = isset($activityStats['completadas']) ? $activityStats['completadas'] : 0;
                        $totalReach = isset($activityStats['alcance_total']) ? $activityStats['alcance_total'] : 0;
                        ?>
                    <?php else: ?>
                        <?php 
                        // Valores por defecto cuando hay error
                        $totalUsers = 0;
                        $totalActivities = 0;
                        $completedActivities = 0;
                        $totalReach = 0;
                        ?>
                    <?php endif; ?>
                    
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
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">Alcance Total</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= number_format($totalReach) ?></span>
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

                <!-- Gráficas y estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actividades por Tipo</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="activitiesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Usuarios por Rol</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="usersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nuevas gráficas informativas -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actividades por Mes (Últimos 12 meses)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Ranking de Equipos</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="teamRankingChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Listados informativos sugeridos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-clock me-2"></i>Usuarios Pendientes de Aprobación
                                    <?php 
                                    $pendingUsers = $GLOBALS['pendingUsers'] ?? [];
                                    if (!$error_message && is_array($pendingUsers) && count($pendingUsers) > 0): ?>
                                        <span class="badge bg-warning text-dark"><?= count($pendingUsers) ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$error_message && is_array($pendingUsers) && count($pendingUsers) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach (array_slice($pendingUsers, 0, 5) as $user): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($user['nombre_completo']) ?></h6>
                                                    <p class="mb-1 text-muted small"><?= htmlspecialchars($user['email']) ?></p>
                                                    <small class="text-muted">
                                                        Rol: <?= htmlspecialchars($user['rol']) ?> | 
                                                        Registro: <?= date('d/m/Y', strtotime($user['fecha_registro'])) ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success btn-sm" onclick="approveUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="rejectUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($pendingUsers) > 5): ?>
                                        <div class="text-center mt-3">
                                            <a href="<?= url('admin/pending_users.php') ?>" class="btn btn-outline-primary btn-sm">
                                                Ver todos (<?= count($pendingUsers) ?>)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <p class="mb-0">No hay usuarios pendientes de aprobación</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Últimas Actividades Recientes
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $recentActivities = $GLOBALS['recentActivities'] ?? [];
                                if (!$error_message && is_array($recentActivities) && count($recentActivities) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($activity['titulo']) ?></h6>
                                                    <small class="badge bg-<?= $activity['estado'] === 'completada' ? 'success' : ($activity['estado'] === 'en_progreso' ? 'warning' : 'secondary') ?>">
                                                        <?= htmlspecialchars($activity['estado']) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1 text-muted"><?= htmlspecialchars($activity['tipo_nombre'] ?? 'Sin tipo') ?></p>
                                                <small class="text-muted">
                                                    Por: <?= htmlspecialchars($activity['usuario_nombre']) ?> | 
                                                    <?= date('d/m/Y', strtotime($activity['fecha_actividad'])) ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="<?= url('activities/') ?>" class="btn btn-outline-primary btn-sm">
                                            Ver todas las actividades
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                        <p class="mb-0">No hay actividades recientes</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sistema funcional básico implementado -->
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Sistema Implementado</h5>
                    <p class="mb-0">
                        El sistema básico de activistas digitales está implementado con todas las funcionalidades core:
                        autenticación, gestión de usuarios, dashboards diferenciados, registro de actividades y seguridad.
                    </p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Obtener datos reales de la base de datos
        <?php
        $activitiesByType = $GLOBALS['activitiesByType'] ?? [];
        $userStats = $GLOBALS['userStats'] ?? [];
        $monthlyActivities = $GLOBALS['monthlyActivities'] ?? [];
        $teamRanking = $GLOBALS['teamRanking'] ?? [];
        
        // Preparar datos para gráfica de actividades por tipo
        $activityLabels = [];
        $activityData = [];
        foreach ($activitiesByType as $activity) {
            $activityLabels[] = $activity['nombre'];
            $activityData[] = (int)$activity['cantidad'];
        }
        
        // Preparar datos para gráfica de usuarios por rol
        $userLabels = [];
        $userData = [];
        foreach ($userStats as $rol => $stats) {
            $userLabels[] = $rol;
            $userData[] = (int)$stats['total'];
        }
        
        // Preparar datos para gráfica de actividades mensuales
        $monthlyLabels = [];
        $monthlyData = [];
        foreach ($monthlyActivities as $month) {
            $monthlyLabels[] = date('M Y', strtotime($month['mes'] . '-01'));
            $monthlyData[] = (int)$month['cantidad'];
        }
        
        // Preparar datos para gráfica de ranking de equipos
        $teamLabels = [];
        $teamData = [];
        foreach (array_slice($teamRanking, 0, 8) as $team) {
            $teamLabels[] = substr($team['lider_nombre'], 0, 15) . (strlen($team['lider_nombre']) > 15 ? '...' : '');
            $teamData[] = (int)$team['completadas'];
        }
        
        // Si no hay datos, usar valores por defecto para evitar gráficas vacías
        if (empty($activityLabels)) {
            $activityLabels = ['Sin datos'];
            $activityData = [0];
        }
        if (empty($userLabels)) {
            $userLabels = ['Sin datos'];
            $userData = [0];
        }
        if (empty($monthlyLabels)) {
            $monthlyLabels = ['Sin datos'];
            $monthlyData = [0];
        }
        if (empty($teamLabels)) {
            $teamLabels = ['Sin datos'];
            $teamData = [0];
        }
        ?>
        
        // Datos reales para las gráficas desde la base de datos
        const activitiesCtx = document.getElementById('activitiesChart').getContext('2d');
        activitiesChart = new Chart(activitiesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($activityLabels) ?>,
                datasets: [{
                    label: 'Cantidad',
                    data: <?= json_encode($activityData) ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.6)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Actividades por Tipo (Datos Reales)'
                    }
                }
            }
        });

        const usersCtx = document.getElementById('usersChart').getContext('2d');
        usersChart = new Chart(usersCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($userLabels) ?>,
                datasets: [{
                    data: <?= json_encode($userData) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 205, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Usuarios por Rol (Datos Reales)'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfica de actividades mensuales
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlyLabels) ?>,
                datasets: [{
                    label: 'Actividades',
                    data: <?= json_encode($monthlyData) ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Tendencia de Actividades Mensuales'
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gráfica de ranking de equipos
        const teamRankingCtx = document.getElementById('teamRankingChart').getContext('2d');
        const teamRankingChart = new Chart(teamRankingCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($teamLabels) ?>,
                datasets: [{
                    label: 'Actividades Completadas',
                    data: <?= json_encode($teamData) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 205, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(199, 199, 199, 0.6)',
                        'rgba(83, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)',
                        'rgba(83, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Top Equipos por Actividades Completadas'
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Función para actualizar gráficas en tiempo real
        function updateCharts() {
            const refreshButton = document.getElementById('refreshData');
            const lastUpdateSpan = document.getElementById('lastUpdate');
            
            // Mostrar indicador de carga
            refreshButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...';
            refreshButton.disabled = true;
            
            fetch('<?= url('api/stats.php') ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Actualizar gráfica de actividades por tipo
                        if (data.data.activities_by_type && data.data.activities_by_type.length > 0) {
                            const newLabels = data.data.activities_by_type.map(item => item.nombre);
                            const newData = data.data.activities_by_type.map(item => parseInt(item.cantidad));
                            
                            activitiesChart.data.labels = newLabels;
                            activitiesChart.data.datasets[0].data = newData;
                            activitiesChart.update();
                        }
                        
                        // Actualizar gráfica de usuarios por rol
                        if (data.data.user_stats) {
                            const userLabels = Object.keys(data.data.user_stats);
                            const userData = Object.values(data.data.user_stats).map(stats => parseInt(stats.total));
                            
                            usersChart.data.labels = userLabels;
                            usersChart.data.datasets[0].data = userData;
                            usersChart.update();
                        }
                        
                        // Actualizar gráfica de actividades mensuales
                        if (data.data.monthly_activities && data.data.monthly_activities.length > 0) {
                            const monthlyLabels = data.data.monthly_activities.map(item => {
                                const date = new Date(item.mes + '-01');
                                return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                            });
                            const monthlyData = data.data.monthly_activities.map(item => parseInt(item.cantidad));
                            
                            monthlyChart.data.labels = monthlyLabels;
                            monthlyChart.data.datasets[0].data = monthlyData;
                            monthlyChart.update();
                        }
                        
                        // Actualizar gráfica de ranking de equipos
                        if (data.data.team_ranking && data.data.team_ranking.length > 0) {
                            const teamLabels = data.data.team_ranking.slice(0, 8).map(item => {
                                const name = item.lider_nombre || 'Sin nombre';
                                return name.length > 15 ? name.substring(0, 15) + '...' : name;
                            });
                            const teamData = data.data.team_ranking.slice(0, 8).map(item => parseInt(item.completadas));
                            
                            teamRankingChart.data.labels = teamLabels;
                            teamRankingChart.data.datasets[0].data = teamData;
                            teamRankingChart.update();
                        }
                        
                        // Actualizar timestamp
                        const now = new Date();
                        lastUpdateSpan.textContent = `Última actualización: ${now.toLocaleTimeString()}`;
                        
                        console.log('Gráficas actualizadas con datos reales');
                    } else {
                        console.error('Error en la respuesta:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar datos:', error);
                    lastUpdateSpan.textContent = 'Error al actualizar datos';
                })
                .finally(() => {
                    // Restaurar botón
                    refreshButton.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Actualizar Datos';
                    refreshButton.disabled = false;
                });
        }
        
        // Guardar referencias a las gráficas para poder actualizarlas
        let activitiesChart, usersChart, monthlyChart, teamRankingChart;
        
        // Agregar event listener para el botón de actualizar
        document.getElementById('refreshData').addEventListener('click', updateCharts);
        
        // Actualizar cada 30 segundos automáticamente (opcional)
        // setInterval(updateCharts, 30000);
        
        // Funciones para gestión de usuarios pendientes
        function approveUser(userId) {
            if (confirm('¿Está seguro de que desea aprobar este usuario?')) {
                fetch('<?= url('api/users.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'approve',
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar la página para actualizar la lista
                        location.reload();
                    } else {
                        alert('Error al aprobar usuario: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al aprobar usuario');
                });
            }
        }
        
        function rejectUser(userId) {
            if (confirm('¿Está seguro de que desea rechazar este usuario? Esta acción no se puede deshacer.')) {
                fetch('<?= url('api/users.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'reject',
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar la página para actualizar la lista
                        location.reload();
                    } else {
                        alert('Error al rechazar usuario: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al rechazar usuario');
                });
            }
        }
    </script>
</body>
</html>