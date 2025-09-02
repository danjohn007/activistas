<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('admin'); 
            ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-calendar-alt text-warning me-2"></i><?= htmlspecialchars($title) ?>
                    </h1>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmación de Reset Mensual
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-info-circle me-2"></i>¿Qué hace esta acción?</h6>
                                    <ul class="mb-0">
                                        <li>Guarda el ranking actual en el historial mensual</li>
                                        <li>Reinicia los puntos de ranking de todos los usuarios a 0</li>
                                        <li>Permite que empiece un nuevo período de ranking</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-calendar me-2"></i>Fecha Actual</h6>
                                    <p class="mb-0">
                                        <strong><?= date('d/m/Y H:i:s') ?></strong><br>
                                        <small class="text-muted">Los rankings se guardarán para <?= date('F Y') ?></small>
                                    </p>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?= url('ranking/') ?>" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <button type="submit" class="btn btn-warning" 
                                                onclick="return confirm('¿Estás seguro de que quieres reiniciar el ranking mensual? Esta acción no se puede deshacer.')">
                                            <i class="fas fa-refresh me-1"></i>Confirmar Reset
                                        </button>
                                    </form>
                                </div>
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