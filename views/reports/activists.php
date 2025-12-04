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
                    <h1 class="h2">
                        <i class="fas fa-chart-bar text-primary me-2"></i><?= htmlspecialchars($title) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary" onclick="exportReport()">
                                <i class="fas fa-download me-1"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>

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
                                                            <small class="text-muted"><?= htmlspecialchars($user['rol']) ?></small>
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
                                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($user['email']) ?><br>
                                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($user['telefono']) ?>
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>