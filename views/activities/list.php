<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Actividades - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-activity {
            transition: transform 0.2s;
        }
        .card-activity:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('activities'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Actividades</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Nueva Actividad
                            </a>
                        </div>
                        <?php if (isset($currentUser) && in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success" onclick="showMonthlyReportModal()">
                                <i class="fas fa-file-excel me-1"></i>Reporte Mensual
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo de Actividad</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach ($activityTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>" <?= ($_GET['tipo'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="programada" <?= ($_GET['estado'] ?? '') === 'programada' ? 'selected' : '' ?>>Programada</option>
                                    <option value="en_progreso" <?= ($_GET['estado'] ?? '') === 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                    <option value="completada" <?= ($_GET['estado'] ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                                    <option value="cancelada" <?= ($_GET['estado'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                                <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Limpiar
                                </a>
                            </div>
                            
                            <?php 
                            // Check if current user is SuperAdmin for advanced search
                            $auth = getAuth();
                            $currentUser = $auth->getCurrentUser();
                            ?>
                            <?php if (isset($currentUser) && $currentUser['rol'] === 'SuperAdmin'): ?>
                            <!-- Advanced Search for SuperAdmin -->
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-search-plus me-1"></i>Búsqueda Avanzada (SuperAdmin)
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <label for="search_title" class="form-label">Título de Actividad</label>
                                <input type="text" class="form-control" id="search_title" name="search_title" 
                                       placeholder="Buscar por título..." 
                                       value="<?= htmlspecialchars($_GET['search_title'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_name" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="search_name" name="search_name" 
                                       placeholder="Buscar por nombre..." 
                                       value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="search_email" name="search_email" 
                                       placeholder="Buscar por correo..." 
                                       value="<?= htmlspecialchars($_GET['search_email'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="search_phone" name="search_phone" 
                                       placeholder="Buscar por teléfono..." 
                                       value="<?= htmlspecialchars($_GET['search_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_lider_id" class="form-label">Filtrar por Líder</label>
                                <select class="form-select" id="filter_lider_id" name="filter_lider_id">
                                    <option value="">Todos los líderes</option>
                                    <?php if (isset($leaders) && !empty($leaders)): ?>
                                        <?php foreach ($leaders as $leader): ?>
                                            <option value="<?= $leader['id'] ?>" <?= ($_GET['filter_lider_id'] ?? '') == $leader['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($leader['nombre_completo']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Incluye actividades del líder y su equipo</small>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Lista de actividades -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lista de Actividades</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes actividades registradas</h5>
                                <p class="text-muted">Comienza registrando tu primera actividad.</p>
                                <a href="<?= url('activities/create.php') ?>" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Crear Primera Actividad
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <?php if (isset($currentUser) && in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                                            <th>Usuario</th>
                                            <?php endif; ?>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Evidencias</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($activity['titulo']) ?></strong>
                                                <?php if (!empty($activity['descripcion'])): ?>
                                                    <br><small class="text-muted">
                                                        <?= htmlspecialchars(substr($activity['descripcion'], 0, 100)) ?>
                                                        <?= strlen($activity['descripcion']) > 100 ? '...' : '' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <?php if (isset($currentUser) && in_array($currentUser['rol'], ['SuperAdmin', 'Gestor', 'Líder'])): ?>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <strong><?= htmlspecialchars($activity['usuario_nombre']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars($activity['usuario_correo']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                            <td><?= htmlspecialchars($activity['tipo_nombre']) ?></td>
                                            <td><?= formatDate($activity['fecha_actividad'], 'd/m/Y') ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'completada' => 'success',
                                                    'en_progreso' => 'warning',
                                                    'programada' => 'primary',
                                                    'cancelada' => 'danger'
                                                ][$activity['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $activity['estado'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($activity['estado'] === 'completada' && !empty($activity['evidences'])): ?>
                                                    <div class="evidence-summary">
                                                        <div class="mb-2">
                                                            <strong class="text-primary">
                                                                <i class="fas fa-file-alt me-1"></i>
                                                                <?= count($activity['evidences']) ?> evidencia<?= count($activity['evidences']) != 1 ? 's' : '' ?>
                                                            </strong>
                                                        </div>
                                                        <?php foreach ($activity['evidences'] as $evidence): ?>
                                                            <div class="evidence-item mb-2">
                                                                <?php if ($evidence['tipo_evidencia'] === 'foto' && !empty($evidence['archivo'])): ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="<?= url('assets/uploads/evidencias/' . htmlspecialchars($evidence['archivo'])) ?>" 
                                                                                 class="evidence-thumbnail me-2" 
                                                                                 alt="Evidencia"
                                                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                                            <small class="text-muted">
                                                                                <i class="fas fa-image me-1"></i>Foto
                                                                            </small>
                                                                        </div>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . htmlspecialchars($evidence['archivo'])) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Ver imagen">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif ($evidence['tipo_evidencia'] === 'video' && !empty($evidence['archivo'])): ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-video me-1"></i>Video: <?= htmlspecialchars(basename($evidence['archivo'])) ?>
                                                                        </small>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . htmlspecialchars($evidence['archivo'])) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Ver/Descargar video">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif ($evidence['tipo_evidencia'] === 'audio' && !empty($evidence['archivo'])): ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-music me-1"></i>Audio: <?= htmlspecialchars(basename($evidence['archivo'])) ?>
                                                                        </small>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . htmlspecialchars($evidence['archivo'])) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Escuchar/Descargar audio">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif ($evidence['tipo_evidencia'] === 'comentario' && !empty($evidence['contenido'])): ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-comment me-1"></i>
                                                                            <?= htmlspecialchars(substr($evidence['contenido'], 0, 50)) ?>
                                                                            <?= strlen($evidence['contenido']) > 50 ? '...' : '' ?>
                                                                        </small>
                                                                        <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" 
                                                                           class="btn btn-sm btn-outline-info" 
                                                                           title="Ver comentario completo">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php elseif (!empty($evidence['archivo'])): ?>
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-file me-1"></i><?= ucfirst($evidence['tipo_evidencia']) ?>: <?= htmlspecialchars(basename($evidence['archivo'])) ?>
                                                                        </small>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . htmlspecialchars($evidence['archivo'])) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank"
                                                                           title="Ver/Descargar archivo">
                                                                            <i class="fas fa-external-link-alt"></i>
                                                                        </a>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-file me-1"></i><?= ucfirst($evidence['tipo_evidencia']) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($activity['estado'] === 'completada'): ?>
                                                    <small class="text-muted">Sin evidencias</small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= url('activities/edit.php?id=' . $activity['id']) ?>" 
                                                       class="btn btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pagination -->
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                            <div class="mt-4">
                                <nav aria-label="Navegación de páginas">
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous button -->
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">
                                                    <i class="fas fa-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Page numbers -->
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        ?>
                                        
                                        <?php if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => 1]))) ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $totalPages]))) ?>"><?= $totalPages ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Next button -->
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= url('activities/?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">
                                                    Siguiente <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <!-- Page info -->
                                <div class="text-center text-muted mt-2">
                                    Mostrando página <?= $page ?> de <?= $totalPages ?> 
                                    (<?= $totalActivities ?> actividades en total)
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Monthly Report Modal -->
    <div class="modal fade" id="monthlyReportModal" tabindex="-1" aria-labelledby="monthlyReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="monthlyReportModalLabel">
                        <i class="fas fa-file-excel me-2"></i>Exportar Reporte Mensual
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="monthlyReportForm">
                        <div class="mb-3">
                            <label for="reportMonth" class="form-label">Seleccionar Mes</label>
                            <input type="month" class="form-control" id="reportMonth" name="reportMonth" 
                                   value="<?= date('Y-m') ?>" required>
                            <div class="form-text">Por defecto está seleccionado el mes actual</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="exportMonthlyReport()">
                        <i class="fas fa-download me-1"></i>Exportar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showMonthlyReportModal() {
            const modal = new bootstrap.Modal(document.getElementById('monthlyReportModal'));
            modal.show();
        }
        
        function exportMonthlyReport() {
            const month = document.getElementById('reportMonth').value;
            if (!month) {
                alert('Por favor selecciona un mes');
                return;
            }
            
            // Create form to download the report
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('api/export_monthly_report.php') ?>';
            
            const monthInput = document.createElement('input');
            monthInput.type = 'hidden';
            monthInput.name = 'month';
            monthInput.value = month;
            form.appendChild(monthInput);
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= generateCSRFToken() ?>';
            form.appendChild(csrfInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('monthlyReportModal'));
            modal.hide();
        }
    </script>
</body>
</html>