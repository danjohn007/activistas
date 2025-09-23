<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Actividad - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
                    <h1 class="h2">Editar Actividad</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver al detalle
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

                <!-- Formulario de edición -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos de la Actividad</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="activity_id" value="<?= $activity['id'] ?>">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" 
                                               value="<?= htmlspecialchars($activity['titulo']) ?>" 
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
                                                        <?= $activity['tipo_actividad_id'] == $type['id'] ? 'selected' : '' ?>>
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
                                          maxlength="1000"><?= htmlspecialchars($activity['descripcion'] ?? '') ?></textarea>
                                <div class="form-text">Describe brevemente los objetivos y detalles de la actividad.</div>
                            </div>

                            <!-- Grupo (Opcional) -->
                            <div class="mb-3">
                                <label for="grupo" class="form-label">
                                    <i class="fas fa-users me-1"></i>Grupo (Opcional)
                                </label>
                                <input type="text" class="form-control" id="grupo" name="grupo" 
                                       value="<?= htmlspecialchars($activity['grupo'] ?? '') ?>"
                                       placeholder="Ej: GeneracionesVa, Grupo mujeres Lupita, Grupo Herman, Grupo Anita">
                                <div class="form-text">Asigna esta actividad a un grupo específico (opcional)</div>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_actividad" class="form-label">Fecha de la Actividad *</label>
                                <input type="date" class="form-control" id="fecha_actividad" name="fecha_actividad" 
                                       value="<?= date('Y-m-d', strtotime($activity['fecha_actividad'])) ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="programada" <?= $activity['estado'] === 'programada' ? 'selected' : '' ?>>Programada</option>
                                    <option value="en_progreso" <?= $activity['estado'] === 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                    <option value="completada" <?= $activity['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
                                    <option value="cancelada" <?= $activity['estado'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="evidence_files" class="form-label">Nuevas Evidencias (opcional)</label>
                                <input type="file" class="form-control" id="evidence_files" name="evidence_files[]" 
                                       accept="image/*,video/*,audio/*" multiple>
                                <div class="form-text">Puedes agregar más fotos, videos o audios relacionados con la actividad.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= url('activities/detail.php?id=' . $activity['id']) ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Información del Registro</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><small><strong>Creado:</strong> <?= formatDate($activity['fecha_creacion']) ?></small></p>
                                <p><small><strong>Responsable:</strong> <?= htmlspecialchars($activity['usuario_nombre']) ?></small></p>
                            </div>
                            <div class="col-md-6">
                                <?php if ($activity['fecha_actualizacion'] !== $activity['fecha_creacion']): ?>
                                    <p><small><strong>Última actualización:</strong> <?= formatDate($activity['fecha_actualizacion']) ?></small></p>
                                <?php endif; ?>
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