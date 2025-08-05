<?php
// Incluir configuración de la aplicación
require_once __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="error-container d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card error-card">
                        <div class="card-body p-5 text-center">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                            <h1 class="display-4 mt-3">404</h1>
                            <h4 class="text-muted mb-4">Página no encontrada</h4>
                            <p class="text-muted mb-4">
                                La página que estás buscando no existe o ha sido movida.
                            </p>
                            <a href="<?= url('') ?>" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Volver al inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>