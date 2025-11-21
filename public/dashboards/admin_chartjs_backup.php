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
        // Detectar si estamos en entorno local/desarrollo
        $isLocal = (
            isset($_SERVER['HTTP_HOST']) && 
            (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
             strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
             strpos($_SERVER['HTTP_HOST'], 'local') !== false)
        );
        
        if ($isLocal) {
            // En local, usar rutas relativas
            $path = ltrim($path, '/');
            return '../' . $path;
        } else {
            // En producción, usar la URL completa
            $base_url = 'https://fix360.app/ad/public';
            $path = ltrim($path, '/');
            return $base_url . ($path ? '/' . $path : '');
        }
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
    
    <!-- Cargar Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                            <a class="nav-link text-white" href="<?= url('ranking/') ?>">
                                <i class="fas fa-trophy me-2"></i>Ranking
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activity-types/') ?>">
                                <i class="fas fa-list me-2"></i>Tipos de Actividad
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

                <!-- Diagnóstico del estado del dashboard -->
                <?php 
                $allDataEmpty = (count($activitiesByType) + count($userStats) + count($monthlyActivities) + count($teamRanking)) === 0;
                if ($allDataEmpty): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-database me-2"></i>Estado de Conexión de Base de Datos</h5>
                        <p><strong>⚠️ Problema Detectado:</strong> No se pudieron cargar datos del dashboard.</p>
                        <p><strong>Posibles Causas:</strong></p>
                        <ul class="mb-2">
                            <li>Conexión a base de datos interrumpida</li>
                            <li>Base de datos sin datos iniciales</li>
                            <li>Configuración de base de datos incorrecta</li>
                            <li>Permisos de usuario insuficientes</li>
                        </ul>
                        <p><strong>✅ Solución Implementada:</strong> Las gráficas mostrarán datos de demostración para evitar pantallas vacías.</p>
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
                        ?>
                    <?php else: ?>
                        <?php 
                        // Valores por defecto cuando hay error
                        $totalUsers = 0;
                        $totalActivities = 0;
                        $completedActivities = 0;
                        ?>
                    <?php endif; ?>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
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
                    
                    <div class="col-xl-4 col-md-6 mb-4">
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
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card bg-info text-white card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">% Atención</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $totalActivities > 0 ? number_format(($completedActivities / $totalActivities) * 100, 1) : '0.0' ?>%</span>
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

                <!-- Métricas del mes actual -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="h4 mb-3">Métricas del Mes Actual (<?= date('F Y') ?>)</h3>
                    </div>
                    <?php 
                    $currentMonthMetrics = $GLOBALS['currentMonthMetrics'] ?? [];
                    $totalMes = isset($currentMonthMetrics['total_actividades_mes']) ? $currentMonthMetrics['total_actividades_mes'] : 0;
                    $completadasMes = isset($currentMonthMetrics['completadas_mes']) ? $currentMonthMetrics['completadas_mes'] : 0;
                    $programadasMes = isset($currentMonthMetrics['programadas_mes']) ? $currentMonthMetrics['programadas_mes'] : 0;
                    $enProgresoMes = isset($currentMonthMetrics['en_progreso_mes']) ? $currentMonthMetrics['en_progreso_mes'] : 0;
                    $alcanceMes = isset($currentMonthMetrics['alcance_total_mes']) ? $currentMonthMetrics['alcance_total_mes'] : 0;
                    ?>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                <h5><?= number_format($totalMes) ?></h5>
                                <small>Total del Mes</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h5><?= number_format($completadasMes) ?></h5>
                                <small>Completadas</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h5><?= number_format($programadasMes) ?></h5>
                                <small>Programadas</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-spinner fa-2x mb-2"></i>
                                <h5><?= number_format($enProgresoMes) ?></h5>
                                <small>En Progreso</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h5><?= number_format($alcanceMes) ?></h5>
                                <small>Alcance Total</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-percentage fa-2x mb-2"></i>
                                <h5><?= $totalMes > 0 ? number_format(($completadasMes / $totalMes) * 100, 1) : '0.0' ?>%</h5>
                                <small>% Completado</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficas y estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Actividades por Tipo
                                </h5>
                            </div>
                            <div class="card-body" style="min-height: 300px;">
                                <canvas id="activitiesChart" width="400" height="200"></canvas>
                                <div id="activitiesChartError" class="d-none alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Error al cargar la gráfica. Intente recargar la página.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Usuarios por Rol
                                </h5>
                            </div>
                            <div class="card-body" style="min-height: 300px;">
                                <canvas id="usersChart" width="200" height="200"></canvas>
                                <div id="usersChartError" class="d-none alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Error al cargar la gráfica.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nuevas gráficas informativas -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Actividades por Mes (Últimos 12 meses)
                                </h5>
                            </div>
                            <div class="card-body" style="min-height: 300px;">
                                <canvas id="monthlyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-trophy me-2"></i>Ranking de Equipos
                                </h5>
                            </div>
                            <div class="card-body" style="min-height: 300px;">
                                <canvas id="teamRankingChart" width="400" height="200"></canvas>
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

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // Obtener datos reales de la base de datos
    $activitiesByType = $GLOBALS['activitiesByType'] ?? [];
    $userStats = $GLOBALS['userStats'] ?? [];
    $monthlyActivities = $GLOBALS['monthlyActivities'] ?? [];
    $teamRanking = $GLOBALS['teamRanking'] ?? [];
    
    // DEBUG en PHP
    error_log("Dashboard Debug - activitiesByType count: " . count($activitiesByType));
    error_log("Dashboard Debug - userStats count: " . count($userStats));
    error_log("Dashboard Debug - monthlyActivities count: " . count($monthlyActivities));
    error_log("Dashboard Debug - teamRanking count: " . count($teamRanking));
    
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
    
    // Preparar fallbacks si no hay datos
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
    
    // Convertir datos PHP a JavaScript
    $activityLabelsJSON = json_encode($activityLabels);
    $activityDataJSON = json_encode($activityData);
    $userLabelsJSON = json_encode($userLabels);
    $userDataJSON = json_encode($userData);
    $monthlyLabelsJSON = json_encode($monthlyLabels);
    $monthlyDataJSON = json_encode($monthlyData);
    $teamLabelsJSON = json_encode($teamLabels);
    $teamDataJSON = json_encode($teamData);
    
    // Debug: Imprimir los valores JSON generados
    error_log("DEBUG JSON - activityLabels: " . $activityLabelsJSON);
    error_log("DEBUG JSON - activityData: " . $activityDataJSON);
    ?>
    
    <!-- Debug: Mostrar datos en comentario HTML -->
    <!-- 
    Activity Labels: <?= htmlspecialchars($activityLabelsJSON) ?>
    Activity Data: <?= htmlspecialchars($activityDataJSON) ?>
    -->
    
    <script>
        // Variables globales para las instancias de Chart.js
        let activitiesChart, usersChart, monthlyChart, teamRankingChart;
        
        // Datos desde PHP
        const activityLabels = <?= $activityLabelsJSON ?>;
        const activityData = <?= $activityDataJSON ?>;
        const userLabels = <?= $userLabelsJSON ?>;
        const userData = <?= $userDataJSON ?>;
        const monthlyLabels = <?= $monthlyLabelsJSON ?>;
        const monthlyData = <?= $monthlyDataJSON ?>;
        const teamLabels = <?= $teamLabelsJSON ?>;
        const teamData = <?= $teamDataJSON ?>;
        
        // DEBUG: Imprimir los datos que se van a usar en JavaScript
        console.log('🔍 DEBUG: Verificando datos desde PHP...');
        console.log('📦 Datos PHP convertidos a JavaScript:');
        console.log('activityLabels:', activityLabels);
        console.log('activityData:', activityData);
        console.log('userLabels:', userLabels);
        console.log('userData:', userData);
        console.log('monthlyLabels:', monthlyLabels);
        console.log('monthlyData:', monthlyData);
        console.log('teamLabels:', teamLabels);
        console.log('teamData:', teamData);
        
        // Función para inicializar las gráficas con manejo de errores mejorado
        function initializeCharts() {
            try {
                console.log('🚀 Inicializando gráficas del dashboard...');
                console.log('📊 Datos disponibles:');
                console.log('- Actividades por tipo:', activityLabels);
                console.log('- Usuarios por rol:', userLabels);
                console.log('- Actividades mensuales:', monthlyLabels);
                console.log('- Equipos:', teamLabels);
                console.log('- Activity Data:', activityData);
                console.log('- User Data:', userData);
                
                // Verificar que Chart.js esté disponible
                if (typeof Chart === 'undefined') {
                    console.error('❌ Chart.js no está cargado');
                    return;
                }
                
                // Verificar que los elementos DOM existan antes de inicializar
                const elementsToCheck = [
                    'activitiesChart', 'usersChart', 'monthlyChart', 'teamRankingChart'
                ];
                
                for (const elementId of elementsToCheck) {
                    const element = document.getElementById(elementId);
                    if (!element) {
                        console.error(`❌ Elemento DOM no encontrado: ${elementId}`);
                        return;
                    }
                }
                
                // Inicializar gráfica de actividades por tipo
                initializeActivitiesChart();
                
                // Inicializar gráfica de usuarios por rol
                initializeUsersChart();
                
                // Inicializar gráfica de actividades mensuales
                initializeMonthlyChart();
                
                // Inicializar gráfica de ranking de equipos
                initializeTeamRankingChart();
                
                console.log('✅ Todas las gráficas inicializadas correctamente');
                
            } catch (error) {
                console.error('❌ Error al inicializar gráficas:', error);
            }
        }
        
        // Función para inicializar gráfica de actividades por tipo
        function initializeActivitiesChart() {
            try {
                const canvas = document.getElementById('activitiesChart');
                if (!canvas) {
                    console.error('❌ Canvas activitiesChart no encontrado');
                    return;
                }
                
                const activitiesCtx = canvas.getContext('2d');
                if (!activitiesCtx) {
                    console.error('❌ No se pudo obtener contexto 2D del canvas');
                    return;
                }
                
                console.log('📊 Iniciando gráfica de actividades con datos:', activityLabels, activityData);
                
                activitiesChart = new Chart(activitiesCtx, {
                    type: 'bar',
                    data: {
                        labels: activityLabels,
                        datasets: [{
                            label: 'Cantidad',
                            data: activityData,
                            backgroundColor: 'rgba(102, 126, 234, 0.6)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
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
                                text: 'Actividades por Tipo'
                            },
                            legend: {
                                display: false
                            }
                        }
                    }
                });
                console.log('✅ Gráfica de actividades por tipo inicializada correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar gráfica de actividades:', error);
            }
        }
        
        // Función para inicializar gráfica de usuarios por rol
        function initializeUsersChart() {
            try {
                const canvas = document.getElementById('usersChart');
                if (!canvas) {
                    console.error('❌ Canvas usersChart no encontrado');
                    return;
                }
                
                const usersCtx = canvas.getContext('2d');
                console.log('📊 Iniciando gráfica de usuarios con datos:', userLabels, userData);
                
                usersChart = new Chart(usersCtx, {
                    type: 'doughnut',
                    data: {
                        labels: userLabels,
                        datasets: [{
                            data: userData,
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
                        maintainAspectRatio: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Usuarios por Rol'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
                console.log('✅ Gráfica de usuarios por rol inicializada correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar gráfica de usuarios:', error);
            }
        }
        
        // Función para inicializar gráfica de actividades mensuales
        function initializeMonthlyChart() {
            try {
                const canvas = document.getElementById('monthlyChart');
                if (!canvas) {
                    console.error('❌ Canvas monthlyChart no encontrado');
                    return;
                }
                
                const monthlyCtx = canvas.getContext('2d');
                console.log('📊 Iniciando gráfica mensual con datos:', monthlyLabels, monthlyData);
                
                monthlyChart = new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Actividades',
                            data: monthlyData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
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
                console.log('✅ Gráfica de actividades mensuales inicializada correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar gráfica mensual:', error);
            }
        }
        
        // Función para inicializar gráfica de ranking de equipos
        function initializeTeamRankingChart() {
            try {
                const canvas = document.getElementById('teamRankingChart');
                if (!canvas) {
                    console.error('❌ Canvas teamRankingChart no encontrado');
                    return;
                }
                
                const teamRankingCtx = canvas.getContext('2d');
                console.log('📊 Iniciando gráfica de ranking con datos:', teamLabels, teamData);
                
                teamRankingChart = new Chart(teamRankingCtx, {
                    type: 'bar',
                    data: {
                        labels: teamLabels,
                        datasets: [{
                            label: 'Actividades Completadas',
                            data: teamData,
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
                        maintainAspectRatio: true,
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
                console.log('✅ Gráfica de ranking de equipos inicializada correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar gráfica de ranking:', error);
            }
        }
        
        // Función para actualizar gráficas en tiempo real
        function updateCharts() {
            const refreshButton = document.getElementById('refreshData');
            const lastUpdateSpan = document.getElementById('lastUpdate');
            
            // Mostrar indicador de carga
            refreshButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...';
            refreshButton.disabled = true;
            
            console.log('🔄 Actualizando gráficas desde API...');
            
            fetch('<?= url('api/stats.php') ?>')
                .then(response => {
                    console.log('📡 Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`Error ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📊 API response:', data);
                    
                    if (data.success) {
                        let chartsUpdated = 0;
                        
                        // Actualizar gráfica de actividades por tipo
                        if (data.data.activities_by_type && data.data.activities_by_type.length > 0 && activitiesChart) {
                            const newLabels = data.data.activities_by_type.map(item => item.nombre);
                            const newData = data.data.activities_by_type.map(item => parseInt(item.cantidad));
                            
                            activitiesChart.data.labels = newLabels;
                            activitiesChart.data.datasets[0].data = newData;
                            activitiesChart.update();
                            chartsUpdated++;
                            console.log('✅ Gráfica de actividades actualizada:', newLabels.length, 'items');
                        } else {
                            console.warn('⚠️ No se recibieron datos de actividades por tipo');
                        }
                        
                        // Actualizar gráfica de usuarios por rol
                        if (data.data.user_stats && usersChart) {
                            const userLabels = Object.keys(data.data.user_stats);
                            const userData = Object.values(data.data.user_stats).map(stats => parseInt(stats.total));
                            
                            usersChart.data.labels = userLabels;
                            usersChart.data.datasets[0].data = userData;
                            usersChart.update();
                            chartsUpdated++;
                            console.log('✅ Gráfica de usuarios actualizada:', userLabels.length, 'roles');
                        } else {
                            console.warn('⚠️ No se recibieron estadísticas de usuarios');
                        }
                        
                        // Actualizar gráfica de actividades mensuales
                        if (data.data.monthly_activities && data.data.monthly_activities.length > 0 && monthlyChart) {
                            const monthlyLabels = data.data.monthly_activities.map(item => {
                                const date = new Date(item.mes + '-01');
                                return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                            });
                            const monthlyData = data.data.monthly_activities.map(item => parseInt(item.cantidad));
                            
                            monthlyChart.data.labels = monthlyLabels;
                            monthlyChart.data.datasets[0].data = monthlyData;
                            monthlyChart.update();
                            chartsUpdated++;
                            console.log('✅ Gráfica mensual actualizada:', monthlyLabels.length, 'meses');
                        } else {
                            console.warn('⚠️ No se recibieron datos de actividades mensuales');
                        }
                        
                        // Actualizar gráfica de ranking de equipos
                        if (data.data.team_ranking && data.data.team_ranking.length > 0 && teamRankingChart) {
                            const teamLabels = data.data.team_ranking.slice(0, 8).map(item => {
                                const name = item.lider_nombre || 'Sin nombre';
                                return name.length > 15 ? name.substring(0, 15) + '...' : name;
                            });
                            const teamData = data.data.team_ranking.slice(0, 8).map(item => parseInt(item.completadas));
                            
                            teamRankingChart.data.labels = teamLabels;
                            teamRankingChart.data.datasets[0].data = teamData;
                            teamRankingChart.update();
                            chartsUpdated++;
                            console.log('✅ Gráfica de ranking actualizada:', teamLabels.length, 'equipos');
                        } else {
                            console.warn('⚠️ No se recibieron datos de ranking de equipos');
                        }
                        
                        // Actualizar timestamp
                        const now = new Date();
                        lastUpdateSpan.textContent = `Última actualización: ${now.toLocaleTimeString()}`;
                        
                        console.log(`✅ ${chartsUpdated} gráficas actualizadas con datos reales`);
                        
                        if (chartsUpdated === 0) {
                            console.warn('⚠️ No se actualizó ninguna gráfica - verificar datos de API');
                        }
                    } else {
                        console.error('❌ Error en la respuesta:', data.error);
                        lastUpdateSpan.textContent = `Error: ${data.error}`;
                        
                        // Mostrar alerta si es error de autenticación
                        if (data.error_code === 'NOT_AUTHENTICATED' || data.error_code === 'USER_NOT_FOUND') {
                            alert('Su sesión ha expirado. Por favor, recargue la página e inicie sesión nuevamente.');
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Error al actualizar datos:', error);
                    lastUpdateSpan.textContent = `Error al actualizar: ${error.message}`;
                    
                    // Mostrar detalles del error en desarrollo
                    if (window.console && console.error) {
                        console.error('📋 Detalles del error:', error);
                    }
                })
                .finally(() => {
                    // Restaurar botón
                    refreshButton.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Actualizar Datos';
                    refreshButton.disabled = false;
                });
        }
        
        // Guardar referencias a las gráficas para poder actualizarlas
        // NOTA: Las variables ya están declaradas al inicio del script
        
        // Agregar event listener para el botón de actualizar
        document.getElementById('refreshData').addEventListener('click', updateCharts);
        
        // Inicialización principal del dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard cargando, inicializando gráficas...');
            console.log('📊 Datos iniciales disponibles:');
            console.log('- Labels de actividades:', <?= $activityLabelsJSON ?>);
            console.log('- Data de actividades:', <?= $activityDataJSON ?>);
            console.log('- Labels de usuarios:', <?= $userLabelsJSON ?>);
            console.log('- Data de usuarios:', <?= $userDataJSON ?>);
            
            // Verificar que Chart está disponible
            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js no está cargado');
                alert('Error: La librería de gráficas no se cargó correctamente. Refresque la página.');
                return;
            }
            
            // Inicializar inmediatamente
            initializeCharts();
            
            console.log('✅ Gráficas inicializadas');
        });
        
        // Actualizar cada 60 segundos automáticamente (solo si hay datos iniciales)
        if (<?= !empty($activitiesByType) ? 'true' : 'false' ?>) {
            setInterval(updateCharts, 60000);
            console.log('🔄 Auto-refresh habilitado (cada 60 segundos)');
        }
        
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