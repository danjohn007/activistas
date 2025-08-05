<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Actividad - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                        <h4><i class="fas fa-plus me-2"></i>Nueva</h4>
                        <small>Actividad</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/' . strtolower($_SESSION['user_role']) . '.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Mis Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white active" href="<?= url('activities/create.php') ?>">
                                <i class="fas fa-plus me-2"></i>Nueva Actividad
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
                    <h1 class="h2">Nueva Actividad</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver a la lista
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

                <?php if (!empty($_SESSION['form_errors'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Errores de validación:</strong>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['form_errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['form_errors']); ?>
                <?php endif; ?>

                <!-- Formulario de nueva actividad -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos de la Actividad</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['titulo'] ?? '') ?>" 
                                               required maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tipo_actividad_id" class="form-label">Tipo de Actividad *</label>
                                        <select class="form-select" id="tipo_actividad_id" name="tipo_actividad_id" required>
                                            <option value="">Seleccionar tipo</option>
                                            <?php foreach ($activityTypes as $type): ?>
                                                <option value="<?= $type['id'] ?>" 
                                                        <?= ($_SESSION['form_data']['tipo_actividad_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                          maxlength="1000"><?= htmlspecialchars($_SESSION['form_data']['descripcion'] ?? '') ?></textarea>
                                <div class="form-text">Describe brevemente los objetivos y detalles de la actividad.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fecha_actividad" class="form-label">Fecha de la Actividad *</label>
                                        <input type="date" class="form-control" id="fecha_actividad" name="fecha_actividad" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['fecha_actividad'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="alcance_estimado" class="form-label">Alcance Estimado (personas)</label>
                                        <input type="number" class="form-control" id="alcance_estimado" name="alcance_estimado" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['alcance_estimado'] ?? '') ?>" 
                                               min="0" max="999999">
                                        <div class="form-text">Número estimado de personas a las que llegará la actividad.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="lugar" class="form-label">Lugar</label>
                                <input type="text" class="form-control" id="lugar" name="lugar" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['lugar'] ?? '') ?>" 
                                       maxlength="255" placeholder="Dirección, ciudad o plataforma digital">
                            </div>

                            <div class="mb-3">
                                <label for="evidence_files" class="form-label">Evidencias (opcional)</label>
                                <input type="file" class="form-control" id="evidence_files" name="evidence_files[]" 
                                       accept="image/*,video/*,audio/*" multiple>
                                <div class="form-text">Puedes subir fotos, videos o audios relacionados con la actividad.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= url('activities/') ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Crear Actividad
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Limpiar datos del formulario después de mostrar
        <?php unset($_SESSION['form_data']); ?>
    </script>
</body>
</html>