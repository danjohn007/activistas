<?php
/**
 * Vista del Historial de Rankings
 */

// Incluir header
include __DIR__ . '/../layouts/header.php';

// Helper function for month names
function getMonthName($month) {
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $months[$month] ?? 'Mes Desconocido';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-history me-2"></i>Historial de Rankings
                    </h1>
                    <p class="text-muted mb-0">Visualiza todos los cortes de ranking históricos con los primeros lugares</p>
                </div>
                <div>
                    <a href="<?= url('ranking/') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>Volver al Ranking Actual
                    </a>
                </div>
            </div>

            <?php if (empty($rankingCuts)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Sin historial disponible</strong><br>
                    No se han encontrado cortes de ranking históricos. Los rankings se guardan automáticamente cuando se realiza un reset mensual.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($rankingCuts as $cut): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?= getMonthName($cut['mes']) ?> <?= $cut['anio'] ?>
                                    </h5>
                                    <small class="opacity-75">
                                        <i class="fas fa-clock me-1"></i>
                                        Corte realizado el <?= formatDate($cut['fecha_corte']) ?>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i>
                                            <?= $cut['total_usuarios'] ?> participantes
                                        </small>
                                    </div>
                                    
                                    <!-- Top 3 del corte -->
                                    <?php if (!empty($cut['top_3'])): ?>
                                        <h6 class="mb-3">
                                            <i class="fas fa-trophy me-1"></i>Top 3
                                        </h6>
                                        <div class="ranking-preview">
                                            <?php foreach (array_slice($cut['top_3'], 0, 3) as $index => $user): ?>
                                                <div class="d-flex align-items-center mb-2 position-<?= $index + 1 ?>">
                                                    <div class="position-badge-small me-2">
                                                        <?php if ($index === 0): ?>
                                                            <i class="fas fa-crown text-warning"></i>
                                                        <?php elseif ($index === 1): ?>
                                                            <i class="fas fa-medal text-secondary"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-award text-warning"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-bold"><?= htmlspecialchars($user['nombre_completo']) ?></div>
                                                        <small class="text-muted">
                                                            <?= number_format($user['puntos']) ?> pts - 
                                                            <?= $user['actividades_completadas'] ?> tareas
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-3">
                                            <i class="fas fa-exclamation-triangle"></i><br>
                                            No hay datos del top 3 para este período
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="<?= url('ranking/?year=' . $cut['anio'] . '&month=' . $cut['mes']) ?>" 
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-eye me-1"></i>Ver Top 20 Completo
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.position-badge-small {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.ranking-preview .position-1 {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 8px !important;
}

.ranking-preview .position-2 {
    background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 8px !important;
}

.ranking-preview .position-3 {
    background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 8px !important;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>