<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .profile-picture {
            width: 120px;
            height: 120px;
            object-fit: cover;
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
                        <h4><i class="fas fa-users me-2"></i>Activistas</h4>
                        <small>SuperAdmin</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/admin.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('admin/users.php') ?>">
                                <i class="fas fa-users me-2"></i>Gestión de Usuarios
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('admin/pending_users.php') ?>">
                                <i class="fas fa-user-clock me-2"></i>Usuarios Pendientes
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Actividades
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
                    <h1 class="h2">Editar Usuario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver a la lista
                        </a>
                    </div>
                </div>

                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($user)): ?>
                <div class="row">
                    <div class="col-md-4">
                        <!-- Información del usuario -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información del Usuario</h5>
                            </div>
                            <div class="card-body text-center">
                                <?php if (!empty($user['foto_perfil'])): ?>
                                    <img src="<?= url('assets/uploads/profiles/' . $user['foto_perfil']) ?>" 
                                         class="rounded-circle profile-picture mb-3" alt="Foto de perfil">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3 profile-picture">
                                        <i class="fas fa-user fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h5><?= htmlspecialchars($user['nombre_completo']) ?></h5>
                                <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                                
                                <div class="mb-3">
                                    <span class="badge bg-info fs-6"><?= htmlspecialchars($user['rol']) ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <?php
                                    $badgeClass = [
                                        'activo' => 'success',
                                        'pendiente' => 'warning',
                                        'suspendido' => 'danger',
                                        'desactivado' => 'secondary'
                                    ][$user['estado']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?> fs-6"><?= ucfirst($user['estado']) ?></span>
                                </div>
                                
                                <div class="text-start">
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calendar me-1"></i>
                                        Registrado: <?= formatDate($user['fecha_registro']) ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-user-tie me-1"></i>
                                        ID: <?= $user['id'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Formulario de edición -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Editar Información</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                                   value="<?= htmlspecialchars($user['nombre_completo']) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                                   value="<?= htmlspecialchars($user['telefono']) ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="3"><?= htmlspecialchars($user['direccion']) ?></textarea>
                                    </div>
                                    
                                    <!-- Redes Sociales -->
                                    <div class="card mt-4 mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-share-alt me-2"></i>Redes Sociales
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="facebook" class="form-label">
                                                        <i class="fab fa-facebook text-primary me-2"></i>Facebook
                                                    </label>
                                                    <input type="url" class="form-control" id="facebook" name="facebook" 
                                                           value="<?= htmlspecialchars($user['facebook'] ?? '') ?>"
                                                           placeholder="https://facebook.com/perfil">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="instagram" class="form-label">
                                                        <i class="fab fa-instagram text-danger me-2"></i>Instagram
                                                    </label>
                                                    <input type="url" class="form-control" id="instagram" name="instagram" 
                                                           value="<?= htmlspecialchars($user['instagram'] ?? '') ?>"
                                                           placeholder="https://instagram.com/perfil">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="tiktok" class="form-label">
                                                        <i class="fab fa-tiktok text-dark me-2"></i>TikTok
                                                    </label>
                                                    <input type="url" class="form-control" id="tiktok" name="tiktok" 
                                                           value="<?= htmlspecialchars($user['tiktok'] ?? '') ?>"
                                                           placeholder="https://tiktok.com/@perfil">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="x" class="form-label">
                                                        <i class="fab fa-x-twitter text-dark me-2"></i>X (Twitter)
                                                    </label>
                                                    <input type="url" class="form-control" id="x" name="x" 
                                                           value="<?= htmlspecialchars($user['x'] ?? '') ?>"
                                                           placeholder="https://x.com/perfil">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Cuenta de Pago -->
                                    <div class="card mt-4 mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-credit-card me-2"></i>Información de Pago
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="cuenta_pago" class="form-label">Cuenta de Pago</label>
                                                <input type="text" class="form-control" id="cuenta_pago" name="cuenta_pago" 
                                                       value="<?= htmlspecialchars($user['cuenta_pago'] ?? '') ?>"
                                                       placeholder="Número de cuenta, PayPal, etc.">
                                                <small class="text-muted">Información para pagos y transferencias</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Tipo de Usuario -->
                                    <?php 
                                    $currentUserRole = $_SESSION['user_role'] ?? '';
                                    $canEditRol = in_array($currentUserRole, ['SuperAdmin', 'Gestor']);
                                    ?>
                                    <?php if ($canEditRol): ?>
                                    <div class="card mt-4 mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-user-tag me-2"></i>Tipo de Usuario
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="rol" class="form-label">Tipo de Usuario *</label>
                                                <select class="form-select" id="rol" name="rol" required>
                                                    <option value="Activista" <?= $user['rol'] === 'Activista' ? 'selected' : '' ?>>Activista</option>
                                                    <option value="Líder" <?= $user['rol'] === 'Líder' ? 'selected' : '' ?>>Líder</option>
                                                    <?php if ($currentUserRole === 'SuperAdmin'): ?>
                                                        <option value="Gestor" <?= $user['rol'] === 'Gestor' ? 'selected' : '' ?>>Gestor</option>
                                                        <option value="SuperAdmin" <?= $user['rol'] === 'SuperAdmin' ? 'selected' : '' ?>>SuperAdmin</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            
                                            <!-- Líder Asignado - solo visible cuando es Activista -->
                                            <div class="mb-3" id="lider-section" style="display: <?= $user['rol'] === 'Activista' ? 'block' : 'none' ?>;">
                                                <label for="lider_id" class="form-label">Líder Asignado</label>
                                                <select class="form-select" id="lider_id" name="lider_id">
                                                    <option value="">Seleccionar líder...</option>
                                                    <?php if (!empty($liders)): ?>
                                                        <?php foreach ($liders as $lider): ?>
                                                            <option value="<?= $lider['id'] ?>" 
                                                                    <?= $user['lider_id'] == $lider['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($lider['nombre_completo']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <small class="text-muted">Solo requerido para activistas</small>
                                            </div>
                                            
                                            <!-- Grupo - visible para SuperAdmin y Gestor -->
                                            <?php if (in_array($_SESSION['user_role'], ['SuperAdmin', 'Gestor']) && !empty($groups)): ?>
                                            <div class="mb-3">
                                                <label for="grupo_id" class="form-label">Grupo Asignado</label>
                                                <select class="form-select" id="grupo_id" name="grupo_id">
                                                    <option value="">Sin grupo específico</option>
                                                    <?php foreach ($groups as $group): ?>
                                                        <option value="<?= $group['id'] ?>" 
                                                                <?= ($user['grupo_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($group['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Asignar usuario a un grupo específico (opcional)</small>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <script>
                                        document.getElementById('rol').addEventListener('change', function() {
                                            const liderSection = document.getElementById('lider-section');
                                            if (this.value === 'Activista') {
                                                liderSection.style.display = 'block';
                                            } else {
                                                liderSection.style.display = 'none';
                                                document.getElementById('lider_id').value = '';
                                            }
                                        });
                                    </script>
                                    <?php else: ?>
                                        <!-- Para usuarios que no pueden editar el rol, mostrar solo si es activista -->
                                        <?php if ($user['rol'] === 'Activista' && !empty($liders)): ?>
                                        <div class="mb-3">
                                            <label for="lider_id" class="form-label">Líder Asignado</label>
                                            <select class="form-select" id="lider_id" name="lider_id">
                                                <option value="">Seleccionar líder...</option>
                                                <?php foreach ($liders as $lider): ?>
                                                    <option value="<?= $lider['id'] ?>" 
                                                            <?= $user['lider_id'] == $lider['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($lider['nombre_completo']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Vigencia -->
                                    <?php 
                                    $currentUserRole = $_SESSION['user_role'] ?? '';
                                    $canEditVigencia = in_array($currentUserRole, ['SuperAdmin', 'Gestor']);
                                    ?>
                                    <?php if ($canEditVigencia): ?>
                                    <div class="card mt-4 mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-calendar-alt me-2"></i>Vigencia del Usuario
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="vigencia_hasta" class="form-label">Fecha de Vigencia</label>
                                                <input type="date" class="form-control" id="vigencia_hasta" name="vigencia_hasta" 
                                                       value="<?= htmlspecialchars($user['vigencia_hasta'] ?? '') ?>"
                                                       min="<?= date('Y-m-d') ?>">
                                                <small class="text-muted">
                                                    Fecha hasta la cual el usuario tendrá acceso al sistema. 
                                                    Dejar vacío para vigencia indefinida.
                                                </small>
                                                <?php if (!empty($user['vigencia_hasta'])): ?>
                                                    <div class="mt-2">
                                                        <small class="<?= $user['vigencia_hasta'] < date('Y-m-d') ? 'text-danger' : 'text-success' ?>">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Estado actual: <?= $user['vigencia_hasta'] < date('Y-m-d') ? 'Vencida' : 'Vigente' ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="foto_perfil" class="form-label">Nueva Foto de Perfil</label>
                                        <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" 
                                               accept="image/jpeg,image/png,image/gif">
                                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary me-md-2">
                                            Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Usuario no encontrado</h5>
                    <p>El usuario que intentas editar no existe o no tienes permisos para acceder a él.</p>
                    <a href="<?= url('admin/users.php') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>Volver a la lista de usuarios
                    </a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>