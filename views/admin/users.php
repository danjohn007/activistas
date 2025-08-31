<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Activistas Digitales</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('users'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('admin/export_users.php?' . http_build_query($_GET)) ?>" 
                               class="btn btn-sm btn-outline-secondary" title="Exportar a Excel">
                                <i class="fas fa-download me-1"></i>Exportar
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

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                                       placeholder="Nombre, correo, teléfono, título actividad...">
                            </div>
                            <div class="col-md-2">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="">Todos los roles</option>
                                    <option value="SuperAdmin" <?= ($_GET['rol'] ?? '') === 'SuperAdmin' ? 'selected' : '' ?>>SuperAdmin</option>
                                    <option value="Gestor" <?= ($_GET['rol'] ?? '') === 'Gestor' ? 'selected' : '' ?>>Gestor</option>
                                    <option value="Líder" <?= ($_GET['rol'] ?? '') === 'Líder' ? 'selected' : '' ?>>Líder</option>
                                    <option value="Activista" <?= ($_GET['rol'] ?? '') === 'Activista' ? 'selected' : '' ?>>Activista</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="pendiente" <?= ($_GET['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="suspendido" <?= ($_GET['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                    <option value="desactivado" <?= ($_GET['estado'] ?? '') === 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                                <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas -->
                <?php if (!empty($stats)): ?>
                <div class="row mb-4">
                    <?php foreach ($stats as $rol => $data): ?>
                    <div class="col-md-3">
                        <div class="card text-center card-stats">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($rol) ?></h5>
                                <h3 class="text-primary"><?= $data['total'] ?></h3>
                                <small class="text-muted">
                                    Activos: <?= $data['activos'] ?> | 
                                    Pendientes: <?= $data['pendientes'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Lista de usuarios -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lista de Usuarios</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No se encontraron usuarios</h5>
                                <p class="text-muted">No hay usuarios que coincidan con los filtros seleccionados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Vigencia</th>
                                            <th>Líder</th>
                                            <th>Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($user['foto_perfil'])): ?>
                                                        <img src="<?= url('assets/uploads/profiles/' . $user['foto_perfil']) ?>" 
                                                             class="rounded-circle me-2" width="32" height="32" alt="Foto">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($user['nombre_completo']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($user['rol']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'activo' => 'success',
                                                    'pendiente' => 'warning',
                                                    'suspendido' => 'danger',
                                                    'desactivado' => 'secondary'
                                                ][$user['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($user['estado']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $vigenciaHasta = $user['vigencia_hasta'] ?? null;
                                                $currentUserRole = $_SESSION['user_role'] ?? '';
                                                $canEditVigencia = in_array($currentUserRole, ['SuperAdmin', 'Gestor']);
                                                ?>
                                                <?php if ($canEditVigencia): ?>
                                                    <input type="date" 
                                                           class="form-control form-control-sm vigencia-input" 
                                                           value="<?= $vigenciaHasta ?>" 
                                                           data-user-id="<?= $user['id'] ?>"
                                                           min="<?= date('Y-m-d') ?>"
                                                           style="max-width: 150px;">
                                                <?php else: ?>
                                                    <span class="<?= $vigenciaHasta ? ($vigenciaHasta < date('Y-m-d') ? 'text-danger' : 'text-success') : 'text-muted' ?>">
                                                        <?= $vigenciaHasta ? formatDate($vigenciaHasta) : 'Sin vigencia' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($user['lider_nombre'] ?? 'N/A') ?></td>
                                            <td><?= formatDate($user['fecha_registro']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('admin/edit_user.php?id=' . $user['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($user['estado'] === 'activo'): ?>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="changeUserStatus(<?= $user['id'] ?>, 'suspendido')" title="Suspender">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    <?php elseif ($user['estado'] === 'suspendido'): ?>
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="changeUserStatus(<?= $user['id'] ?>, 'activo')" title="Activar">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['estado'] !== 'desactivado'): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="changeUserStatus(<?= $user['id'] ?>, 'desactivado')" title="Desactivar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pagination -->
                        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Mostrando <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> 
                                a <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total_users']) ?> 
                                de <?= $pagination['total_users'] ?> usuarios
                            </div>
                            
                            <nav aria-label="Paginación de usuarios">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($pagination['has_prev']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']]))) ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start = max(1, $pagination['current_page'] - 2);
                                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                    
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => 1]))) ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $pagination['total_pages']): ?>
                                        <?php if ($end < $pagination['total_pages'] - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']]))) ?>"><?= $pagination['total_pages'] ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($pagination['has_next']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => $pagination['next_page']]))) ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Form para cambio de estado -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeUserStatus(userId, status) {
            if (confirm('¿Estás seguro de que quieres cambiar el estado de este usuario?')) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusValue').value = status;
                document.getElementById('statusForm').submit();
            }
        }

        // Handle vigencia updates
        document.addEventListener('DOMContentLoaded', function() {
            const vigenciaInputs = document.querySelectorAll('.vigencia-input');
            
            vigenciaInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const userId = this.dataset.userId;
                    const vigenciaHasta = this.value;
                    
                    // Show loading state
                    this.disabled = true;
                    this.style.opacity = '0.5';
                    
                    // AJAX request to update vigencia
                    fetch('<?= url("admin/update_vigencia.php") ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            vigencia_hasta: vigenciaHasta,
                            csrf_token: '<?= generateCSRFToken() ?>'
                        })
                    })
                    .then(response => {
                        // Check if response is ok before trying to parse JSON
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.disabled = false;
                        this.style.opacity = '1';
                        
                        if (data.success) {
                            // Show success message
                            showAlert('success', 'Vigencia actualizada correctamente');
                        } else {
                            // Show error and revert value
                            showAlert('danger', data.error || 'Error al actualizar vigencia');
                            this.value = data.original_value || '';
                        }
                    })
                    .catch(error => {
                        this.disabled = false;
                        this.style.opacity = '1';
                        
                        // Provide more specific error messages
                        let errorMessage = 'Error de conexión al actualizar vigencia';
                        if (error.message && error.message.includes('HTTP error')) {
                            errorMessage = 'Error del servidor al actualizar vigencia. Por favor, inténtelo de nuevo.';
                        } else if (error.message && error.message.includes('Failed to fetch')) {
                            errorMessage = 'Error de conexión de red. Verifique su conexión a internet.';
                        }
                        
                        showAlert('danger', errorMessage);
                        console.error('Error:', error);
                    });
                });
            });
        });

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