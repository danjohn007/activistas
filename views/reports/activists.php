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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .report-card {
            transition: transform 0.2s;
            border-radius: 10px;
        }
        .report-card:hover {
            transform: translateY(-2px);
        }
        .performance-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        .performance-excellent {
            background-color: #28a745;
            color: white;
        }
        .performance-good {
            background-color: #ffc107;
            color: black;
        }
        .performance-poor {
            background-color: #dc3545;
            color: white;
        }
        .search-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .activist-name-link {
            color: #0d6efd;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .activist-name-link:hover {
            color: #0a58ca;
            text-decoration: underline;
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('activist_report'); 
            ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2">
                            <i class="fas fa-chart-bar text-primary me-2"></i><?= htmlspecialchars($title) ?>
                            <?php if ($snapshotMode): ?>
                                <span class="badge bg-info ms-2">
                                    <i class="fas fa-camera"></i> Snapshot: <?= htmlspecialchars($snapshotData['nombre']) ?>
                                </span>
                            <?php endif; ?>
                        </h1>
                        <?php if ($userRole === 'Líder'): ?>
                            <?php if (empty($availableCortes)): ?>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>No hay cortes disponibles.</strong> 
                                    El SuperAdministrador aún no ha creado ningún corte para tu grupo.
                                    <?php if (empty($currentUser['grupo_id'])): ?>
                                        <br><small class="text-danger">No tienes un grupo asignado. Contacta al administrador.</small>
                                    <?php else: ?>
                                        <br><small>Tu grupo: <strong><?= htmlspecialchars($currentUser['grupo_nombre'] ?? 'ID: ' . $currentUser['grupo_id']) ?></strong></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary" onclick="exportReport()">
                                <i class="fas fa-download me-1"></i>Exportar
                            </button>
                        </div>
                        <?php if (in_array($userRole, ['SuperAdmin', 'Gestor'])): ?>
                        <div class="btn-group me-2">
                            <a href="<?= url('cortes/index.php') ?>" class="btn btn-outline-info">
                                <i class="fas fa-history me-1"></i>Ver Snapshots Guardados
                            </a>
                        </div>
                        <?php if (!$snapshotMode): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#captureSnapshotModal">
                                <i class="fas fa-camera me-1"></i>Capturar Snapshot
                            </button>
                        </div>
                        <?php if ($userRole === 'SuperAdmin'): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#massiveSnapshotModal">
                                <i class="fas fa-layer-group me-1"></i>Cortes Masivos (Todos los Grupos)
                            </button>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Cortes disponibles para Líderes -->
                <?php if ($userRole === 'Líder' && !empty($snapshotData)): ?>
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <div class="card border-success shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-camera me-2"></i>Se ha hecho un corte
                                </h6>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title text-primary mb-3">
                                    <?= htmlspecialchars($snapshotData['nombre']) ?>
                                </h5>
                                
                                <div class="alert alert-warning mb-3">
                                    <strong><i class="fas fa-calendar-check me-2"></i>Rango de fechas del corte:</strong>
                                    <div class="mt-2">
                                        El administrador hizo un corte del periodo comprendido del 
                                        <strong><?= date('d/m/Y', strtotime($snapshotData['fecha_inicio'])) ?></strong>
                                        al
                                        <strong><?= date('d/m/Y', strtotime($snapshotData['fecha_fin'])) ?></strong>
                                    </div>
                                </div>
                                
                                <?php if (!empty($snapshotData['descripcion'])): ?>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-quote-left me-1"></i>
                                        <?= htmlspecialchars($snapshotData['descripcion']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="alert alert-info mb-3">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <strong><i class="fas fa-calendar-alt me-2"></i>Periodo del Corte:</strong>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span class="badge bg-primary fs-6 px-3 py-2">
                                                    <?= date('d/m/Y', strtotime($snapshotData['fecha_inicio'])) ?>
                                                </span>
                                                <i class="fas fa-arrow-right mx-3 text-primary"></i>
                                                <span class="badge bg-primary fs-6 px-3 py-2">
                                                    <?= date('d/m/Y', strtotime($snapshotData['fecha_fin'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <strong><i class="fas fa-clock text-success me-2"></i>Fecha y Hora del Corte:</strong>
                                        </div>
                                        <div class="col-12">
                                            <h5 class="text-success mb-0">
                                                <?= date('d/m/Y', strtotime($snapshotData['fecha_creacion'])) ?>
                                                <span class="text-muted">a las</span>
                                                <?= date('H:i', strtotime($snapshotData['fecha_creacion'])) ?> hrs
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-user-shield me-1"></i>Creado por:
                                        </small>
                                        <br>
                                        <strong><?= htmlspecialchars($snapshotData['creador_nombre'] ?? 'Sistema') ?></strong>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i>Activistas:
                                        </small>
                                        <br>
                                        <span class="badge bg-secondary fs-6"><?= $snapshotData['total_activistas'] ?? 0 ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Snapshot Selector -->
                <?php if (!empty($availableCortes)): ?>
                <div class="alert alert-light border mb-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-eye fa-lg text-primary"></i>
                        </div>
                        <div class="col">
                            <strong>Modo de visualización:</strong>
                            <select class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="changeViewMode(this.value)">
                                <option value="">Tiempo Real (Actual)</option>
                                <optgroup label="Snapshots Guardados">
                                    <?php foreach ($availableCortes as $corte): ?>
                                        <option value="<?= $corte['id'] ?>" <?= $corteId == $corte['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($corte['nombre']) ?> 
                                            (<?= date('d/m/Y', strtotime($corte['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($corte['fecha_fin'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <?php if ($snapshotMode): ?>
                        <div class="col-auto">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-lock"></i> Datos Congelados al <?= date('d/m/Y H:i', strtotime($snapshotData['fecha_creacion'])) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php 
                // Para líderes: solo mostrar datos si hay cortes disponibles
                $showData = true;
                if ($userRole === 'Líder' && empty($availableCortes)) {
                    $showData = false;
                }
                ?>
                
                <?php if ($showData): ?>
                <!-- Search and Filter Form -->
                <div class="search-form">
                    <h5 class="mb-3"><i class="fas fa-search me-2"></i>Filtros de Búsqueda</h5>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="search_name" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="search_name" name="search_name" 
                                       value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>" 
                                       placeholder="Buscar por nombre">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="search_email" class="form-label">Correo</label>
                                <input type="email" class="form-control" id="search_email" name="search_email" 
                                       value="<?= htmlspecialchars($_GET['search_email'] ?? '') ?>" 
                                       placeholder="Buscar por correo">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="search_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="search_phone" name="search_phone" 
                                       value="<?= htmlspecialchars($_GET['search_phone'] ?? '') ?>" 
                                       placeholder="Buscar por teléfono">
                            </div>
                            
                            <!-- Filtros de fecha - Solo para SuperAdmin y Gestor -->
                            <?php if (in_array($userRole, ['SuperAdmin', 'Gestor'])): ?>
                            <div class="col-md-3 mb-3">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                            </div>
                            <?php endif; ?>
                            
                            <!-- Group filtering - SuperAdmin only -->
                            <?php if ($_SESSION['user_role'] === 'SuperAdmin' && !empty($groups)): ?>
                            <div class="col-md-3 mb-3">
                                <label for="grupo_id" class="form-label">Filtrar por Grupo</label>
                                <select class="form-select" id="grupo_id" name="grupo_id">
                                    <option value="">Todos los grupos</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['id'] ?>" 
                                                <?= ($_GET['grupo_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($group['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Leader filtering - SuperAdmin and Gestor -->
                            <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor']) && !empty($leaders)): ?>
                            <div class="col-md-3 mb-3">
                                <label for="filter_lider_id" class="form-label">Filtrar por Líder</label>
                                <select class="form-select" id="filter_lider_id" name="filter_lider_id">
                                    <option value="">Todos los líderes</option>
                                    <?php foreach ($leaders as $leader): ?>
                                        <option value="<?= $leader['id'] ?>" 
                                                <?= ($_GET['filter_lider_id'] ?? '') == $leader['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($leader['nombre_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                                <a href="?" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Statistics Summary -->
                <div class="row mb-4">
                    <?php
                    $totalActivists = count($reportData);
                    $excellentPerformers = count(array_filter($reportData, function($user) { return $user['porcentaje_cumplimiento'] >= 80; }));
                    $averageCompletion = $totalActivists > 0 ? array_sum(array_column($reportData, 'porcentaje_cumplimiento')) / $totalActivists : 0;
                    $totalTasks = array_sum(array_column($reportData, 'tareas_completadas'));
                    ?>
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="text-primary"><?= $totalActivists ?></h4>
                                <p class="mb-0">Total Activistas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-star fa-2x text-success mb-2"></i>
                                <h4 class="text-success"><?= $excellentPerformers ?></h4>
                                <p class="mb-0">Excelente Rendimiento</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                                <h4 class="text-info"><?= number_format($averageCompletion, 1) ?>%</h4>
                                <p class="mb-0">Promedio Cumplimiento</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                                <h4 class="text-warning"><?= $totalTasks ?></h4>
                                <p class="mb-0">Tareas Completadas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>Detalle de Rendimiento de Activistas
                            <span class="badge bg-secondary ms-2"><?= count($reportData) ?> resultados</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reportData)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No se encontraron activistas con los criterios seleccionados</h5>
                                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><i class="fas fa-user me-1"></i>Activista</th>
                                            <th><i class="fas fa-envelope me-1"></i>Contacto</th>
                                            <?php if (in_array($userRole, ['SuperAdmin', 'Gestor'])): ?>
                                                <th><i class="fas fa-user-tie me-1"></i>Líder</th>
                                            <?php endif; ?>
                                            <th><i class="fas fa-clipboard-list me-1"></i>Tareas</th>
                                            <th><i class="fas fa-check-circle me-1"></i>Completadas</th>
                                            <th><i class="fas fa-percentage me-1"></i>% Cumplimiento</th>
                                            <th><i class="fas fa-trophy me-1"></i>Puntos</th>
                                            <th><i class="fas fa-chart-line me-1"></i>Rendimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $user): ?>
                                            <?php
                                            $performanceClass = 'performance-poor';
                                            $performanceText = 'Bajo';
                                            if ($user['porcentaje_cumplimiento'] >= 80) {
                                                $performanceClass = 'performance-excellent';
                                                $performanceText = 'Excelente';
                                            } elseif ($user['porcentaje_cumplimiento'] >= 60) {
                                                $performanceClass = 'performance-good';
                                                $performanceText = 'Bueno';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <a href="<?= url('reports/activist_tasks.php?user_id=' . $user['id']) ?>" 
                                                               class="activist-name-link" 
                                                               title="Clic para ver detalle de tareas">
                                                                <?= htmlspecialchars($user['nombre_completo']) ?>
                                                                <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                                            </a>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($user['rol'] ?? 'Activista') ?></small>
                                                        </div>
                                                        <a href="<?= url('reports/activist_tasks.php?user_id=' . $user['id']) ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Ver detalle de tareas">
                                                            <i class="fas fa-list-check me-1"></i>Detalle
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($user['email'] ?? 'Sin email') ?><br>
                                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($user['telefono'] ?? 'Sin teléfono') ?>
                                                    </small>
                                                </td>
                                                <?php if (in_array($userRole, ['SuperAdmin', 'Gestor'])): ?>
                                                    <td>
                                                        <small><?= htmlspecialchars($user['lider_nombre'] ?? 'Sin líder') ?></small>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="badge bg-info"><?= $user['total_tareas_asignadas'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?= $user['tareas_completadas'] ?></span>
                                                </td>
                                                <td>
                                                    <strong class="<?= $user['porcentaje_cumplimiento'] >= 70 ? 'text-success' : ($user['porcentaje_cumplimiento'] >= 50 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= number_format($user['porcentaje_cumplimiento'], 1) ?>%
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= number_format($user['puntos_actuales']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge performance-badge <?= $performanceClass ?>">
                                                        <?= $performanceText ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; // Fin de $showData ?>
            </main>
        </div>
    </div>

    <!-- Modal: Capture Snapshot -->
    <div class="modal fade" id="captureSnapshotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('cortes/create.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="from_report" value="1">
                    <?php if (!empty($_GET['fecha_desde'])): ?>
                        <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_desde']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($_GET['fecha_hasta'])): ?>
                        <input type="hidden" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_hasta']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($_GET['grupo_id'])): ?>
                        <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-camera me-2"></i>Capturar Snapshot
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Se guardará un snapshot con los datos actuales y filtros aplicados.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre del Snapshot</label>
                            <input type="text" name="nombre" class="form-control" 
                                   placeholder="Ej: Reporte Diciembre 2025" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" 
                                   value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" 
                                   value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción (Opcional)</label>
                            <textarea name="descripcion" class="form-control" rows="2" 
                                      placeholder="Notas sobre este snapshot"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Guardar Snapshot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cortes Masivos para Todos los Grupos -->
    <div class="modal fade" id="massiveSnapshotModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="<?= url('cortes/create_massive.php') ?>" id="massiveSnapshotForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-layer-group me-2"></i>Crear Cortes Masivos (Todos los Grupos)
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>¿Cómo funciona?
                            </h6>
                            <p class="mb-0">
                                Esta función creará <strong>un corte para cada grupo activo</strong> automáticamente, 
                                todos con las mismas fechas que especifiques. Esto ahorra tiempo al no tener que 
                                crear los cortes uno por uno.
                            </p>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle me-2"></i>Resultado esperado:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Se creará 1 corte por cada grupo activo en el sistema</li>
                                <li>Cada corte tendrá el nombre base + el nombre del grupo</li>
                                <li>Todos los cortes tendrán las mismas fechas de inicio y fin</li>
                                <li>Los líderes verán automáticamente su corte correspondiente</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Nombre Base del Corte</label>
                            <input type="text" name="nombre" class="form-control" 
                                   placeholder="Ej: Corte Diciembre 2025" required>
                            <small class="text-muted">
                                Se agregará automáticamente " - [Nombre del Grupo]" a cada corte
                            </small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Inicio del Periodo</label>
                                    <input type="date" name="fecha_inicio" class="form-control" 
                                           value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Fin del Periodo</label>
                                    <input type="date" name="fecha_fin" class="form-control" 
                                           value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? date('Y-m-d')) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción (Opcional)</label>
                            <textarea name="descripcion" class="form-control" rows="2" 
                                      placeholder="Descripción que se aplicará a todos los cortes"></textarea>
                        </div>
                        
                        <div class="alert alert-success mb-0">
                            <strong><i class="fas fa-clock me-2"></i>Tiempo estimado:</strong>
                            Este proceso puede tardar unos segundos dependiendo del número de grupos y activistas.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="massiveSnapshotBtn">
                            <i class="fas fa-layer-group me-1"></i>Crear Cortes para Todos los Grupos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeViewMode(corteId) {
            const url = new URL(window.location);
            if (corteId) {
                url.searchParams.set('corte_id', corteId);
            } else {
                url.searchParams.delete('corte_id');
            }
            window.location.href = url.toString();
        }
        
        function exportReport() {
            // Simple CSV export functionality
            const table = document.querySelector('table');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = 'reporte_activistas_' + new Date().toISOString().split('T')[0] + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Manejar envío del formulario de cortes masivos
        document.getElementById('massiveSnapshotForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('massiveSnapshotBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando cortes...';
        });
    </script>
</body>
</html>