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
    <title><?php echo htmlspecialchars($corte['nombre']); ?> - Cortes de Periodo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card {
            border-left: 4px solid;
        }
        .ranking-badge {
            font-size: 1.1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .ranking-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .ranking-2 { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: white; }
        .ranking-3 { background: linear-gradient(135deg, #CD7F32, #A0522D); color: white; }
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
                        <h2><i class="fas fa-chart-line me-2"></i><?php echo htmlspecialchars($corte['nombre']); ?></h2>
                        <p class="text-muted mb-0">
                            <i class="far fa-calendar"></i> 
                            Periodo: <?php echo date('d/m/Y', strtotime($corte['fecha_inicio'])); ?> 
                            al <?php echo date('d/m/Y', strtotime($corte['fecha_fin'])); ?>
                        </p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
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

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card" style="border-left-color: #0d6efd;">
                        <div class="card-body">
                            <h6 class="text-muted">Total Activistas</h6>
                            <h3><?php echo $corte['total_activistas'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card" style="border-left-color: #198754;">
                        <div class="card-body">
                            <h6 class="text-muted">Promedio Cumplimiento</h6>
                            <h3><?php echo number_format($corte['promedio_cumplimiento'] ?? 0, 1); ?>%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card" style="border-left-color: #6c757d;">
                        <div class="card-body">
                            <h6 class="text-muted">Estado</h6>
                            <h5><span class="badge bg-<?php echo $corte['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($corte['estado']); ?>
                            </span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card" style="border-left-color: #ffc107;">
                        <div class="card-body">
                            <h6 class="text-muted">Fecha Creación</h6>
                            <h6><?php echo date('d/m/Y H:i', strtotime($corte['fecha_creacion'])); ?></h6>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($corte['descripcion'])): ?>
            <div class="alert alert-info mb-4">
                <strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($corte['descripcion'])); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($corte['grupo_nombre']) || !empty($corte['activista_nombre']) || !empty($corte['actividad_nombre'])): ?>
            <div class="alert alert-primary mb-4">
                <h6 class="alert-heading"><i class="fas fa-filter me-2"></i>Filtros Aplicados:</h6>
                <ul class="mb-0">
                    <?php if (!empty($corte['grupo_nombre'])): ?>
                        <li><strong>Grupo:</strong> <?php echo htmlspecialchars($corte['grupo_nombre']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($corte['activista_nombre'])): ?>
                        <li><strong>Activista:</strong> <?php echo htmlspecialchars($corte['activista_nombre']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($corte['actividad_nombre'])): ?>
                        <li><strong>Tipo de Actividad:</strong> <?php echo htmlspecialchars($corte['actividad_nombre']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Search -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="detail.php" class="row g-3">
                        <input type="hidden" name="id" value="<?php echo $corte['id']; ?>">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Buscar activista por nombre..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <?php if (!empty($_GET['search'])): ?>
                            <a href="detail.php?id=<?php echo $corte['id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Detail Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($detalle)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay datos para mostrar en este corte</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ranking</th>
                                        <th>Activista</th>
                                        <th>Tareas Asignadas</th>
                                        <th>Tareas Entregadas</th>
                                        <th>Cumplimiento</th>
                                        <th>Fecha Calculo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detalle as $row): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                                $ranking = $row['ranking_posicion'] ?? 999;
                                                $rankingClass = '';
                                                if ($ranking == 1) $rankingClass = 'ranking-1';
                                                elseif ($ranking == 2) $rankingClass = 'ranking-2';
                                                elseif ($ranking == 3) $rankingClass = 'ranking-3';
                                            ?>
                                            <span class="ranking-badge <?php echo $rankingClass; ?>">
                                                <?php echo $ranking; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nombre_completo']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> ID: <?php echo $row['usuario_id']; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $row['tareas_asignadas']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $row['tareas_entregadas']; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $cumplimiento = $row['porcentaje_cumplimiento'] ?? 0;
                                                $badgeClass = $cumplimiento >= 80 ? 'success' : ($cumplimiento >= 60 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>" style="font-size: 1rem;">
                                                <?php echo number_format($cumplimiento, 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($row['fecha_calculo'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="tareas_activista.php?corte_id=<?php echo $corte['id']; ?>&usuario_id=<?php echo $row['usuario_id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Ver Tareas">
                                                <i class="fas fa-list"></i> Detalles
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Freeze Notice -->
            <div class="alert alert-warning mt-4">
                <i class="fas fa-snowflake me-2"></i>
                <strong>Datos Congelados:</strong> Estos datos fueron calculados el 
                <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($corte['fecha_creacion'])); ?> 
                y permanecen inalterados. Las entregas posteriores no afectan este reporte.
            </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
