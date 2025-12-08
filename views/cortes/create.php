<?php
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Acceso directo no permitido');
}

$formData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Corte de Periodo - Activistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <h2><i class="fas fa-plus-circle me-2"></i>Crear Corte de Periodo</h2>
                        <p class="text-muted mb-0">Genera un reporte histórico con datos congelados</p>
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

            <?php if (!empty($formErrors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Errores:</strong>
                <ul class="mb-0">
                    <?php foreach ($formErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="create.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Nombre -->
                        <div class="mb-3">
                            <label class="form-label required">Nombre del Corte</label>
                            <input type="text" name="nombre" class="form-control" 
                                   value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>"
                                   placeholder="Ej: Corte Enero 2025" required>
                            <small class="text-muted">Nombre identificador para el periodo</small>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"
                                      placeholder="Descripción opcional del corte"><?php echo htmlspecialchars($formData['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <!-- Filtros -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Filtrar por Grupo (Opcional)</label>
                                    <select name="grupo_id" class="form-select">
                                        <option value="">Todos los grupos</option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?php echo $group['id']; ?>"
                                                    <?php echo (($formData['grupo_id'] ?? '') == $group['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($group['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Solo activistas de este grupo</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Filtrar por Activista (Opcional)</label>
                                    <input type="text" 
                                           id="activista_search" 
                                           class="form-control" 
                                           placeholder="Buscar activista..."
                                           autocomplete="off">
                                    <input type="hidden" name="usuario_id" id="usuario_id_hidden" value="<?php echo $formData['usuario_id'] ?? ''; ?>">
                                    <div id="activista_results" class="list-group position-absolute" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none; width: calc(100% - 30px);"></div>
                                    <small class="text-muted">
                                        <span id="selected_activista">
                                            <?php 
                                            if (!empty($formData['usuario_id'])) {
                                                foreach ($activistas as $a) {
                                                    if ($a['id'] == $formData['usuario_id']) {
                                                        echo 'Seleccionado: ' . htmlspecialchars($a['nombre_completo']);
                                                        break;
                                                    }
                                                }
                                            } else {
                                                echo 'Todos los activistas';
                                            }
                                            ?>
                                        </span>
                                        <?php if (!empty($formData['usuario_id'])): ?>
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="clearActivista()">
                                                <i class="fas fa-times"></i> Limpiar
                                            </button>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Filtrar por Tipo de Actividad (Opcional)</label>
                                    <select name="actividad_id" class="form-select">
                                        <option value="">Todas las actividades</option>
                                        <?php foreach ($activityTypes as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"
                                                    <?php echo (($formData['actividad_id'] ?? '') == $type['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Solo tareas de este tipo</small>
                                </div>
                            </div>
                        </div>

                        <!-- Fechas -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Inicio del Periodo</label>
                                    <input type="date" name="fecha_inicio" class="form-control" 
                                           value="<?php echo htmlspecialchars($formData['fecha_inicio'] ?? ''); ?>"
                                           required>
                                    <small class="text-muted">Las tareas asignadas desde esta fecha se incluirán</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Fin del Periodo</label>
                                    <input type="date" name="fecha_fin" class="form-control" 
                                           value="<?php echo htmlspecialchars($formData['fecha_fin'] ?? ''); ?>"
                                           required>
                                    <small class="text-muted">Las tareas completadas hasta esta fecha se contarán</small>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Cómo funciona el corte:</h6>
                            <ul class="mb-0">
                                <li><strong>Filtros Opcionales:</strong> Puedes crear cortes de diferentes tipos:
                                    <ul>
                                        <li><strong>General:</strong> Sin filtros = Todos los activistas y todas las tareas</li>
                                        <li><strong>Por Grupo:</strong> Solo activistas de un grupo específico</li>
                                        <li><strong>Por Activista:</strong> Solo un activista individual</li>
                                        <li><strong>Por Actividad:</strong> Solo un tipo de tarea específica</li>
                                        <li><strong>Combinado:</strong> Puedes combinar filtros (ej: Grupo + Tipo de Actividad)</li>
                                    </ul>
                                </li>
                                <li><strong>Tareas Asignadas:</strong> Se contarán las tareas creadas entre la fecha inicio y fin.</li>
                                <li><strong>Tareas Entregadas:</strong> Se contarán las tareas completadas dentro del periodo.</li>
                                <li><strong>Datos Congelados:</strong> Los valores no cambiarán aunque se entreguen más tareas después.</li>
                                <li><strong>Ranking:</strong> Se calculará automáticamente según el porcentaje de cumplimiento.</li>
                            </ul>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Crear Corte
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Warning -->
            <div class="alert alert-warning mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Nota:</strong> El proceso de creación del corte puede tardar unos segundos 
                si hay muchos activistas. Los datos se calcularán para cada activista individualmente.
            </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lista de activistas
        const activistas = <?php echo json_encode($activistas); ?>;
        
        // Buscador de activistas
        const searchInput = document.getElementById('activista_search');
        const resultsDiv = document.getElementById('activista_results');
        const hiddenInput = document.getElementById('usuario_id_hidden');
        const selectedSpan = document.getElementById('selected_activista');
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }
            
            const filtered = activistas.filter(a => 
                a.nombre_completo.toLowerCase().includes(query)
            );
            
            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron resultados</div>';
                resultsDiv.style.display = 'block';
                return;
            }
            
            resultsDiv.innerHTML = filtered.map(a => `
                <a href="#" class="list-group-item list-group-item-action" onclick="selectActivista(${a.id}, '${a.nombre_completo.replace(/'/g, "\\'")}'); return false;">
                    <i class="fas fa-user me-2"></i>${a.nombre_completo}
                </a>
            `).join('');
            
            resultsDiv.style.display = 'block';
        });
        
        function selectActivista(id, nombre) {
            hiddenInput.value = id;
            searchInput.value = '';
            selectedSpan.innerHTML = 'Seleccionado: ' + nombre + ' <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="clearActivista()"><i class="fas fa-times"></i> Limpiar</button>';
            resultsDiv.style.display = 'none';
        }
        
        function clearActivista() {
            hiddenInput.value = '';
            searchInput.value = '';
            selectedSpan.textContent = 'Todos los activistas';
            resultsDiv.style.display = 'none';
        }
        
        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
