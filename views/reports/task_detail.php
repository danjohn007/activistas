<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Tarea - <?= htmlspecialchars($titulo) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .rank-badge {
            font-size: 1.2rem;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); }
        .user-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('reports'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <a href="<?= url('reports/global-tasks.php?fecha_desde=' . urlencode($filters['fecha_desde']) . '&fecha_hasta=' . urlencode($filters['fecha_hasta'])) ?>" 
                           class="btn btn-outline-secondary btn-sm mb-2">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Informe
                        </a>
                        <h1 class="h2">
                            <i class="fas fa-clipboard-check text-success me-2"></i><?= htmlspecialchars($titulo) ?>
                        </h1>
                        <?php if (!empty($taskDetails[0])): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($taskDetails[0]['tipo_actividad']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php
                    // Mostrar aviso de edición para roles con permisos
                    $canEdit = isset($GLOBALS['currentUser']) && in_array($GLOBALS['currentUser']['rol'], ['SuperAdmin', 'Gestor', 'Líder']);
                    if ($canEdit):
                    ?>
                        <div>
                            <span class="badge bg-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Puedes editar actividades individuales usando el botón "Editar"
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas de la tarea -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h4 class="text-info mb-0"><?= $stats['total_asignadas'] ?></h4>
                                <small class="text-muted">Asignadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h4 class="text-success mb-0"><?= $stats['total_completadas'] ?></h4>
                                <small class="text-muted">Completadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h4 class="text-warning mb-0"><?= $stats['total_pendientes'] ?></h4>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <h4 class="text-primary mb-0"><?= $stats['porcentaje_cumplimiento'] ?>%</h4>
                                <small class="text-muted">Cumplimiento</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-secondary">
                            <div class="card-body">
                                <h4 class="text-secondary mb-0"><?= $stats['tiempo_promedio_horas'] ?>h</h4>
                                <small class="text-muted">Tiempo Promedio</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <input type="hidden" name="titulo" value="<?= htmlspecialchars($titulo) ?>">
                            <input type="hidden" name="tipo_actividad_id" value="<?= htmlspecialchars($tipoActividadId) ?>">
                            
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha Desde
                                </label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($filters['fecha_desde']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha Hasta
                                </label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="<?= htmlspecialchars($filters['fecha_hasta']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">
                                    <i class="fas fa-filter me-1"></i>Estado
                                </label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="completada" <?= ($filters['estado'] ?? '') === 'completada' ? 'selected' : '' ?>>Completadas</option>
                                    <option value="programada" <?= ($filters['estado'] ?? '') === 'programada' ? 'selected' : '' ?>>Pendientes</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="nombre_activista" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nombre de Activista
                                </label>
                                <input type="text" class="form-control" id="nombre_activista" name="nombre_activista" 
                                       value="<?= htmlspecialchars($filters['nombre_activista'] ?? '') ?>" 
                                       placeholder="Buscar por nombre...">
                            </div>
                            <div class="col-md-4">
                                <label for="grupo_id" class="form-label">
                                    <i class="fas fa-users me-1"></i>Grupo
                                </label>
                                <select class="form-select" id="grupo_id" name="grupo_id">
                                    <option value="">Todos los grupos</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?= $grupo['id'] ?>" <?= ($filters['grupo_id'] ?? '') == $grupo['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($grupo['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="lider_id" class="form-label">
                                    <i class="fas fa-user-tie me-1"></i>Lider
                                </label>
                                <select class="form-select" id="lider_id" name="lider_id">
                                    <option value="">Todos los lideres</option>
                                    <?php foreach ($lideres as $lider): ?>
                                        <option value="<?= $lider['id'] ?>" <?= ($filters['lider_id'] ?? '') == $lider['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lider['nombre_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Filtrar
                                </button>
                                <?php if (!empty($filters['nombre_activista']) || !empty($filters['grupo_id']) || !empty($filters['lider_id']) || !empty($filters['estado'])): ?>
                                    <a href="?titulo=<?= urlencode($titulo) ?>&tipo_actividad_id=<?= urlencode($tipoActividadId) ?>&fecha_desde=<?= urlencode($filters['fecha_desde']) ?>&fecha_hasta=<?= urlencode($filters['fecha_hasta']) ?>" 
                                       class="btn btn-secondary w-100 mt-2">
                                        <i class="fas fa-times me-1"></i>Limpiar Filtros
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de usuarios -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Usuarios Asignados
                            <small class="text-muted">(Ordenados por fecha de entrega)</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($taskDetails)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay usuarios asignados a esta tarea</h5>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php 
                                $position = 0;
                                foreach ($taskDetails as $detail): 
                                    if ($detail['estado'] === 'completada') {
                                        $position++;
                                    }
                                ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card user-card h-100 <?= $detail['estado'] === 'completada' ? 'border-success' : 'border-warning' ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?= htmlspecialchars($detail['usuario_nombre']) ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <?= htmlspecialchars($detail['usuario_email']) ?>
                                                        </small>
                                                        <?php if (!empty($detail['usuario_telefono'])): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-phone me-1"></i>
                                                                <?= htmlspecialchars($detail['usuario_telefono']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($detail['grupo_nombre'])): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-users me-1"></i>
                                                                <?= htmlspecialchars($detail['grupo_nombre']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($detail['lider_nombre'])): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-user-tie me-1"></i>
                                                                Líder: <?= htmlspecialchars($detail['lider_nombre']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($detail['estado'] === 'completada' && $position <= 3): ?>
                                                        <span class="rank-badge rank-<?= $position ?>">
                                                            <?= $position ?>°
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <hr>

                                                <div class="mb-2">
                                                    <strong>Estado:</strong>
                                                    <?php
                                                    $estadoBadge = [
                                                        'completada' => 'success',
                                                        'en_progreso' => 'warning',
                                                        'programada' => 'primary',
                                                        'cancelada' => 'danger'
                                                    ][$detail['estado']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $estadoBadge ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $detail['estado'])) ?>
                                                    </span>
                                                </div>

                                                <div class="mb-2">
                                                    <small>
                                                        <i class="fas fa-calendar-plus me-1 text-primary"></i>
                                                        <strong>Asignada:</strong> 
                                                        <?= date('d/m/Y H:i', strtotime($detail['fecha_asignacion'])) ?>
                                                    </small>
                                                </div>

                                                <?php if ($detail['estado'] === 'completada' && $detail['fecha_actualizacion']): ?>
                                                    <div class="mb-2">
                                                        <small>
                                                            <i class="fas fa-check-circle me-1 text-success"></i>
                                                            <strong>Completada:</strong> 
                                                            <?= date('d/m/Y H:i', strtotime($detail['fecha_actualizacion'])) ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($detail['horas_para_completar'] !== null): ?>
                                                        <div class="mb-2">
                                                            <small>
                                                                <i class="fas fa-clock me-1 text-info"></i>
                                                                <strong>Tiempo:</strong> 
                                                                <?= round($detail['horas_para_completar'], 1) ?> horas
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (!empty($detail['asignado_por'])): ?>
                                                    <div class="mb-2">
                                                        <small>
                                                            <i class="fas fa-user-tag me-1 text-secondary"></i>
                                                            <strong>Asignado por:</strong> 
                                                            <?= htmlspecialchars($detail['asignado_por']) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($detail['total_evidencias'] > 0): ?>
                                                    <div class="mb-2">
                                                        <small>
                                                            <i class="fas fa-paperclip me-1 text-warning"></i>
                                                            <strong>Evidencias:</strong> 
                                                            <?= $detail['total_evidencias'] ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mt-3">
                                                    <div class="d-grid gap-2">
                                                        <a href="<?= url('activities/detail.php?id=' . $detail['id']) ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>Ver Completa
                                                        </a>
                                                        <?php
                                                        // Solo SuperAdmin, Gestor y Líder pueden editar
                                                        if (isset($GLOBALS['currentUser']) && in_array($GLOBALS['currentUser']['rol'], ['SuperAdmin', 'Gestor', 'Líder'])):
                                                        ?>
                                                            <a href="<?= url('activities/edit.php?id=' . $detail['id']) ?>" 
                                                               class="btn btn-sm btn-warning"
                                                               onclick="return confirm('¿Estás seguro de que deseas editar la actividad de <?= htmlspecialchars($detail['usuario_nombre']) ?>?\n\nNota: Los cambios afectarán solo a esta actividad individual.')"
                                                               title="Editar actividad de <?= htmlspecialchars($detail['usuario_nombre']) ?>">
                                                                <i class="fas fa-edit me-1"></i>Editar
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
