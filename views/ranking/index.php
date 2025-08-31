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
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <h4><i class="fas fa-trophy me-2"></i>Ranking</h4>
                        <small><?= htmlspecialchars($currentUser['nombre_completo']) ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/' . strtolower($currentUser['rol']) . '.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <?php if ($currentUser['rol'] === 'Activista'): ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Mis Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('tasks/') ?>">
                                <i class="fas fa-clipboard-list me-2"></i>Tareas Pendientes
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Actividades
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('ranking/') ?>">
                                <i class="fas fa-trophy me-2"></i>Ranking
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

                <!-- Descripción del ranking -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= htmlspecialchars($description) ?>
                </div>

                <!-- Información del sistema de puntos -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calculator me-2"></i>Sistema de Puntos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-check-circle text-success me-2"></i>Tareas Completadas</h6>
                                <p class="mb-3"><strong>200 puntos</strong> por cada tarea completada exitosamente.</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock text-warning me-2"></i>Tiempo de Respuesta</h6>
                                <p class="mb-0">Hasta <strong>800 puntos</strong> por mejor tiempo de respuesta. 
                                Se resta 1 punto por cada posición posterior en tiempo (puede llegar a valores negativos).</p>
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
                                            <th width="150" class="text-center">Puntos</th>
                                            <th width="120" class="text-center">Tareas</th>
                                            <th width="150" class="text-center">Mejor Tiempo</th>
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