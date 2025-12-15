<?php
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Acceso directo no permitido');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cortes - Activistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .estado-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .estado-activo {
            background-color: #d1f2eb;
            color: #0f5132;
        }
        .estado-cerrado {
            background-color: #d3d3d4;
            color: #41464b;
        }
        .corte-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border-left: 4px solid #0d6efd;
        }
        .corte-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('mis_cortes'); 
            ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h2><i class="fas fa-camera me-2"></i>Mis Cortes Realizados</h2>
                        <p class="text-muted mb-0">Cortes históricos de mi equipo</p>
                    </div>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                    <?php 
                        echo htmlspecialchars($_SESSION['message']); 
                        unset($_SESSION['message'], $_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="mis_cortes.php" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="activo" <?php echo ($_GET['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="cerrado" <?php echo ($_GET['estado'] ?? '') === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" name="fecha_desde" class="form-control" 
                                       value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" 
                                       value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-secondary me-2">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="mis_cortes.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cortes List -->
                <?php if (empty($cortes)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No tienes cortes realizados</h5>
                            <p class="text-muted">Los cortes creados por el administrador aparecerán aquí</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($cortes as $corte): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card corte-card h-100" onclick="window.location.href='<?= url('reports/activists.php?corte_id=' . $corte['id']) ?>'">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-camera me-2"></i>
                                        <?php echo htmlspecialchars($corte['nombre']); ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($corte['descripcion'])): ?>
                                        <p class="text-muted small mb-3">
                                            <?php echo htmlspecialchars($corte['descripcion']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <strong><i class="far fa-calendar me-2"></i>Periodo:</strong>
                                        <br>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($corte['fecha_inicio'])); ?>
                                            al
                                            <?php echo date('d/m/Y', strtotime($corte['fecha_fin'])); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-6">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-primary"><?php echo $corte['total_activistas'] ?? 0; ?></h4>
                                                <small class="text-muted">Activistas</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-2">
                                                <?php 
                                                    $promedio = $corte['promedio_cumplimiento'] ?? 0;
                                                    $badgeClass = $promedio >= 80 ? 'success' : ($promedio >= 60 ? 'warning' : 'danger');
                                                ?>
                                                <h4 class="mb-0 text-<?php echo $badgeClass; ?>">
                                                    <?php echo number_format($promedio, 1); ?>%
                                                </h4>
                                                <small class="text-muted">Cumplimiento</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="estado-badge estado-<?php echo $corte['estado']; ?>">
                                            <?php echo ucfirst($corte['estado']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($corte['fecha_creacion'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <button class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-eye me-2"></i>Ver Reporte del Corte
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Info Box -->
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Sobre los Cortes:</strong>
                    Los cortes son snapshots históricos del desempeño de tu equipo en un periodo específico.
                    Haz clic en cualquier corte para ver el reporte detallado.
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
