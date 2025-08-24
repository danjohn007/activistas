<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Tipo de Actividad - Activistas Digitales</title>
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
                        <h4><i class="fas fa-plus me-2"></i>Nuevo</h4>
                        <small>Tipo de Actividad</small>
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
                        <i class="fas fa-plus me-2"></i>Nuevo Tipo de Actividad
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activity-types/') ?>" class="btn btn-outline-secondary">
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

                <!-- Formulario de nuevo tipo de actividad -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos del Tipo de Actividad</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">
                                            <i class="fas fa-tag me-1"></i>Nombre del Tipo <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="nombre" 
                                               name="nombre" 
                                               maxlength="100"
                                               value="<?= htmlspecialchars($_SESSION['form_data']['nombre'] ?? '') ?>" 
                                               required>
                                        <div class="form-text">Máximo 100 caracteres</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="activo" class="form-label">
                                            <i class="fas fa-toggle-on me-1"></i>Estado
                                        </label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="activo" 
                                                   name="activo" 
                                                   <?= !isset($_SESSION['form_data']) || ($_SESSION['form_data']['activo'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="activo">
                                                Tipo de actividad activo
                                            </label>
                                        </div>
                                        <div class="form-text">Los tipos inactivos no aparecen en los formularios</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Descripción
                                </label>
                                <textarea class="form-control" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          rows="4" 
                                          maxlength="1000"
                                          placeholder="Descripción detallada del tipo de actividad..."><?= htmlspecialchars($_SESSION['form_data']['descripcion'] ?? '') ?></textarea>
                                <div class="form-text">Esta descripción se cargará automáticamente al crear actividades de este tipo. Máximo 1000 caracteres.</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?= url('activity-types/') ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Crear Tipo de Actividad
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información sobre tipos de actividad -->
                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-lightbulb me-2"></i>Consejos para crear tipos de actividad</h6>
                    <ul class="mb-0">
                        <li><strong>Nombre descriptivo:</strong> Use nombres claros y específicos (ej: "Campaña en Redes Sociales")</li>
                        <li><strong>Descripción detallada:</strong> Incluya información sobre qué tipo de actividades abarca</li>
                        <li><strong>Ejemplos útiles:</strong> Mencione ejemplos específicos en la descripción</li>
                        <li><strong>Objetivos:</strong> Explique qué se busca lograr con este tipo de actividades</li>
                    </ul>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Limpiar datos del formulario de la sesión
        <?php if (isset($_SESSION['form_data'])): ?>
            <?php unset($_SESSION['form_data']); ?>
        <?php endif; ?>
        
        // Contador de caracteres para la descripción
        const descripcionTextarea = document.getElementById('descripcion');
        if (descripcionTextarea) {
            descripcionTextarea.addEventListener('input', function() {
                const length = this.value.length;
                const maxLength = 1000;
                const remaining = maxLength - length;
                
                // Crear o actualizar contador
                let counter = document.getElementById('descripcion-counter');
                if (!counter) {
                    counter = document.createElement('div');
                    counter.id = 'descripcion-counter';
                    counter.className = 'form-text text-end mt-1';
                    this.parentNode.appendChild(counter);
                }
                
                counter.textContent = `${length}/${maxLength} caracteres`;
                counter.className = `form-text text-end mt-1 ${remaining < 50 ? 'text-warning' : ''}`;
            });
        }
    </script>
</body>
</html>