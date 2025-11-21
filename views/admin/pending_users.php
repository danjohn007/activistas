<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Pendientes - Activistas Digitales</title>
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
            renderSidebar('pending_users'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Usuarios Pendientes de Aprobación</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-warning text-dark fs-6 py-2">
                                <?= count($pendingUsers ?? []) ?> pendientes
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

                <!-- Lista de usuarios pendientes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Solicitudes de Registro</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingUsers)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">¡Excelente!</h5>
                                <p class="text-muted">No hay usuarios pendientes de aprobación en este momento.</p>
                                <a href="<?= url('admin/users.php') ?>" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i>Ver todos los usuarios
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($pendingUsers as $user): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 border-warning">
                                        <div class="card-header bg-warning bg-opacity-10">
                                            <h6 class="card-title mb-0 text-warning">
                                                <i class="fas fa-clock me-2"></i>Pendiente de Aprobación
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <?php if (!empty($user['foto_perfil'])): ?>
                                                    <img src="<?= url('assets/uploads/profiles/' . $user['foto_perfil']) ?>" 
                                                         class="rounded-circle" width="80" height="80" alt="Foto de perfil">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                                         style="width: 80px; height: 80px;">
                                                        <i class="fas fa-user fa-2x text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <h6 class="card-title text-center"><?= htmlspecialchars($user['nombre_completo']) ?></h6>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?= htmlspecialchars($user['email']) ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?= htmlspecialchars($user['telefono']) ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars($user['direccion']) ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <span class="badge bg-info"><?= htmlspecialchars($user['rol']) ?></span>
                                                <?php if (!empty($user['lider_nombre'])): ?>
                                                    <br><small class="text-muted">
                                                        Líder: <?= htmlspecialchars($user['lider_nombre']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Registrado: <?= formatDate($user['fecha_registro']) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-success btn-sm" 
                                                        onclick="showApprovalModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_completo'], ENT_QUOTES) ?>')">
                                                    <i class="fas fa-check me-1"></i>Aprobar
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="processUser(<?= $user['id'] ?>, 'reject')">
                                                    <i class="fas fa-times me-1"></i>Rechazar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Form para procesamiento de usuarios -->
    <form id="processForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="user_id" id="processUserId">
        <input type="hidden" name="action" id="processAction">
        <input type="hidden" name="vigencia_hasta" id="vigenciaHasta">
        <input type="hidden" name="rol" id="rolHidden">
        <input type="hidden" name="lider_id" id="liderHidden">
        <input type="hidden" name="grupo_id" id="grupoHidden">
    </form>

    <!-- Modal para selección de vigencia -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalLabel">Aprobar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres aprobar a <strong id="userName"></strong>?</p>
                    
                    <!-- Role Selection -->
                    <div class="mb-3">
                        <label for="rolInput" class="form-label">Tipo de Usuario:</label>
                        <select class="form-select" id="rolInput">
                            <option value="Activista">Activista</option>
                            <option value="Líder">Líder</option>
                            <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
                                <option value="Gestor">Gestor</option>
                                <option value="SuperAdmin">SuperAdmin</option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Selecciona el tipo de usuario para la aprobación.</div>
                    </div>
                    
                    <!-- Leader Selection - only for Activists -->
                    <div class="mb-3" id="liderInputSection" style="display: block;">
                        <label for="liderInput" class="form-label">Líder Asignado:</label>
                        <select class="form-select" id="liderInput">
                            <option value="">Seleccionar líder...</option>
                            <?php if (!empty($liders)): ?>
                                <?php foreach ($liders as $lider): ?>
                                    <option value="<?= $lider['id'] ?>"><?= htmlspecialchars($lider['nombre_completo']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Solo requerido para activistas.</div>
                    </div>
                    
                    <!-- Group Assignment - for SuperAdmin and Gestor -->
                    <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor'])): ?>
                    <?php if (!empty($groups)): ?>
                    <div class="mb-3">
                        <label for="grupoInput" class="form-label">Grupo (opcional):</label>
                        <select class="form-select" id="grupoInput">
                            <option value="">Sin grupo específico</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['nombre']) ?> (<?= $group['miembros_count'] ?? 0 ?> miembros)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Asignar usuario a un grupo específico (opcional).</div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Vigencia -->
                    <div class="mb-3">
                        <label for="vigenciaInput" class="form-label">Vigencia hasta (opcional):</label>
                        <input type="date" class="form-control" id="vigenciaInput" min="<?= date('Y-m-d') ?>">
                        <div class="form-text">Si no se especifica, el usuario tendrá acceso permanente.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="confirmApproval()">
                        <i class="fas fa-check me-1"></i>Aprobar Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle role change to show/hide leader selection
        document.getElementById('rolInput').addEventListener('change', function() {
            const liderSection = document.getElementById('liderInputSection');
            if (this.value === 'Activista') {
                liderSection.style.display = 'block';
            } else {
                liderSection.style.display = 'none';
                document.getElementById('liderInput').value = '';
            }
        });
        
        function showApprovalModal(userId, userName) {
            document.getElementById('processUserId').value = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('vigenciaInput').value = '';
            document.getElementById('rolInput').value = 'Activista'; // default to Activista
            document.getElementById('liderInput').value = '';
            if (document.getElementById('grupoInput')) {
                document.getElementById('grupoInput').value = '';
            }
            document.getElementById('liderInputSection').style.display = 'block';
            
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            modal.show();
        }
        
        function confirmApproval() {
            const vigencia = document.getElementById('vigenciaInput').value;
            const rol = document.getElementById('rolInput').value;
            const lider = document.getElementById('liderInput').value;
            const grupo = document.getElementById('grupoInput') ? document.getElementById('grupoInput').value : '';
            
            document.getElementById('processAction').value = 'approve';
            document.getElementById('vigenciaHasta').value = vigencia || '';
            document.getElementById('rolHidden').value = rol;
            document.getElementById('liderHidden').value = lider || '';
            document.getElementById('grupoHidden').value = grupo || '';
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('approvalModal'));
            modal.hide();
            
            // Submit form
            document.getElementById('processForm').submit();
        }
        
        function processUser(userId, action) {
            const actionText = action === 'approve' ? 'aprobar' : 'rechazar';
            if (confirm(`¿Estás seguro de que quieres ${actionText} este usuario?`)) {
                document.getElementById('processUserId').value = userId;
                document.getElementById('processAction').value = action;
                document.getElementById('vigenciaHasta').value = '';
                document.getElementById('rolHidden').value = '';
                document.getElementById('liderHidden').value = '';
                document.getElementById('grupoHidden').value = '';
                document.getElementById('processForm').submit();
            }
        }
    </script>
</body>
</html>