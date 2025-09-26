<?php
/**
 * Gestión de Grupos - SuperAdmin
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/user.php';
require_once __DIR__ . '/../../models/group.php';
require_once __DIR__ . '/../../includes/functions.php';

// Verificar autenticación y permisos
$auth = getAuth();
$auth->requireRole(['SuperAdmin']);

// Inicializar modelos
$userModel = new User();
$groupModel = new Group();

// Procesar acciones
$action = $_GET['action'] ?? '';
$flash = getFlashMessage();

switch($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $groupModel->createGroup($_POST);
            if ($result) {
                redirectWithMessage('admin/groups.php', 'Grupo creado exitosamente', 'success');
            } else {
                redirectWithMessage('admin/groups.php', 'Error al crear grupo', 'error');
            }
        }
        break;
        
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $groupId = $_POST['group_id'];
            $result = $groupModel->updateGroup($groupId, $_POST);
            if ($result) {
                redirectWithMessage('admin/groups.php', 'Grupo actualizado exitosamente', 'success');
            } else {
                redirectWithMessage('admin/groups.php', 'Error al actualizar grupo', 'error');
            }
        }
        break;
        
    case 'delete':
        $groupId = $_GET['id'];
        if ($groupId && $groupModel->deleteGroup($groupId)) {
            redirectWithMessage('admin/groups.php', 'Grupo eliminado exitosamente', 'success');
        } else {
            redirectWithMessage('admin/groups.php', 'Error al eliminar grupo', 'error');
        }
        break;
}

// Obtener lista de grupos
$groups = $groupModel->getAllGroups();
$leaders = $userModel->getActiveLiders();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Grupos - Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            require_once __DIR__ . '/../../includes/sidebar.php';
            renderSidebar('groups'); 
            ?>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users-cog me-2"></i>Gestión de Grupos
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Grupo
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Lista de Grupos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Grupos Registrados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($groups)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay grupos registrados</h5>
                                <p class="text-muted">Crea el primer grupo para organizar a los activistas</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre del Grupo</th>
                                            <th>Descripción</th>
                                            <th>Líder</th>
                                            <th>Miembros</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($groups as $group): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($group['nombre']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($group['descripcion'] ?? 'Sin descripción') ?></td>
                                            <td>
                                                <?php if ($group['lider_nombre']): ?>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($group['lider_nombre']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin líder asignado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= $group['miembros_count'] ?? 0 ?> miembros
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $group['activo'] ? 'success' : 'warning' ?>">
                                                    <?= $group['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($group['fecha_creacion'] ?? '', 'd/m/Y') ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['nombre']) ?>', '<?= htmlspecialchars($group['descripcion'] ?? '') ?>', <?= $group['lider_id'] ?? 'null' ?>, <?= $group['activo'] ? 'true' : 'false' ?>)"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editGroupModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $group['id'] ?>, '<?= htmlspecialchars($group['nombre']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Crear Grupo -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="groups.php?action=create">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Nuevo Grupo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Grupo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   placeholder="Ej: GeneracionesVa, Grupo mujeres Lupita">
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                      placeholder="Descripción del grupo y sus objetivos"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="lider_id" class="form-label">Líder del Grupo</label>
                            <select class="form-select" id="lider_id" name="lider_id">
                                <option value="">Sin líder asignado</option>
                                <?php foreach ($leaders as $leader): ?>
                                    <option value="<?= $leader['id'] ?>">
                                        <?= htmlspecialchars($leader['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo">
                                    Grupo activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Grupo -->
    <div class="modal fade" id="editGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="groups.php?action=edit">
                    <input type="hidden" id="edit_group_id" name="group_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Grupo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Grupo *</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_lider_id" class="form-label">Líder del Grupo</label>
                            <select class="form-select" id="edit_lider_id" name="lider_id">
                                <option value="">Sin líder asignado</option>
                                <?php foreach ($leaders as $leader): ?>
                                    <option value="<?= $leader['id'] ?>">
                                        <?= htmlspecialchars($leader['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_activo" name="activo" value="1">
                                <label class="form-check-label" for="edit_activo">
                                    Grupo activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editGroup(id, nombre, descripcion, liderId, activo) {
            document.getElementById('edit_group_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_descripcion').value = descripcion;
            document.getElementById('edit_lider_id').value = liderId || '';
            document.getElementById('edit_activo').checked = activo;
        }

        function confirmDelete(id, nombre) {
            if (confirm(`¿Está seguro de eliminar el grupo "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                window.location.href = `groups.php?action=delete&id=${id}`;
            }
        }
    </script>
</body>
</html>