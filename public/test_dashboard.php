<?php
// Test dashboard para verificar las gráficas sin autenticación
// Solo para testing y debugging

// Datos de prueba para las gráficas
$activitiesByType = [
    ['nombre' => 'Redes Sociales', 'cantidad' => 12],
    ['nombre' => 'Eventos', 'cantidad' => 8],
    ['nombre' => 'Capacitación', 'cantidad' => 5],
    ['nombre' => 'Encuestas', 'cantidad' => 3]
];

$userStats = [
    'SuperAdmin' => ['total' => 1],
    'Gestor' => ['total' => 2],
    'Líder' => ['total' => 5],
    'Activista' => ['total' => 15]
];

$monthlyActivities = [
    ['mes' => '2024-01', 'cantidad' => 8],
    ['mes' => '2024-02', 'cantidad' => 12],
    ['mes' => '2024-03', 'cantidad' => 15],
    ['mes' => '2024-04', 'cantidad' => 18],
    ['mes' => '2024-05', 'cantidad' => 22],
    ['mes' => '2024-06', 'cantidad' => 25]
];

$teamRanking = [
    ['lider_nombre' => 'María González', 'completadas' => 15],
    ['lider_nombre' => 'Juan Pérez', 'completadas' => 12],
    ['lider_nombre' => 'Ana Martínez', 'completadas' => 10],
    ['lider_nombre' => 'Carlos Rodríguez', 'completadas' => 8]
];

// Preparar datos para JavaScript
$activityLabels = [];
$activityData = [];
foreach ($activitiesByType as $activity) {
    $activityLabels[] = $activity['nombre'];
    $activityData[] = (int)$activity['cantidad'];
}

$userLabels = [];
$userData = [];
foreach ($userStats as $rol => $stats) {
    $userLabels[] = $rol;
    $userData[] = (int)$stats['total'];
}

$monthlyLabels = [];
$monthlyData = [];
foreach ($monthlyActivities as $month) {
    $monthlyLabels[] = date('M Y', strtotime($month['mes'] . '-01'));
    $monthlyData[] = (int)$month['cantidad'];
}

$teamLabels = [];
$teamData = [];
foreach ($teamRanking as $team) {
    $teamLabels[] = substr($team['lider_nombre'], 0, 15);
    $teamData[] = (int)$team['completadas'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard - Gráficas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="text-center mb-4">Test Dashboard - Verificación de Gráficas</h1>
        
        <div class="alert alert-info">
            <strong>Propósito:</strong> Este dashboard de prueba permite verificar que las gráficas funcionan correctamente 
            sin necesidad de autenticación o conexión a base de datos.
        </div>

        <!-- Métricas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Usuarios</h5>
                        <h2><?= array_sum(array_column($userStats, 'total')) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Actividades</h5>
                        <h2><?= array_sum($activityData) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Este Mes</h5>
                        <h2><?= end($monthlyData) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Equipos Activos</h5>
                        <h2><?= count($teamRanking) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Actividades por Tipo</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="activitiesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Usuarios por Rol</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="usersChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Actividades por Mes</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ranking de Equipos</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="teamRankingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-success mt-4">
            <strong>Estado:</strong> Si las cuatro gráficas se muestran correctamente arriba, 
            entonces el problema en el dashboard original está relacionado con la conexión a la base de datos 
            o la autenticación, no con la lógica de Chart.js.
        </div>
    </div>

    <script>
        // Variables globales para las gráficas
        let activitiesChart, usersChart, monthlyChart, teamRankingChart;
        
        console.log('🚀 Iniciando test dashboard...');
        
        // Datos desde PHP
        const activityLabels = <?= json_encode($activityLabels) ?>;
        const activityData = <?= json_encode($activityData) ?>;
        const userLabels = <?= json_encode($userLabels) ?>;
        const userData = <?= json_encode($userData) ?>;
        const monthlyLabels = <?= json_encode($monthlyLabels) ?>;
        const monthlyData = <?= json_encode($monthlyData) ?>;
        const teamLabels = <?= json_encode($teamLabels) ?>;
        const teamData = <?= json_encode($teamData) ?>;
        
        console.log('📊 Datos cargados:', {
            activityLabels,
            activityData,
            userLabels,
            userData,
            monthlyLabels,
            monthlyData,
            teamLabels,
            teamData
        });

        function initializeCharts() {
            try {
                console.log('🎨 Inicializando gráficas...');
                
                // Verificar que Chart.js esté disponible
                if (typeof Chart === 'undefined') {
                    console.error('❌ Chart.js no está cargado');
                    return;
                }
                
                // 1. Gráfica de actividades por tipo
                const activitiesCtx = document.getElementById('activitiesChart').getContext('2d');
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
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribución de Actividades por Tipo'
                            }
                        }
                    }
                });
                console.log('✅ Gráfica de actividades inicializada');

                // 2. Gráfica de usuarios por rol
                const usersCtx = document.getElementById('usersChart').getContext('2d');
                usersChart = new Chart(usersCtx, {
                    type: 'doughnut',
                    data: {
                        labels: userLabels,
                        datasets: [{
                            data: userData,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 205, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribución de Usuarios por Rol'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
                console.log('✅ Gráfica de usuarios inicializada');

                // 3. Gráfica de actividades mensuales
                const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
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
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Tendencia de Actividades por Mes'
                            },
                            legend: { display: false }
                        }
                    }
                });
                console.log('✅ Gráfica mensual inicializada');

                // 4. Gráfica de ranking de equipos
                const teamRankingCtx = document.getElementById('teamRankingChart').getContext('2d');
                teamRankingChart = new Chart(teamRankingCtx, {
                    type: 'bar',
                    data: {
                        labels: teamLabels,
                        datasets: [{
                            label: 'Actividades Completadas',
                            data: teamData,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 205, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 205, 86, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Top Equipos por Actividades Completadas'
                            },
                            legend: { display: false }
                        }
                    }
                });
                console.log('✅ Gráfica de ranking inicializada');

                console.log('🎉 Todas las gráficas inicializadas correctamente');
                
            } catch (error) {
                console.error('❌ Error al inicializar gráficas:', error);
            }
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM listo, esperando 500ms...');
            setTimeout(initializeCharts, 500);
        });
    </script>
</body>
</html>