<?php
// Partial para mostrar una tarjeta de tarea
$esCompletada = $tarea['estado'] === 'completada';
$cardClass = $esCompletada ? 'task-completada' : 'task-pendiente';
?>

<div class="card task-card <?php echo $cardClass; ?>">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h5 class="card-title">
                    <?php if ($esCompletada): ?>
                        <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                        <i class="fas fa-clock text-danger"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($tarea['tipo_actividad'] ?? 'Actividad'); ?>
                </h5>
                
                <p class="card-text mb-2">
                    <strong>Descripcion:</strong> <?php echo htmlspecialchars($tarea['descripcion'] ?? 'Sin descripcion'); ?>
                </p>
                
                <div class="row g-2">
                    <div class="col-auto">
                        <small class="text-muted">
                            <i class="far fa-calendar"></i> Asignada: 
                            <?php echo date('d/m/Y', strtotime($tarea['fecha_creacion'])); ?>
                        </small>
                    </div>
                    
                    <?php if ($tarea['fecha_cierre']): ?>
                    <div class="col-auto">
                        <small class="text-muted">
                            <i class="far fa-calendar-times"></i> Vence: 
                            <?php echo date('d/m/Y', strtotime($tarea['fecha_cierre'])); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($esCompletada && $tarea['fecha_actualizacion']): ?>
                    <div class="col-auto">
                        <small class="text-success">
                            <i class="fas fa-check"></i> Completada: 
                            <?php echo date('d/m/Y H:i', strtotime($tarea['fecha_actualizacion'])); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($tarea['comentario'])): ?>
                <div class="mt-2">
                    <small><strong>Comentario del activista:</strong></small>
                    <p class="mb-0"><small><?php echo nl2br(htmlspecialchars($tarea['comentario'])); ?></small></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <?php if (!empty($tarea['evidences']) && count($tarea['evidences']) > 0): ?>
                    <div class="text-center">
                        <?php foreach ($tarea['evidences'] as $evidence): ?>
                            <?php 
                            // Construir path correcto
                            $archivo = $evidence['archivo'];
                            // Si no tiene path completo, agregarlo
                            if (strpos($archivo, 'assets/') !== 0 && strpos($archivo, 'public/') !== 0) {
                                $archivo = 'assets/uploads/evidencias/' . $archivo;
                            }
                            // Si tiene public/ al inicio, quitarlo
                            if (strpos($archivo, 'public/') === 0) {
                                $archivo = substr($archivo, 7);
                            }
                            ?>
                            <?php if ($evidence['tipo_evidencia'] === 'foto'): ?>
                                <img src="<?= url($archivo) ?>" 
                                     alt="Evidencia" 
                                     class="evidence-image img-thumbnail mb-2"
                                     style="max-width: 100%; cursor: pointer;"
                                     onclick="window.open('<?= url($archivo) ?>', '_blank')"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EImagen no encontrada%3C/text%3E%3C/svg%3E';">
                                <br>
                            <?php elseif ($evidence['tipo_evidencia'] === 'video'): ?>
                                <video controls style="max-width: 100%; max-height: 200px;" class="rounded mb-2">
                                    <source src="<?= url($archivo) ?>" type="video/mp4">
                                </video>
                                <br>
                            <?php elseif ($evidence['tipo_evidencia'] === 'audio'): ?>
                                <audio controls style="max-width: 100%;" class="mb-2">
                                    <source src="<?= url($archivo) ?>" type="audio/mpeg">
                                </audio>
                                <br>
                            <?php elseif ($evidence['tipo_evidencia'] === 'comentario'): ?>
                                <div class="alert alert-info mb-2 text-start">
                                    <i class="fas fa-comment me-1"></i>
                                    <small><?= nl2br(htmlspecialchars($evidence['contenido'])) ?></small>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <small class="text-muted">Click para ampliar</small>
                    </div>
                <?php else: ?>
                    <?php if (!$esCompletada): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-image fa-3x mb-2"></i>
                            <br>
                            <small>Sin evidencia</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-2 d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-<?php echo $esCompletada ? 'success' : 'danger'; ?>">
                    <?php echo $tarea['estado_texto']; ?>
                </span>
                
                <?php if (!empty($tarea['puntos']) && $tarea['puntos'] > 0): ?>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-star"></i> <?php echo $tarea['puntos']; ?> puntos
                    </span>
                <?php endif; ?>
            </div>
            
            <a href="../activities/detail.php?id=<?php echo $tarea['id']; ?>" 
               class="btn btn-sm btn-outline-primary" 
               target="_blank">
                <i class="fas fa-eye"></i> Ver Detalle
            </a>
        </div>
    </div>
</div>
