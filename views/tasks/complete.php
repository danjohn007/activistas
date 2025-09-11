<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Tarea - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .task-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #28a745;
        }
        .evidence-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            border: 2px dashed #dee2e6;
            transition: border-color 0.3s;
        }
        .evidence-section:hover {
            border-color: #28a745;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <h4><i class="fas fa-clipboard-check me-2"></i>Completar</h4>
                        <small><?= htmlspecialchars($currentUser['nombre_completo']) ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('dashboards/activista.php') ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('activities/') ?>">
                                <i class="fas fa-tasks me-2"></i>Mis Actividades
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('tasks/') ?>">
                                <i class="fas fa-clipboard-list me-2"></i>Tareas Pendientes
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('ranking/') ?>">
                                <i class="fas fa-trophy me-2"></i>Ranking
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= url('profile.php') ?>">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= url('logout.php') ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-clipboard-check text-success me-2"></i>Completar Tarea
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?= url('tasks/') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver a Tareas
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

                <!-- Información de la tarea -->
                <div class="card task-info mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="card-title text-success">
                                    <i class="fas fa-tasks me-2"></i><?= htmlspecialchars($task['titulo']) ?>
                                </h4>
                                <?php if (!empty($task['descripcion'])): ?>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($task['descripcion'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <div class="text-md-end">
                                    <div class="mb-2">
                                        <small class="text-muted">Tipo:</small><br>
                                        <span class="badge bg-primary"><?= htmlspecialchars($task['tipo_nombre']) ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Asignado por:</small><br>
                                        <strong><?= htmlspecialchars($task['solicitante_nombre'] ?? 'N/A') ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Fecha actividad:</small><br>
                                        <strong><?= date('d/m/Y', strtotime($task['fecha_actividad'])) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advertencia importante -->
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>¡Importante!</strong> Una vez que subas la evidencia, esta tarea se marcará como completada 
                    automáticamente y <strong>no podrás modificar la evidencia</strong>. Se registrará la hora exacta 
                    de finalización para el cálculo del ranking.
                </div>

                <!-- Formulario de evidencia -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-upload me-2"></i>Subir Evidencia
                        </h5>
                    </div>
                    <div class="card-body evidence-section">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_evidencia" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Tipo de Evidencia *
                                    </label>
                                    <select class="form-select" id="tipo_evidencia" name="tipo_evidencia" required>
                                        <option value="">Selecciona el tipo...</option>
                                        <option value="foto">📷 Foto</option>
                                        <option value="video">🎥 Video</option>
                                        <option value="audio">🎵 Audio</option>
                                        <option value="comentario">💬 Comentario/Texto</option>
                                        <option value="live">📹 Transmisión en vivo</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="archivo" class="form-label">
                                        <i class="fas fa-file me-1"></i>Archivo (Obligatorio) *
                                    </label>
                                    <input type="file" class="form-control" id="archivo" name="archivo" 
                                           accept="image/*,video/*,audio/*" required>
                                    <div class="form-text">
                                        Máximo 20MB. Formatos: JPG, PNG, GIF, MP4, MP3, WAV
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="contenido" class="form-label">
                                    <i class="fas fa-comment me-1"></i>Descripción de la Evidencia *
                                </label>
                                <textarea class="form-control" id="contenido" name="contenido" rows="5" 
                                         placeholder="Describe detalladamente la actividad realizada, resultados obtenidos, etc." 
                                         required></textarea>
                                <div class="form-text">
                                    Proporciona una descripción detallada de la evidencia que estás subiendo.
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Consejos para una buena evidencia:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Incluye detalles específicos sobre lo que hiciste</li>
                                    <li>Si es una foto/video, asegúrate de que se vea claramente</li>
                                    <li>Menciona resultados obtenidos (alcance, participación, etc.)</li>
                                    <li>Sé honesto y preciso en tu descripción</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= url('tasks/') ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-1"></i>Completar Tarea
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar placeholder del textarea según el tipo de evidencia
        document.getElementById('tipo_evidencia').addEventListener('change', function() {
            const textarea = document.getElementById('contenido');
            const tipo = this.value;
            
            switch(tipo) {
                case 'foto':
                    textarea.placeholder = 'Describe qué se muestra en la foto, dónde fue tomada, resultados obtenidos...';
                    break;
                case 'video':
                    textarea.placeholder = 'Describe el contenido del video, duración, personas que participaron, resultados...';
                    break;
                case 'audio':
                    textarea.placeholder = 'Describe el contenido del audio, contexto, participantes, resultados...';
                    break;
                case 'comentario':
                    textarea.placeholder = 'Describe detalladamente la actividad realizada, acciones tomadas, resultados obtenidos...';
                    break;
                case 'live':
                    textarea.placeholder = 'Describe la transmisión en vivo: plataforma, duración, alcance, interacciones...';
                    break;
                default:
                    textarea.placeholder = 'Describe detalladamente la actividad realizada, resultados obtenidos, etc.';
            }
        });
        
        // Confirmación antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que deseas completar esta tarea? Una vez enviada la evidencia no podrás modificarla.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>