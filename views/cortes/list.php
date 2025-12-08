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
    <title>Cortes de Periodo - Activistas</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('cortes'); 
            ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h2><i class="fas fa-chart-line me-2"></i>Cortes de Periodo</h2>
                        <p class="text-muted mb-0">Reportes históricos con datos congelados</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="create.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Nuevo Corte
                        </a>
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
                    <form method="GET" action="index.php" class="row g-3">
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
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($cortes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay cortes de periodo creados</p>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Corte
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Periodo</th>
                                        <th>Activistas</th>
                                        <th>Promedio Cumplimiento</th>
                                        <th>Estado</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cortes as $corte): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($corte['nombre']); ?></strong>
                                            <?php if (!empty($corte['descripcion'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($corte['descripcion']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($corte['grupo_nombre'])): ?>
                                                <br><span class="badge bg-primary"><i class="fas fa-users"></i> <?php echo htmlspecialchars($corte['grupo_nombre']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($corte['activista_nombre'])): ?>
                                                <br><span class="badge bg-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($corte['activista_nombre']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($corte['actividad_nombre'])): ?>
                                                <br><span class="badge bg-secondary"><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($corte['actividad_nombre']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="far fa-calendar"></i> 
                                                <?php echo date('d/m/Y', strtotime($corte['fecha_inicio'])); ?>
                                                <br>al <?php echo date('d/m/Y', strtotime($corte['fecha_fin'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $corte['total_activistas'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $promedio = $corte['promedio_cumplimiento'] ?? 0;
                                                $badgeClass = $promedio >= 80 ? 'success' : ($promedio >= 60 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo number_format($promedio, 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="estado-badge estado-<?php echo $corte['estado']; ?>">
                                                <?php echo ucfirst($corte['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($corte['fecha_creacion'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="detail.php?id=<?php echo $corte['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Ver Detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($corte['estado'] === 'activo' && $currentUser['rol'] === 'SuperAdmin'): ?>
                                            <form method="POST" action="cerrar.php" style="display: inline;" 
                                                  onsubmit="return confirm('¿Cerrar este corte? No podrá ser modificado.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="corte_id" value="<?php echo $corte['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Cerrar Corte">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($currentUser['rol'] === 'SuperAdmin'): ?>
                                            <form method="POST" action="delete.php" style="display: inline;" 
                                                  onsubmit="return confirm('¿Eliminar este corte? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="corte_id" value="<?php echo $corte['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Sobre los Cortes de Periodo:</strong>
                Los datos de cada corte se congelan al momento de su creación y nunca se actualizan, 
                permitiendo consultas históricas precisas sin importar las entregas futuras.
            </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
