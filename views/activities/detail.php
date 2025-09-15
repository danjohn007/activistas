<?php
require_once __DIR__ . '/../../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Actividad - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .evidence-item {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
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
                    <h1 class="h2"><?= htmlspecialchars($activity['titulo']) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('activities/') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver a la lista
                            </a>
                            <a href="<?= url('activities/edit.php?id=' . $activity['id']) ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i>Editar
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

                <div class="row">
                    <!-- Información principal -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información de la Actividad</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tipo:</strong> <?= htmlspecialchars($activity['tipo_nombre']) ?></p>
                                        <p><strong>Fecha:</strong> <?= formatDate($activity['fecha_actividad'], 'd/m/Y') ?></p>
                                        <p><strong>Lugar:</strong> <?= htmlspecialchars($activity['lugar'] ?? 'No especificado') ?></p>
                                        <?php if (!empty($activity['fecha_cierre'])): ?>
                                            <p><strong>Vigencia:</strong> 
                                                <?= formatDate($activity['fecha_cierre'], 'd/m/Y') ?>
                                                <?php if (!empty($activity['hora_cierre'])): ?>
                                                    <?= date('H:i', strtotime($activity['hora_cierre'])) ?>
                                                <?php endif; ?>
                                                <?php 
                                                // Mostrar indicador de urgencia en detalle
                                                if (!empty($activity['fecha_cierre'])) {
                                                    $today = new DateTime();
                                                    $closeDate = new DateTime($activity['fecha_cierre']);
                                                    if (!empty($activity['hora_cierre'])) {
                                                        $closeDate->setTime(...explode(':', $activity['hora_cierre']));
                                                    }
                                                    $diff = $today->diff($closeDate);
                                                    $urgencyDays = $closeDate > $today ? $diff->days : -$diff->days;
                                                    
                                                    if ($urgencyDays <= 1 && $closeDate > $today) {
                                                        echo '<span class="badge bg-danger ms-2">' . ($urgencyDays == 0 ? 'Vence hoy' : 'Vence mañana') . '</span>';
                                                    } elseif ($closeDate <= $today) {
                                                        echo '<span class="badge bg-danger ms-2">Vencida</span>';
                                                    } elseif ($urgencyDays <= 3) {
                                                        echo '<span class="badge bg-warning text-dark ms-2">Vence en ' . $urgencyDays . ' días</span>';
                                                    }
                                                }
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Estado:</strong> 
                                            <?php
                                            $badgeClass = [
                                                'completada' => 'success',
                                                'en_progreso' => 'warning',
                                                'programada' => 'primary',
                                                'cancelada' => 'danger'
                                            ][$activity['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $activity['estado'])) ?>
                                            </span>
                                        </p>
                                        <p><strong>Responsable:</strong> <?= htmlspecialchars($activity['usuario_nombre']) ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($activity['descripcion'])): ?>
                                    <hr>
                                    <h6>Descripción:</h6>
                                    <p><?= nl2br(htmlspecialchars($activity['descripcion'])) ?></p>
                                <?php endif; ?>
                                
                                <!-- Enlaces relacionados -->
                                <?php if (!empty($activity['enlace_1']) || !empty($activity['enlace_2'])): ?>
                                    <hr>
                                    <h6><i class="fas fa-link me-2"></i>Enlaces relacionados:</h6>
                                    <div class="mb-2">
                                        <?php if (!empty($activity['enlace_1'])): ?>
                                            <div class="mb-2">
                                                <a href="<?= htmlspecialchars($activity['enlace_1']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-external-link-alt me-1"></i>Enlace 1
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($activity['enlace_2'])): ?>
                                            <div class="mb-2">
                                                <a href="<?= htmlspecialchars($activity['enlace_2']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-external-link-alt me-1"></i>Enlace 2
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Mostrar imágenes de referencia si es una tarea pendiente -->
                                <?php if (!empty($activity['tarea_pendiente']) && !empty($activity['solicitante_nombre'])): ?>
                                    <hr>
                                    <h6>Información de la Tarea:</h6>
                                    <p><strong>Asignada por:</strong> <?= htmlspecialchars($activity['solicitante_nombre']) ?></p>
                                    
                                    <!-- Mostrar evidencias iniciales (imágenes de referencia) -->
                                    <?php 
                                    // Obtener evidencias iniciales (bloqueada = 0) para mostrar imágenes de referencia
                                    if (!empty($evidence)) {
                                        $initialEvidence = array_filter($evidence, function($e) {
                                            return empty($e['bloqueada']) || $e['bloqueada'] == 0;
                                        });
                                        
                                        if (!empty($initialEvidence)): ?>
                                            <div class="mt-3">
                                                <h6>Archivos de Referencia:</h6>
                                                <div class="row">
                                                    <?php foreach ($initialEvidence as $item): ?>
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <h6 class="card-title">
                                                                        <i class="fas fa-<?= $item['tipo_evidencia'] === 'foto' ? 'image' : ($item['tipo_evidencia'] === 'video' ? 'video' : 'file') ?> me-2"></i>
                                                                        <?= ucfirst($item['tipo_evidencia']) ?>
                                                                    </h6>
                                                                    <?php if (!empty($item['archivo'])): ?>
                                                                        <?php if (in_array($item['tipo_evidencia'], ['foto', 'image'])): ?>
                                                                            <img src="<?= url('assets/uploads/evidencias/' . basename($item['archivo'])) ?>" 
                                                                                 class="img-fluid rounded mb-2" 
                                                                                 alt="Imagen de referencia"
                                                                                 style="max-height: 200px; object-fit: cover;">
                                                                        <?php endif; ?>
                                                                        <p><small><strong>Archivo:</strong> <?= htmlspecialchars(basename($item['archivo'])) ?></small></p>
                                                                        <a href="<?= url('assets/uploads/evidencias/' . basename($item['archivo'])) ?>" 
                                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                                            <i class="fas fa-download me-1"></i>Descargar
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['contenido'])): ?>
                                                                        <p class="mt-2"><small><?= nl2br(htmlspecialchars($item['contenido'])) ?></small></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Evidencias -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Evidencias</h5>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEvidenceModal">
                                    <i class="fas fa-plus me-1"></i>Agregar Evidencia
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($evidence)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No hay evidencias registradas</h6>
                                        <p class="text-muted">Agrega fotos, videos o documentos para respaldar esta actividad.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($evidence as $item): ?>
                                        <div class="evidence-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6>
                                                        <i class="fas fa-<?= $item['tipo_evidencia'] === 'foto' ? 'image' : ($item['tipo_evidencia'] === 'video' ? 'video' : 'file') ?> me-2"></i>
                                                        <?= ucfirst($item['tipo_evidencia']) ?>
                                                    </h6>
                                                    <?php if (!empty($item['archivo'])): ?>
                                                        <?php if (in_array($item['tipo_evidencia'], ['foto', 'image'])): ?>
                                                            <img src="<?= url('assets/uploads/evidencias/' . basename($item['archivo'])) ?>" 
                                                                 class="img-fluid rounded mb-2" 
                                                                 alt="Evidencia"
                                                                 style="max-height: 300px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <p class="mb-1"><strong>Archivo:</strong> <?= htmlspecialchars(basename($item['archivo'])) ?></p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['contenido'])): ?>
                                                        <p class="mb-1"><?= nl2br(htmlspecialchars($item['contenido'])) ?></p>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        Subido el <?= formatDate($item['fecha_subida']) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($item['archivo'])): ?>
                                                    <div>
                                                        <a href="<?= url('assets/uploads/evidencias/' . basename($item['archivo'])) ?>" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar con información adicional -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Información del Registro</h6>
                            </div>
                            <div class="card-body">
                                <p><small><strong>Creado:</strong> <?= formatDate($activity['fecha_creacion']) ?></small></p>
                                <?php if ($activity['fecha_actualizacion'] !== $activity['fecha_creacion']): ?>
                                    <p><small><strong>Actualizado:</strong> <?= formatDate($activity['fecha_actualizacion']) ?></small></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Acciones Rápidas</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="<?= url('activities/edit.php?id=' . $activity['id']) ?>" class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>Editar Actividad
                                    </a>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEvidenceModal">
                                        <i class="fas fa-plus me-2"></i>Agregar Evidencia
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para agregar evidencia -->
    <div class="modal fade" id="addEvidenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('activities/add_evidence.php') ?>" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Evidencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="activity_id" value="<?= $activity['id'] ?>">
                        
                        <div class="mb-3">
                            <label for="evidence_type" class="form-label">Tipo de Evidencia</label>
                            <select class="form-select" id="evidence_type" name="evidence_type" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="foto">Foto</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                                <option value="documento">Documento</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="evidence_file" class="form-label">Archivo</label>
                            <input type="file" class="form-control" id="evidence_file" name="evidence_file">
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Descripción</label>
                            <textarea class="form-control" id="content" name="content" rows="3" 
                                      placeholder="Describe brevemente esta evidencia..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Evidencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>