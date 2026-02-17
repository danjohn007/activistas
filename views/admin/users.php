<?php
// Incluir las dependencias necesarias
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
?>
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
                                    <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                        <option value="eliminado" <?= ($_GET['estado'] ?? '') === 'eliminado' ? 'selected' : '' ?>>Eliminado</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="municipio" class="form-label">Municipio</label>
                                <select class="form-select" id="municipio" name="municipio">
                                    <option value="">Todos los municipios</option>
                                    <option value="Sin especificar" <?= ($_GET['municipio'] ?? '') === 'Sin especificar' ? 'selected' : '' ?>>Sin especificar</option>
                                    <?php foreach (getMunicipiosQueretaro() as $municipio): ?>
                                        <option value="<?= htmlspecialchars($municipio) ?>" <?= ($_GET['municipio'] ?? '') === $municipio ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($municipio) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
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

                <?php if (($currentUser['rol'] ?? '') === 'SuperAdmin' && !empty($municipioStats)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Distribución por Municipio</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Municipio</th>
                                        <th class="text-end">Usuarios</th>
                                        <th class="text-end">Líderes</th>
                                        <th class="text-end">Activistas</th>
                                        <th class="text-end">Activos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($municipioStats as $municipioRow): ?>
                                        <?php
                                            $municipioValue = $municipioRow['municipio'];
                                            $baseQuery = $_GET;
                                            $baseQuery['municipio'] = $municipioValue;
                                            $baseQuery['page'] = 1;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($municipioRow['municipio']) ?></td>
                                            <td class="text-end">
                                                <a href="<?= url('admin/users.php?' . http_build_query(array_merge($baseQuery, ['rol' => '']))) ?>">
                                                    <?= (int)$municipioRow['total_usuarios'] ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('admin/users.php?' . http_build_query(array_merge($baseQuery, ['rol' => 'Líder']))) ?>">
                                                    <?= (int)$municipioRow['total_lideres'] ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('admin/users.php?' . http_build_query(array_merge($baseQuery, ['rol' => 'Activista']))) ?>">
                                                    <?= (int)$municipioRow['total_activistas'] ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('admin/users.php?' . http_build_query(array_merge($baseQuery, ['estado' => 'activo']))) ?>">
                                                    <?= (int)$municipioRow['total_activos'] ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                                            <th>Municipio</th>
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
                                                ?>
                                                <span class="<?= $vigenciaHasta ? ($vigenciaHasta < date('Y-m-d') ? 'text-danger' : 'text-success') : 'text-muted' ?>">
                                                    <?= $vigenciaHasta ? formatDate($vigenciaHasta) : 'Sin vigencia' ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($user['lider_nombre'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($user['municipio'] ?? 'No especificado') ?></td>
                                            <td><?= formatDate($user['fecha_registro']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('admin/edit_user.php?id=' . $user['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($currentUser['rol'] === 'SuperAdmin' && in_array($user['rol'], ['Líder', 'Activista'])): ?>
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="showChangePasswordModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_completo']) ?>')" 
                                                                title="Cambiar Contraseña">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($currentUser['rol'] === 'SuperAdmin' && $user['rol'] === 'Activista' && !empty($user['lider_id'])): ?>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="unlinkFromLeader(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_completo']) ?>')" 
                                                                title="Desvincular de Líder">
                                                            <i class="fas fa-unlink"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($currentUser['rol'] === 'SuperAdmin' && $user['estado'] !== 'eliminado'): ?>
                                                        <button type="button" class="btn btn-danger" 
                                                                data-user-id="<?= $user['id'] ?>" 
                                                                data-user-name="<?= htmlspecialchars($user['nombre_completo']) ?>" 
                                                                onclick="deletePermanently(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_completo']) ?>')" 
                                                                title="Eliminar Usuario Permanentemente">
                                                            <i class="fas fa-trash"></i> Eliminar
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

    <!-- Modal para cambio de contraseña -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="userName" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="userName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="newPassword" minlength="6" required>
                            <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirmPassword" minlength="6" required>
                        </div>
                        <input type="hidden" id="targetUserId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="changePassword()">
                        <i class="fas fa-save me-2"></i>Cambiar Contraseña
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeUserStatus(userId, status) {
            console.log('changeUserStatus called', { userId: userId, status: status });
            
            const statusMessages = {
                'activo': 'activar',
                'suspendido': 'suspender',
                'desactivado': 'desactivar'
            };
            
            const actionText = statusMessages[status] || 'cambiar el estado de';
            
            if (confirm(`¿Estás seguro de que quieres ${actionText} este usuario?`)) {
                console.log('changeUserStatus: confirmed, submitting form');
                
                // Find and update the form
                const form = document.getElementById('statusForm');
                const userIdInput = document.getElementById('statusUserId');
                const statusInput = document.getElementById('statusValue');
                
                if (!form || !userIdInput || !statusInput) {
                    console.error('changeUserStatus: form elements not found', {
                        form: !!form,
                        userIdInput: !!userIdInput,
                        statusInput: !!statusInput
                    });
                    showAlert('danger', 'Error: Formulario no encontrado. Recarga la página e intenta nuevamente.');
                    return;
                }
                
                userIdInput.value = userId;
                statusInput.value = status;
                
                console.log('changeUserStatus: form values set', {
                    userId: userIdInput.value,
                    status: statusInput.value
                });
                
                // Add loading indicator to the button that was clicked
                const actionButtons = document.querySelectorAll(`button[onclick*="changeUserStatus(${userId}"]`);
                let targetButton = null;
                
                actionButtons.forEach(button => {
                    if (button.onclick && button.onclick.toString().includes(`'${status}'`)) {
                        targetButton = button;
                    }
                });
                
                if (targetButton) {
                    const originalContent = targetButton.innerHTML;
                    targetButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    targetButton.disabled = true;
                    
                    // Re-enable button after a short delay if form submission fails
                    setTimeout(() => {
                        if (targetButton.disabled) {
                            targetButton.innerHTML = originalContent;
                            targetButton.disabled = false;
                        }
                    }, 3000);
                }
                
                form.submit();
            } else {
                console.log('changeUserStatus: cancelled by user');
            }
        }

        function showChangePasswordModal(userId, userName) {
            document.getElementById('targetUserId').value = userId;
            document.getElementById('userName').value = userName;
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        }

        function changePassword() {
            const userId = document.getElementById('targetUserId').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validate password
            if (newPassword.length < 6) {
                showAlert('danger', 'La contraseña debe tener al menos 6 caracteres');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showAlert('danger', 'Las contraseñas no coinciden');
                return;
            }
            
            if (!confirm('¿Estás seguro de que quieres cambiar la contraseña de este usuario?')) {
                return;
            }
            
            // Show loading
            const submitBtn = document.querySelector('#changePasswordModal .btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cambiando...';
            submitBtn.disabled = true;
            
            // Make AJAX call
            fetch('<?= url('api/users.php') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'change_password',
                    user_id: userId,
                    new_password: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                    modal.hide();
                } else {
                    showAlert('danger', data.error || 'Error al cambiar la contraseña');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error de conexión al cambiar la contraseña');
            })
            .finally(() => {
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
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
        
        function deleteUser(userId, userName) {
            console.log('deleteUser called', { userId: userId, userName: userName });
            
            // Debug: Check if we're on the right page
            console.log('Current URL:', window.location.href);
            console.log('Available buttons:', document.querySelectorAll('button[data-user-id]').length);
            
            if (confirm(`¿Estás seguro de que quieres ELIMINAR permanentemente el usuario "${userName}"? Esta acción no se puede deshacer.`)) {
                // Show loading state - find the delete button for this specific user using data attribute
                const deleteBtn = document.querySelector(`button[data-user-id="${userId}"][title="Eliminar Usuario"]`);
                
                console.log('deleteUser: delete button found:', deleteBtn !== null);
                
                if (!deleteBtn) {
                    console.error('deleteUser: no se encontró el botón de eliminar para userId=', userId);
                    console.error('deleteUser: available buttons with data-user-id:', 
                        Array.from(document.querySelectorAll('button[data-user-id]')).map(btn => ({
                            userId: btn.getAttribute('data-user-id'),
                            title: btn.getAttribute('title')
                        }))
                    );
                    showAlert('danger', 'Error: No se pudo encontrar el botón de eliminar. Abre la consola para más detalles.');
                    return;
                }

                const originalContent = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteBtn.disabled = true;

                console.log('deleteUser: sending request to API for userId=', userId);
                console.log('deleteUser: API URL will be:', '<?= url('api/users.php') ?>');

                // Make AJAX call
                fetch('<?= url('api/users.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        user_id: userId
                    })
                })
                .then(response => {
                    console.log('deleteUser: received response status:', response.status);
                    console.log('deleteUser: response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('deleteUser: response data:', data);
                    if (data.success) {
                        showAlert('success', data.message);
                        // Reload page after 2 seconds to show the updated list
                        setTimeout(() => {
                            console.log('deleteUser: reloading page...');
                            window.location.reload();
                        }, 2000);
                    } else {
                        showAlert('danger', data.error || 'Error al eliminar usuario');
                        // Restore button
                        deleteBtn.innerHTML = originalContent;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('deleteUser: fetch error:', error);
                    showAlert('danger', 'Error de conexión al eliminar usuario: ' + error.message);
                    // Restore button
                    deleteBtn.innerHTML = originalContent;
                    deleteBtn.disabled = false;
                });
            }
        }
        
        // Nueva función para desvincular activista de líder
        function unlinkFromLeader(userId, userName) {
            if (confirm(`¿Estás seguro de que quieres desvincular a "${userName}" de su líder?\n\nEl activista quedará sin líder asignado.`)) {
                const btn = event.target.closest('button');
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;

                fetch('<?= url('api/users.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'unlink_from_leader',
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showAlert('danger', data.error || 'Error al desvincular activista');
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Error de conexión: ' + error.message);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
            }
        }
        
        // Nueva función para eliminar permanentemente
        function deletePermanently(userId, userName) {
            // Primero verificar si se puede eliminar
            fetch('<?= url('api/users.php') ?>?' + new URLSearchParams({
                action: 'check_delete',
                user_id: userId
            }))
            .then(response => response.json())
            .then(checkData => {
                if (!checkData.success) {
                    showAlert('danger', checkData.error || 'Error al verificar usuario');
                    return;
                }
                
                const stats = checkData.stats || {};
                let warningMessage = ` ¡ALERTA DE ELIMINACIÓN PERMANENTE! \n\n`;
                warningMessage += `Esta acción eliminará PERMANENTEMENTE a:\n`;
                warningMessage += `"${userName}"\n\n`;
                warningMessage += `⛔ NO SE PODRÁ RECUPERAR\n`;
                warningMessage += `⛔ TODOS LOS DATOS SERÁN BORRADOS\n`;
                warningMessage += `⛔ ESTA ACCIÓN ES IRREVERSIBLE\n\n`;
                
                if (stats.activities > 0 || stats.activists > 0 || stats.evidences > 0) {
                    warningMessage += ` Datos que serán eliminados:\n`;
                    if (stats.activities > 0) {
                        warningMessage += `   • ${stats.activities} Actividad(es)\n`;
                    }
                    if (stats.activists > 0) {
                        warningMessage += `   • ${stats.activists} Activista(s) serán desvinculados\n`;
                    }
                    if (stats.evidences > 0) {
                        warningMessage += `   • ${stats.evidences} Evidencia(s)\n`;
                    }
                    warningMessage += `\n`;
                }
                
                warningMessage += `❓ ¿Estás COMPLETAMENTE SEGURO de eliminar permanentemente este usuario?\n\n`;
                warningMessage += `💡 Recuerda: Una vez eliminado, NO HAY FORMA DE RECUPERARLO.`;
                
                if (confirm(warningMessage)) {
                    // Buscar el botón usando el userId y userName
                    const btn = document.querySelector(`button[data-user-id="${userId}"][data-user-name="${userName}"]`);
                    
                    if (!btn) {
                        showAlert('danger', 'Error: No se pudo encontrar el botón de eliminar');
                        return;
                    }
                    
                    const originalContent = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    btn.disabled = true;

                    fetch('<?= url('api/users.php') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_permanent',
                            user_id: userId,
                            force: !checkData.can_delete // Forzar si tiene dependencias
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('success', '✅ ' + data.message);
                            setTimeout(() => window.location.reload(), 2000);
                        } else {
                            showAlert('danger', '❌ ' + (data.error || 'Error al eliminar usuario'));
                            btn.innerHTML = originalContent;
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('danger', 'Error de conexión: ' + error.message);
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error al verificar usuario: ' + error.message);
            });
        }
    </script>
</body>
</html>