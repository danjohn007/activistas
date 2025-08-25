<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proponer Actividad - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('propose_activity'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Proponer Nueva Actividad</h1>
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

                <!-- Información sobre propuestas -->
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i>Propón tu Actividad</h5>
                    <p class="mb-2">Como activista, puedes proponer nuevas actividades que consideres beneficiosas para el movimiento.</p>
                    <p class="mb-0">
                        <strong>Beneficios:</strong> Si tu propuesta es aprobada y la completas exitosamente, 
                        recibirás <strong>100 puntos extra</strong> en tu ranking además de los puntos normales por completar la actividad.
                    </p>
                </div>

                <!-- Formulario de propuesta -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Detalles de la Propuesta
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('activities/create_proposal.php') ?>">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tipo_actividad_id" class="form-label">Tipo de Actividad *</label>
                                        <select class="form-select" id="tipo_actividad_id" name="tipo_actividad_id" required>
                                            <option value="">Selecciona un tipo</option>
                                            <?php foreach ($activityTypes as $type): ?>
                                                <option value="<?= $type['id'] ?>" 
                                                        <?= (($_SESSION['form_data']['tipo_actividad_id'] ?? '') == $type['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fecha_actividad" class="form-label">Fecha Propuesta *</label>
                                        <input type="date" class="form-control" id="fecha_actividad" name="fecha_actividad" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['fecha_actividad'] ?? '') ?>" 
                                               min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título de la Actividad *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['titulo'] ?? '') ?>" 
                                       placeholder="Describe brevemente tu propuesta..." required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción Detallada *</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required
                                          placeholder="Explica detalladamente tu propuesta: objetivos, metodología, beneficios esperados..."><?= htmlspecialchars($_SESSION['form_data']['descripcion'] ?? '') ?></textarea>
                                <div class="form-text">
                                    Incluye toda la información relevante para que los administradores puedan evaluar tu propuesta.
                                </div>
                            </div>
                            
                            <!-- Información adicional -->
                            <div class="alert alert-light">
                                <h6><i class="fas fa-info-circle me-2"></i>Proceso de Aprobación</h6>
                                <ul class="mb-0 small">
                                    <li>Tu propuesta será revisada por SuperAdmin, Gestor o tu Líder</li>
                                    <li>Recibirás notificación del estado de tu propuesta</li>
                                    <li>Si es aprobada, aparecerá en tus tareas pendientes</li>
                                    <li>Al completarla, recibirás los puntos correspondientes más 100 puntos de bonus</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Propuesta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // Limpiar datos del formulario después de mostrar
    unset($_SESSION['form_data']);
    ?>
</body>
</html>