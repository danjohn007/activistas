<?php
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Acceso directo no permitido');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cortes de Periodo - Activistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .estado-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .estado-activo {
            background-color: #d1f2eb;
            color: #0f5132;
        }
        .estado-cerrado {
            background-color: #d3d3d4;
            color: #41464b;
        }
        .bulk-actions {
            display: none;
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #fff;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .bulk-actions.show {
            display: block;
        }
        .checkbox-cell {
            width: 40px;
        }
        .grupo-cortes {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
        .grupo-cortes:hover {
            background-color: #e9ecef;
        }
        .corte-hijo {
            background-color: #fff;
            border-left: 3px solid #6c757d;
        }
        .collapse-icon {
            transition: transform 0.3s;
        }
        .collapse-icon.rotated {
            transform: rotate(90deg);
        }
        .grupo-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('cortes'); 
            ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h2><i class="fas fa-chart-line me-2"></i>Cortes de Periodo</h2>
                        <p class="text-muted mb-0">Reportes históricos con datos congelados</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="create.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Nuevo Corte
                        </a>
                    </div>
                </div>

            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message'], $_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="index.php" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-search me-1"></i>Buscar por nombre</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Buscar corte..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="activo" <?php echo ($_GET['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="cerrado" <?php echo ($_GET['estado'] ?? '') === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" 
                                   value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" 
                                   value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-secondary me-2">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
            <div class="bulk-actions" id="bulkActionsBar">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span id="selectedCount">0</span> corte(s) seleccionado(s)
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger" onclick="deleteSelected()">
                            <i class="fas fa-trash me-1"></i>Eliminar Seleccionados
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($cortes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay cortes de periodo creados</p>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Corte
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Info de resultados y paginación -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">
                                Mostrando <?php echo count($cortes); ?> de <?php echo $totalCortes; ?> cortes
                            </div>
                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Seleccionar Todos
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <form id="deleteForm" method="POST" action="delete_multiple.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                            <th class="checkbox-cell">
                                                <input type="checkbox" id="selectAllCheckbox" 
                                                       class="form-check-input" onclick="toggleSelectAll(this)">
                                            </th>
                                            <?php endif; ?>
                                            <th>Nombre</th>
                                            <th>Periodo</th>
                                            <th>Activistas</th>
                                            <th>Promedio Cumplimiento</th>
                                            <th>Estado</th>
                                            <th>Creado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cortes as $item): ?>
                                            <?php if ($item['es_grupo']): ?>
                                                <!-- Fila de Grupo -->
                                                <tr class="grupo-cortes" style="cursor: pointer;" onclick="toggleGrupo('grupo-<?php echo $item['cortes'][0]['id']; ?>')">
                                                    <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                                    <td class="checkbox-cell">
                                                        <i class="fas fa-chevron-right collapse-icon" id="icon-grupo-<?php echo $item['cortes'][0]['id']; ?>"></i>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <strong><i class="fas fa-layer-group me-2"></i><?php echo htmlspecialchars($item['nombre_grupo']); ?></strong>
                                                        <span class="badge grupo-badge ms-2"><?php echo $item['cantidad']; ?> cortes</span>
                                                        <br><small class="text-muted">Corte masivo - Clic para expandir</small>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <i class="far fa-calendar"></i> 
                                                            <?php echo date('d/m/Y', strtotime($item['fecha_inicio'])); ?>
                                                            <br>al <?php echo date('d/m/Y', strtotime($item['fecha_fin'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $item['total_activistas']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $promedio = $item['promedio_cumplimiento'];
                                                            $badgeClass = $promedio >= 80 ? 'success' : ($promedio >= 60 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                                            <?php echo number_format($promedio, 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="estado-badge estado-<?php echo $item['estado']; ?>">
                                                            <?php echo ucfirst($item['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i', strtotime($item['fecha_creacion'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">Grupo</span>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Cortes hijos (ocultos por defecto) -->
                                                <?php foreach ($item['cortes'] as $corteHijo): ?>
                                                <tr class="corte-hijo collapse" id="grupo-<?php echo $item['cortes'][0]['id']; ?>">
                                                    <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                                    <td class="checkbox-cell">
                                                        <input type="checkbox" name="corte_ids[]" 
                                                               value="<?php echo $corteHijo['id']; ?>" 
                                                               class="form-check-input corte-checkbox"
                                                               onchange="updateBulkActions()">
                                                    </td>
                                                    <?php endif; ?>
                                                    <td class="ps-5">
                                                        <i class="fas fa-angle-right me-2 text-muted"></i>
                                                        <strong><?php echo htmlspecialchars($corteHijo['nombre']); ?></strong>
                                                        <?php if (!empty($corteHijo['descripcion'])): ?>
                                                            <br><small class="text-muted ps-4"><?php echo htmlspecialchars($corteHijo['descripcion']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <i class="far fa-calendar"></i> 
                                                            <?php echo date('d/m/Y', strtotime($corteHijo['fecha_inicio'])); ?>
                                                            <br>al <?php echo date('d/m/Y', strtotime($corteHijo['fecha_fin'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $corteHijo['total_activistas'] ?? 0; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $promedio = $corteHijo['promedio_cumplimiento'] ?? 0;
                                                            $badgeClass = $promedio >= 80 ? 'success' : ($promedio >= 60 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                                            <?php echo number_format($promedio, 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="estado-badge estado-<?php echo $corteHijo['estado']; ?>">
                                                            <?php echo ucfirst($corteHijo['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i', strtotime($corteHijo['fecha_creacion'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <a href="detail.php?id=<?php echo $corteHijo['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="Ver Detalle">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                                        <form method="POST" action="delete.php" style="display: inline;" 
                                                              onsubmit="return confirm('¿Eliminar este corte? Esta acción no se puede deshacer.');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="corte_id" value="<?php echo $corteHijo['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <!-- Corte individual -->
                                                <?php $corte = $item['corte']; ?>
                                        <tr>
                                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                            <td class="checkbox-cell">
                                                <input type="checkbox" name="corte_ids[]" 
                                                       value="<?php echo $corte['id']; ?>" 
                                                       class="form-check-input corte-checkbox"
                                                       onchange="updateBulkActions()">
                                            </td>
                                            <?php endif; ?>
                                        <td>
                                            <strong><?php echo htmlspecialchars($corte['nombre']); ?></strong>
                                            <?php if (!empty($corte['descripcion'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($corte['descripcion']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($corte['grupo_nombre'])): ?>
                                                <br><span class="badge bg-primary"><i class="fas fa-users"></i> <?php echo htmlspecialchars($corte['grupo_nombre']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($corte['activista_nombre'])): ?>
                                                <br><span class="badge bg-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($corte['activista_nombre']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($corte['actividad_nombre'])): ?>
                                                <br><span class="badge bg-secondary"><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($corte['actividad_nombre']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="far fa-calendar"></i> 
                                                <?php echo date('d/m/Y', strtotime($corte['fecha_inicio'])); ?>
                                                <br>al <?php echo date('d/m/Y', strtotime($corte['fecha_fin'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $corte['total_activistas'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $promedio = $corte['promedio_cumplimiento'] ?? 0;
                                                $badgeClass = $promedio >= 80 ? 'success' : ($promedio >= 60 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo number_format($promedio, 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="estado-badge estado-<?php echo $corte['estado']; ?>">
                                                <?php echo ucfirst($corte['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($corte['fecha_creacion'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="detail.php?id=<?php echo $corte['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Ver Detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($corte['estado'] === 'activo' && $currentUser['rol'] === 'SuperAdmin'): ?>
                                            <form method="POST" action="cerrar.php" style="display: inline;" 
                                                  onsubmit="return confirm('¿Cerrar este corte? No podrá ser modificado.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="corte_id" value="<?php echo $corte['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Cerrar Corte">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                            <form method="POST" action="delete.php" style="display: inline;" 
                                                  onsubmit="return confirm('¿Eliminar este corte? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="corte_id" value="<?php echo $corte['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        </form>
                        
                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                $queryParams = $_GET;
                                unset($queryParams['page']);
                                $queryString = http_build_query($queryParams);
                                $baseUrl = 'index.php?' . ($queryString ? $queryString . '&' : '');
                                ?>
                                
                                <!-- Previous -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <!-- Page numbers -->
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                
                                if ($start > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=1">1</a>
                                    </li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $totalPages; ?>">
                                            <?php echo $totalPages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Sobre los Cortes de Periodo:</strong>
                Los datos de cada corte se congelan al momento de su creación y nunca se actualizan, 
                permitiendo consultas históricas precisas sin importar las entregas futuras.
            </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleGrupo(grupoId) {
            const elementos = document.querySelectorAll('#' + grupoId);
            const icon = document.getElementById('icon-' + grupoId);
            
            elementos.forEach(el => {
                if (el.classList.contains('show')) {
                    el.classList.remove('show');
                    if (icon) icon.classList.remove('rotated');
                } else {
                    el.classList.add('show');
                    if (icon) icon.classList.add('rotated');
                }
            });
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.corte-checkbox:checked');
            const count = checkboxes.length;
            const bulkBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');
            
            if (count > 0) {
                bulkBar.classList.add('show');
                selectedCount.textContent = count;
            } else {
                bulkBar.classList.remove('show');
            }
            
            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.corte-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
            }
        }
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.corte-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('.corte-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = true;
            }
            updateBulkActions();
        }
        
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.corte-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkActions();
        }
        
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.corte-checkbox:checked');
            const count = checkboxes.length;
            
            if (count === 0) {
                alert('Por favor selecciona al menos un corte para eliminar');
                return;
            }
            
            if (confirm(`¿Estás seguro de eliminar ${count} corte(s)? Esta acción no se puede deshacer.`)) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
