<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de Actividad - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
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
                        <h4><i class="fas fa-list me-2"></i>Tipos</h4>
                        <small>Actividades</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/admin.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('activity-types/') ?>">
                                <i class="fas fa-list me-2"></i>Tipos de Actividad
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('ranking/') ?>">
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
                        <i class="fas fa-list me-2"></i>Tipos de Actividad
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activity-types/create.php') ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Nuevo Tipo
                            </a>
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

                <!-- Lista de tipos de actividades -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gestión de Tipos de Actividades</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($activityTypes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activityTypes as $type): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($type['id']) ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($type['nombre']) ?></strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(substr($type['descripcion'], 0, 100)) ?>
                                                        <?= strlen($type['descripcion']) > 100 ? '...' : '' ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($type['activo']): ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($type['fecha_creacion'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= url('activity-types/edit.php?id=' . $type['id']) ?>" 
                                                           class="btn btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($type['activo']): ?>
                                                            <button type="button" class="btn btn-outline-warning" 
                                                                    onclick="toggleStatus(<?= $type['id'] ?>, 0)" title="Desactivar">
                                                                <i class="fas fa-pause"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="toggleStatus(<?= $type['id'] ?>, 1)" title="Activar">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteActivityType(<?= $type['id'] ?>, '<?= htmlspecialchars($type['nombre']) ?>')" 
                                                                title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay tipos de actividades registrados</h5>
                                <p class="text-muted">Comienza creando el primer tipo de actividad</p>
                                <a href="<?= url('activity-types/create.php') ?>" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Crear Primer Tipo
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-info-circle me-2"></i>Información importante</h6>
                    <ul class="mb-0">
                        <li>Los tipos de actividad son utilizados para categorizar las actividades del sistema</li>
                        <li>Al crear una actividad, la descripción del tipo se carga automáticamente</li>
                        <li>Los tipos inactivos no aparecen en los formularios de creación de actividades</li>
                        <li>No se pueden eliminar tipos que ya tienen actividades asociadas</li>
                    </ul>
                </div>
            </main>
        </div>
    </div>

    <!-- Formulario oculto para cambios de estado -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" id="statusAction">
        <input type="hidden" name="id" id="statusId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleStatus(id, newStatus) {
            const action = newStatus === 1 ? 'activar' : 'desactivar';
            const message = newStatus === 1 ? 'activar' : 'desactivar';
            
            if (confirm(`¿Está seguro de que desea ${message} este tipo de actividad?`)) {
                document.getElementById('statusId').value = id;
                document.getElementById('statusAction').value = action;
                document.getElementById('statusForm').action = '<?= url('activity-types/toggle.php') ?>';
                document.getElementById('statusForm').submit();
            }
        }
        
        function deleteActivityType(id, name) {
            if (confirm(`¿Está seguro de que desea ELIMINAR permanentemente el tipo de actividad "${name}"? Esta acción no se puede deshacer y fallará si hay actividades usando este tipo.`)) {
                // Show loading state
                const deleteBtn = event.target.closest('button');
                const originalContent = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteBtn.disabled = true;
                
                // Make AJAX call
                fetch('<?= url('api/activity-types.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        type_id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        // Reload page after 2 seconds to show the updated list
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showAlert('danger', data.error || 'Error al eliminar tipo de actividad');
                        // Restore button
                        deleteBtn.innerHTML = originalContent;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Error de conexión al eliminar tipo de actividad');
                    // Restore button
                    deleteBtn.innerHTML = originalContent;
                    deleteBtn.disabled = false;
                });
            }
        }
        
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('main');
            const firstChild = container.firstElementChild;
            container.insertBefore(alertDiv, firstChild.nextElementSibling);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>