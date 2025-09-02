<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
        }
        .ranking-card {
            transition: transform 0.2s;
            border-radius: 15px;
        }
        .ranking-card:hover {
            transform: translateY(-3px);
        }
        .position-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border: 3px solid #ffd700;
        }
        .position-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
            border: 3px solid #c0c0c0;
        }
        .position-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #d4a574 100%);
            border: 3px solid #cd7f32;
        }
        .position-badge {
            font-size: 1.2rem;
            font-weight: bold;
            min-width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .points-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .trophy-icon {
            font-size: 2rem;
        }
        .current-user {
            background-color: #e3f2fd;
            border-left: 5px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('ranking'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-trophy text-warning me-2"></i><?= htmlspecialchars($title) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-warning text-dark fs-6">
                                <?= count($rankings) ?> participante(s)
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

                <!-- Month Selector for Admin -->
                <?php if (isset($showMonthSelector) && $showMonthSelector): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Seleccionar Período</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="year" class="form-label">Año</label>
                                    <select class="form-select" id="year" name="year">
                                        <?php
                                        $startYear = date('Y') - 2;
                                        $endYear = date('Y');
                                        for ($y = $endYear; $y >= $startYear; $y--): ?>
                                            <option value="<?= $y ?>" <?= ($currentYear == $y) ? 'selected' : '' ?>>
                                                <?= $y ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="month" class="form-label">Mes</label>
                                    <select class="form-select" id="month" name="month">
                                        <?php
                                        $months = [
                                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                        ];
                                        foreach ($months as $num => $name): ?>
                                            <option value="<?= $num ?>" <?= ($currentMonth == $num) ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-1"></i>Ver Ranking
                                    </button>
                                    <a href="?" class="btn btn-outline-secondary">
                                        <i class="fas fa-calendar-day me-1"></i>Actual
                                    </a>
                                </div>
                            </form>
                            
                            <?php if (!empty($availablePeriods)): ?>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Períodos disponibles:</strong>
                                        <?php foreach (array_slice($availablePeriods, 0, 6) as $period): ?>
                                            <a href="?year=<?= $period['anio'] ?>&month=<?= $period['mes'] ?>" 
                                               class="badge bg-secondary text-decoration-none me-1">
                                                <?= $months[$period['mes']] ?> <?= $period['anio'] ?>
                                            </a>
                                        <?php endforeach; ?>
                                        <?php if (count($availablePeriods) > 6): ?>
                                            <span class="text-muted">y <?= count($availablePeriods) - 6 ?> más...</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Descripción del ranking -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= htmlspecialchars($description) ?>
                </div>

                <!-- Información del sistema de puntos -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calculator me-2"></i>Sistema de Puntos - Nuevo Modelo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h6><i class="fas fa-trophy text-warning me-2"></i>Nuevo Sistema de Ranking por Orden de Respuesta</h6>
                                <div class="alert alert-light">
                                    <ul class="mb-0">
                                        <li><strong>Base:</strong> 1000 puntos</li>
                                        <li><strong>Primer respondedor:</strong> 1000 + número total de usuarios activos en el sistema</li>
                                        <li><strong>Segundo respondedor:</strong> (1000 + total usuarios) - 1</li>
                                        <li><strong>Tercer respondedor:</strong> (1000 + total usuarios) - 2</li>
                                        <li><strong>Y así sucesivamente...</strong> hasta el último en responder</li>
                                    </ul>
                                </div>
                                <div class="text-muted">
                                    <small><i class="fas fa-info-circle me-1"></i>
                                    Los puntos se acumulan por cada tarea completada. Responder rápido a las tareas te dará más puntos en el ranking.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($currentUser['rol'] === 'Activista' && $userPosition): ?>
                <!-- Posición del usuario actual -->
                <div class="alert alert-primary">
                    <i class="fas fa-medal me-2"></i>
                    <strong>Tu posición actual:</strong> #<?= $userPosition ?> en el ranking general
                </div>
                <?php endif; ?>

                <?php if (empty($rankings)): ?>
                    <!-- Sin datos de ranking -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-chart-bar text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-muted">No hay datos de ranking</h4>
                        <p class="text-muted">Aún no hay activistas con tareas completadas.</p>
                    </div>
                <?php else: ?>
                    <!-- Podio (Top 3) -->
                    <?php if (count($rankings) >= 3): ?>
                    <div class="row mb-5">
                        <div class="col-12">
                            <h3 class="text-center mb-4">
                                <i class="fas fa-medal text-warning"></i> Podio de Campeones
                            </h3>
                        </div>
                        
                        <!-- Segundo lugar -->
                        <div class="col-md-4 order-md-1 mb-3">
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                <a href="<?= url('admin/edit_user.php?id=' . $rankings[1]['id']) ?>" class="text-decoration-none">
                            <?php endif; ?>
                            <div class="card ranking-card position-2 text-center">
                                <div class="card-body">
                                    <div class="position-badge bg-light text-dark mx-auto mb-3">2</div>
                                    <?php if (isset($rankings[1]['id']) && !empty($rankings[1]['id'])): ?>
                                        <h5 class="card-title">
                                            <a href="<?= url('profile.php?user_id=' . $rankings[1]['id']) ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($rankings[1]['nombre_completo']) ?>
                                            </a>
                                        </h5>
                                    <?php else: ?>
                                        <h5 class="card-title"><?= htmlspecialchars($rankings[1]['nombre_completo']) ?></h5>
                                    <?php endif; ?>
                                    <div class="points-display"><?= number_format($rankings[1]['ranking_puntos']) ?> pts</div>
                                    <small class="text-muted"><?= $rankings[1]['actividades_completadas'] ?> tareas</small>
                                </div>
                            </div>
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Primer lugar -->
                        <div class="col-md-4 order-md-2 mb-3">
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                <a href="<?= url('admin/edit_user.php?id=' . $rankings[0]['id']) ?>" class="text-decoration-none">
                            <?php endif; ?>
                            <div class="card ranking-card position-1 text-center">
                                <div class="card-body">
                                    <i class="fas fa-crown trophy-icon text-warning mb-2"></i>
                                    <div class="position-badge bg-warning text-dark mx-auto mb-3">1</div>
                                    <?php if (isset($rankings[0]['id']) && !empty($rankings[0]['id'])): ?>
                                        <h5 class="card-title">
                                            <a href="<?= url('profile.php?user_id=' . $rankings[0]['id']) ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($rankings[0]['nombre_completo']) ?>
                                            </a>
                                        </h5>
                                    <?php else: ?>
                                        <h5 class="card-title"><?= htmlspecialchars($rankings[0]['nombre_completo']) ?></h5>
                                    <?php endif; ?>
                                    <div class="points-display text-warning"><?= number_format($rankings[0]['ranking_puntos']) ?> pts</div>
                                    <small class="text-muted"><?= $rankings[0]['actividades_completadas'] ?> tareas</small>
                                </div>
                            </div>
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tercer lugar -->
                        <div class="col-md-4 order-md-3 mb-3">
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                <a href="<?= url('admin/edit_user.php?id=' . $rankings[2]['id']) ?>" class="text-decoration-none">
                            <?php endif; ?>
                            <div class="card ranking-card position-3 text-center">
                                <div class="card-body">
                                    <div class="position-badge bg-light text-dark mx-auto mb-3">3</div>
                                    <?php if (isset($rankings[2]['id']) && !empty($rankings[2]['id'])): ?>
                                        <h5 class="card-title">
                                            <a href="<?= url('profile.php?user_id=' . $rankings[2]['id']) ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($rankings[2]['nombre_completo']) ?>
                                            </a>
                                        </h5>
                                    <?php else: ?>
                                        <h5 class="card-title"><?= htmlspecialchars($rankings[2]['nombre_completo']) ?></h5>
                                    <?php endif; ?>
                                    <div class="points-display"><?= number_format($rankings[2]['ranking_puntos']) ?> pts</div>
                                    <small class="text-muted"><?= $rankings[2]['actividades_completadas'] ?> tareas</small>
                                </div>
                            </div>
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tabla completa del ranking -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list-ol me-2"></i>Ranking Completo
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="80">Posición</th>
                                            <th>Activista</th>
                                            <th width="150" class="text-center">Puntos Totales</th>
                                            <th width="120" class="text-center">Tareas Completadas</th>
                                            <th width="150" class="text-center">Mejor Tiempo de Respuesta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rankings as $index => $user): ?>
                                            <?php 
                                            $isCurrentUser = ($currentUser['rol'] === 'Activista' && 
                                                             $user['nombre_completo'] === $currentUser['nombre_completo']);
                                            $rowClass = $isCurrentUser ? 'current-user' : '';
                                            ?>
                                            <tr class="<?= $rowClass ?>">
                                                <td class="text-center">
                                                    <?php if ($index < 3): ?>
                                                        <?php 
                                                        $medals = ['🥇', '🥈', '🥉'];
                                                        echo $medals[$index] . ' ' . ($index + 1);
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= $index + 1 ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($user['id']) && !empty($user['id'])): ?>
                                                        <a href="<?= url('profile.php?user_id=' . $user['id']) ?>" class="text-decoration-none">

                                                            <strong><?= htmlspecialchars($user['nombre_completo']) ?></strong>
                                                        </a>
                                                    <?php else: ?>
                                                        <strong><?= htmlspecialchars($user['nombre_completo']) ?></strong>
                                                    <?php endif; ?>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="badge bg-primary ms-2">Tú</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="points-display"><?= number_format($user['ranking_puntos']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?= $user['actividades_completadas'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($user['mejor_tiempo_minutos']): ?>
                                                        <?php 
                                                        $horas = floor($user['mejor_tiempo_minutos'] / 60);
                                                        $minutos = $user['mejor_tiempo_minutos'] % 60;
                                                        ?>
                                                        <small class="text-muted">
                                                            <?php if ($horas > 0): ?>
                                                                <?= $horas ?>h <?= $minutos ?>m
                                                            <?php else: ?>
                                                                <?= $minutos ?>m
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Motivación para activistas -->
                <?php if ($currentUser['rol'] === 'Activista'): ?>
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h5 class="card-title text-success">
                            <i class="fas fa-rocket me-2"></i>¡Sube en el Ranking!
                        </h5>
                        <p class="card-text">
                            ¡El ranking se basa únicamente en tu tiempo de respuesta! 
                            Sube evidencia lo más rápido posible para ganar más puntos y subir posiciones.
                        </p>
                        <a href="<?= url('tasks/') ?>" class="btn btn-success">
                            <i class="fas fa-clipboard-list me-1"></i>Ver Mis Tareas
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>