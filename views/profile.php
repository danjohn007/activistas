<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #dee2e6;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
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
                        <small><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <?php 
                            $dashboardUrl = 'dashboards/activista.php';
                            switch($_SESSION['user_role'] ?? '') {
                                case 'SuperAdmin':
                                    $dashboardUrl = 'dashboards/admin.php';
                                    break;
                                case 'Gestor':
                                    $dashboardUrl = 'dashboards/gestor.php';
                                    break;
                                case 'Líder':
                                    $dashboardUrl = 'dashboards/lider.php';
                                    break;
                            }
                            ?>
                            <a class="nav-link text-white" href="<?= url($dashboardUrl) ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('profile.php') ?>">
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
                        <?php if (isset($isOwnProfile) && $isOwnProfile): ?>
                            Mi Perfil
                        <?php else: ?>
                            Perfil de <?= htmlspecialchars($user['nombre_completo'] ?? 'Usuario') ?>
                        <?php endif; ?>
                    </h1>
                    <?php if (!$isOwnProfile): ?>
                        <a href="<?= url('profile.php') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Mi Perfil
                        </a>
                    <?php endif; ?>
                </div>

                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['debug_view'])): ?>
                    <div class="alert alert-warning" role="alert">
                        Vista: views/profile.php | Municipios: <?= count(getMunicipiosQueretaro()) ?>
                    </div>
                <?php endif; ?>

                <?php if (in_array(($user['rol'] ?? ''), ['Líder', 'Activista']) && empty($user['municipio'])): ?>
                    <div class="alert alert-warning" role="alert">
                        <strong>Importante:</strong> Debes registrar tu municipio para continuar con tu perfil.
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <?php if (isset($isOwnProfile) && $isOwnProfile): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit me-2"></i>Editar Perfil
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                                       value="<?= htmlspecialchars($user['nombre_completo'] ?? '') ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                                                <small class="text-muted">El email no se puede modificar</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Teléfono</label>
                                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                                       value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="rol" class="form-label">Rol</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['rol'] ?? '') ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="3"><?= htmlspecialchars($user['direccion'] ?? '') ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="municipio" class="form-label">Municipio <?= in_array(($user['rol'] ?? ''), ['Líder', 'Activista']) ? '*' : '' ?></label>
                                        <select class="form-select" id="municipio" name="municipio" <?= in_array(($user['rol'] ?? ''), ['Líder', 'Activista']) ? 'required' : '' ?>>
                                            <option value="">Seleccione un municipio</option>
                                            <?php foreach (getMunicipiosQueretaro() as $municipio): ?>
                                                <option value="<?= htmlspecialchars($municipio) ?>" <?= ($user['municipio'] ?? '') === $municipio ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($municipio) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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
                                                           placeholder="https://facebook.com/tu-perfil">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="instagram" class="form-label">
                                                        <i class="fab fa-instagram text-danger me-2"></i>Instagram
                                                    </label>
                                                    <input type="url" class="form-control" id="instagram" name="instagram" 
                                                           value="<?= htmlspecialchars($user['instagram'] ?? '') ?>"
                                                           placeholder="https://instagram.com/tu-perfil">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="tiktok" class="form-label">
                                                        <i class="fab fa-tiktok text-dark me-2"></i>TikTok
                                                    </label>
                                                    <input type="url" class="form-control" id="tiktok" name="tiktok" 
                                                           value="<?= htmlspecialchars($user['tiktok'] ?? '') ?>"
                                                           placeholder="https://tiktok.com/@tu-perfil">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="x" class="form-label">
                                                        <i class="fab fa-x-twitter text-dark me-2"></i>X (Twitter)
                                                    </label>
                                                    <input type="url" class="form-control" id="x" name="x" 
                                                           value="<?= htmlspecialchars($user['x'] ?? '') ?>"
                                                           placeholder="https://x.com/tu-perfil">
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
                                    
                                    <div class="mb-3">
                                        <label for="foto_perfil" class="form-label">Foto de Perfil</label>
                                        <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" accept="image/*">
                                        <small class="text-muted">Formatos permitidos: JPG, PNG, GIF (máximo 5MB)</small>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Vista de solo lectura del perfil -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-eye me-2"></i>Información del Usuario
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Nombre Completo</label>
                                            <p class="form-control-plaintext"><?= htmlspecialchars($user['nombre_completo'] ?? '') ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Email</label>
                                            <p class="form-control-plaintext"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Teléfono</label>
                                            <p class="form-control-plaintext"><?= htmlspecialchars($user['telefono'] ?? 'No especificado') ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Rol</label>
                                            <p class="form-control-plaintext">
                                                <span class="badge bg-primary"><?= htmlspecialchars($user['rol'] ?? '') ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Dirección</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($user['direccion'] ?? 'No especificada') ?></p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Municipio</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($user['municipio'] ?? 'No especificado') ?></p>
                                </div>
                                
                                <!-- Redes Sociales -->
                                <?php if (!empty($user['facebook']) || !empty($user['instagram']) || !empty($user['tiktok']) || !empty($user['x'])): ?>
                                <div class="card mt-4 mb-3">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-share-alt me-2"></i>Redes Sociales
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php if (!empty($user['facebook'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="fab fa-facebook text-primary me-2"></i>Facebook
                                                </label>
                                                <p class="form-control-plaintext">
                                                    <a href="<?= htmlspecialchars($user['facebook']) ?>" target="_blank" class="text-decoration-none">
                                                        Ver perfil <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($user['instagram'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="fab fa-instagram text-danger me-2"></i>Instagram
                                                </label>
                                                <p class="form-control-plaintext">
                                                    <a href="<?= htmlspecialchars($user['instagram']) ?>" target="_blank" class="text-decoration-none">
                                                        Ver perfil <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="row">
                                            <?php if (!empty($user['tiktok'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="fab fa-tiktok text-dark me-2"></i>TikTok
                                                </label>
                                                <p class="form-control-plaintext">
                                                    <a href="<?= htmlspecialchars($user['tiktok']) ?>" target="_blank" class="text-decoration-none">
                                                        Ver perfil <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($user['x'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="fab fa-x-twitter text-dark me-2"></i>X (Twitter)
                                                </label>
                                                <p class="form-control-plaintext">
                                                    <a href="<?= htmlspecialchars($user['x']) ?>" target="_blank" class="text-decoration-none">
                                                        Ver perfil <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-circle me-2"></i>Información del Perfil
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php if (!empty($user['foto_perfil'])): ?>
                                        <img src="<?= url('assets/uploads/profiles/' . $user['foto_perfil']) ?>" 
                                             alt="Foto de perfil" class="profile-photo">
                                    <?php else: ?>
                                        <div class="profile-photo d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h5><?= htmlspecialchars($user['nombre_completo'] ?? 'Usuario') ?></h5>
                                <p class="text-muted"><?= htmlspecialchars($user['rol'] ?? '') ?></p>
                                <hr>
                                <div class="text-start">
                                    <p><strong>Email:</strong><br><?= htmlspecialchars($user['email'] ?? '') ?></p>
                                    <p><strong>Municipio:</strong><br><?= htmlspecialchars($user['municipio'] ?? 'No especificado') ?></p>
                                    <p><strong>Estado:</strong><br>
                                        <span class="badge bg-<?= ($user['estado'] ?? '') === 'activo' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($user['estado'] ?? 'Pendiente') ?>
                                        </span>
                                    </p>
                                    <p><strong>Miembro desde:</strong><br>
                                        <?= date('d/m/Y', strtotime($user['fecha_registro'] ?? 'now')) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>Cambiar Contraseña
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Para cambiar tu contraseña, contacta al administrador del sistema.</p>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    <i class="fas fa-key me-2"></i>Contactar Administrador
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>