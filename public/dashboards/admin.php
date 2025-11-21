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

// Incluir funciones básicas
if (file_exists(__DIR__ . '/../../includes/functions.php')) {
    require_once __DIR__ . '/../../includes/functions.php';
}

// Fallback para funciones críticas
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
        $isLocal = (
            isset($_SERVER['HTTP_HOST']) && 
            (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
             strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
             strpos($_SERVER['HTTP_HOST'], 'local') !== false)
        );
        
        if ($isLocal) {
            $path = ltrim($path, '/');
            return '../' . $path;
        } else {
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
    require_once __DIR__ . '/../../controllers/dashboardController.php';
    
    if (!class_exists('DashboardController')) {
        throw new Exception("La clase DashboardController no fue encontrada");
    }
    
    $controller = new DashboardController();
    
    if (!method_exists($controller, 'adminDashboard')) {
        throw new Exception("El método adminDashboard no existe en DashboardController");
    }
    
    $controller->adminDashboard();
    
    // Obtener datos de las variables globales
    $activitiesByType = $GLOBALS['activitiesByType'] ?? [];
    $userStats = $GLOBALS['userStats'] ?? [];
    $monthlyActivities = $GLOBALS['monthlyActivities'] ?? [];
    $teamRanking = $GLOBALS['teamRanking'] ?? [];
    $recentActivities = $GLOBALS['recentActivities'] ?? [];
    $pendingUsers = $GLOBALS['pendingUsers'] ?? [];
    $activityStats = $GLOBALS['activityStats'] ?? [];
    $currentMonthMetrics = $GLOBALS['currentMonthMetrics'] ?? [];
    
    // Debug: verificar qué datos se cargaron
    if (function_exists('logActivity')) {
        logActivity("Dashboard Visual - Actividades por tipo: " . count($activitiesByType), 'DEBUG');
        logActivity("Dashboard Visual - User stats: " . count($userStats), 'DEBUG');
        logActivity("Dashboard Visual - Monthly activities: " . count($monthlyActivities), 'DEBUG');
        logActivity("Dashboard Visual - Team ranking: " . count($teamRanking), 'DEBUG');
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    
    if (function_exists('logActivity')) {
        logActivity("Error en admin dashboard: " . $error_message, 'ERROR');
    } else {
        error_log("Admin Dashboard Error: " . $error_message);
    }
    
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
    
    // Inicializar variables vacías para evitar errores
    $activitiesByType = [];
    $userStats = [];
    $monthlyActivities = [];
    $teamRanking = [];
    $recentActivities = [];
    $pendingUsers = [];
    $activityStats = [];
    $currentMonthMetrics = [];
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
        
        /* Estilos para barras de progreso horizontales */
        .bar-chart-item {
            margin-bottom: 1rem;
        }
        .bar-label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bar-container {
            width: 100%;
            height: 35px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .bar-fill.bg-success-gradient {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
        }
        .bar-fill.bg-info-gradient {
            background: linear-gradient(90deg, #17a2b8 0%, #0dcaf0 100%);
        }
        .bar-fill.bg-warning-gradient {
            background: linear-gradient(90deg, #ffc107 0%, #fd7e14 100%);
        }
        .bar-fill.bg-danger-gradient {
            background: linear-gradient(90deg, #dc3545 0%, #e91e63 100%);
        }
        
        /* Estilos para gráficas de torta (donuts con CSS) */
        .donut-chart {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }
        .donut-item {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            min-width: 120px;
        }
        .donut-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }
        .donut-circle.role-superadmin { background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%); }
        .donut-circle.role-gestor { background: linear-gradient(135deg, #0dcaf0 0%, #17a2b8 100%); }
        .donut-circle.role-lider { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .donut-circle.role-activista { background: linear-gradient(135deg, #20c997 0%, #28a745 100%); }
        .donut-circle.role-default { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
        
        /* Estilos para línea de tiempo */
        .timeline-chart {
            position: relative;
            padding: 1rem 0;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .timeline-month {
            min-width: 80px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .timeline-bar {
            flex-grow: 1;
            height: 30px;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: white;
            font-weight: bold;
            margin: 0 10px;
            transition: transform 0.2s;
        }
        .timeline-bar:hover {
            transform: scaleY(1.1);
        }
        
        /* Ranking visual */
        .ranking-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .ranking-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .ranking-position {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.1rem;
            margin-right: 1rem;
        }
        .ranking-position.pos-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: white; }
        .ranking-position.pos-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%); color: white; }
        .ranking-position.pos-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); color: white; }
        .ranking-position.default { background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%); color: #495057; }
        .ranking-info {
            flex-grow: 1;
        }
        .ranking-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .ranking-score {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            min-width: 60px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('dashboard'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard SuperAdmin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="window.location.reload()" class="btn btn-sm btn-success">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Métricas principales -->
                <div class="row mb-4">
                    <?php 
                    $totalUsers = is_array($userStats) ? array_sum(array_column($userStats, 'total')) : 0;
                    $totalActivities = isset($activityStats['total_actividades']) ? $activityStats['total_actividades'] : 0;
                    $completedActivities = isset($activityStats['completadas']) ? $activityStats['completadas'] : 0;
                    ?>
                    
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
                                        <h5 class="card-title text-uppercase text-white-50 mb-0">% Completado</h5>
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
                    $totalMes = isset($currentMonthMetrics['total_actividades_mes']) ? $currentMonthMetrics['total_actividades_mes'] : 0;
                    $completadasMes = isset($currentMonthMetrics['completadas_mes']) ? $currentMonthMetrics['completadas_mes'] : 0;
                    $programadasMes = isset($currentMonthMetrics['programadas_mes']) ? $currentMonthMetrics['programadas_mes'] : 0;
                    $enProgresoMes = isset($currentMonthMetrics['en_progreso_mes']) ? $currentMonthMetrics['en_progreso_mes'] : 0;
                    $alcanceMes = isset($currentMonthMetrics['alcance_total_mes']) ? $currentMonthMetrics['alcance_total_mes'] : 0;
                    ?>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                <h5 class="mb-1"><?= number_format($totalMes) ?></h5>
                                <small>Total del Mes</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h5 class="mb-1"><?= number_format($completadasMes) ?></h5>
                                <small>Completadas</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h5 class="mb-1"><?= number_format($programadasMes) ?></h5>
                                <small>Programadas</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="fas fa-spinner fa-2x mb-2"></i>
                                <h5 class="mb-1"><?= number_format($enProgresoMes) ?></h5>
                                <small>En Progreso</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-secondary text-white h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <h5 class="mb-1"><?= number_format(count($recentActivities)) ?></h5>
                                <small>Recientes</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark text-white h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="fas fa-percentage fa-2x mb-2"></i>
                                <h5 class="mb-1"><?= $totalMes > 0 ? number_format(($completadasMes / $totalMes) * 100, 1) : '0.0' ?>%</h5>
                                <small>% Completado</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficas visuales con HTML/CSS -->
                <div class="row mb-4">
                    <!-- Actividades por Tipo -->
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Actividades por Tipo
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                <?php if (!empty($activitiesByType)): ?>
                                    <?php 
                                    $maxCount = max(array_column($activitiesByType, 'cantidad'));
                                    foreach ($activitiesByType as $activity): 
                                        $percentage = $maxCount > 0 ? ($activity['cantidad'] / $maxCount) * 100 : 0;
                                    ?>
                                        <div class="bar-chart-item">
                                            <div class="bar-label">
                                                <span><?= htmlspecialchars($activity['nombre']) ?></span>
                                                <span class="badge bg-primary"><?= $activity['cantidad'] ?></span>
                                            </div>
                                            <div class="bar-container">
                                                <div class="bar-fill" style="width: <?= $percentage ?>%">
                                                    <?= $activity['cantidad'] ?> actividades
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>No hay datos disponibles.</strong> Las gráficas se mostrarán cuando haya actividades registradas en el sistema.
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="<?= url('activities/') ?>" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Crear Primera Actividad
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usuarios por Rol -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Usuarios por Rol
                                </h5>
                            </div>
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <?php if (!empty($userStats)): ?>
                                    <div class="donut-chart">
                                        <?php foreach ($userStats as $rol => $stats): 
                                            // Normalizar nombre del rol para CSS (quitar acentos)
                                            $rolClass = strtolower(str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $rol));
                                        ?>
                                            <div class="donut-item">
                                                <div class="donut-circle role-<?= $rolClass ?>">
                                                    <?= $stats['total'] ?>
                                                </div>
                                                <div class="donut-label">
                                                    <strong><?= htmlspecialchars($rol) ?></strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No hay estadísticas de usuarios disponibles.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividades Mensuales y Ranking -->
                <div class="row mb-4">
                    <!-- Actividades por Mes -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Actividades por Mes (Últimos 12 meses)
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 450px; overflow-y: auto;">
                                <?php if (!empty($monthlyActivities)): ?>
                                    <div class="timeline-chart">
                                        <?php 
                                        $maxMonthly = max(array_column($monthlyActivities, 'cantidad'));
                                        foreach ($monthlyActivities as $month): 
                                            $widthPercent = $maxMonthly > 0 ? ($month['cantidad'] / $maxMonthly) * 100 : 0;
                                            $monthDate = date('M Y', strtotime($month['mes'] . '-01'));
                                        ?>
                                            <div class="timeline-item">
                                                <div class="timeline-month"><?= $monthDate ?></div>
                                                <div class="timeline-bar" style="width: <?= $widthPercent ?>%">
                                                    <?= $month['cantidad'] ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No hay datos de actividades mensuales.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ranking de Equipos -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-trophy me-2"></i>Ranking de Equipos
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 450px; overflow-y: auto;">
                                <?php if (!empty($teamRanking)): ?>
                                    <?php 
                                    $position = 1;
                                    foreach (array_slice($teamRanking, 0, 8) as $team): 
                                        $posClass = $position <= 3 ? "pos-$position" : "default";
                                    ?>
                                        <div class="ranking-item">
                                            <div class="ranking-position <?= $posClass ?>">
                                                <?= $position ?>
                                            </div>
                                            <div class="ranking-info">
                                                <div class="ranking-name">
                                                    <?= htmlspecialchars($team['lider_nombre']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= isset($team['miembros_equipo']) ? $team['miembros_equipo'] : 0 ?> miembros
                                                </small>
                                            </div>
                                            <div class="ranking-score">
                                                <?= $team['completadas'] ?>
                                            </div>
                                        </div>
                                    <?php 
                                        $position++;
                                    endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No hay equipos con actividades completadas aún.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usuarios Pendientes y Actividades Recientes -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-clock me-2"></i>Usuarios Pendientes de Aprobación
                                    <?php if (is_array($pendingUsers) && count($pendingUsers) > 0): ?>
                                        <span class="badge bg-warning text-dark"><?= count($pendingUsers) ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (is_array($pendingUsers) && count($pendingUsers) > 0): ?>
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
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Últimas Actividades Recientes
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (is_array($recentActivities) && count($recentActivities) > 0): ?>
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
</body>
</html>
