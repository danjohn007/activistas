<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Activistas Digitales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        .register-card {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
        .foto-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card register-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h2 class="text-primary fw-bold">Registro de Usuario</h2>
                                <p class="text-muted">Únete al sistema de activistas digitales</p>
                            </div>
                            
                            <?php $flash = getFlashMessage(); ?>
                            <?php if ($flash): ?>
                                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($flash['message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['form_errors'])): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($_SESSION['form_errors'] as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php unset($_SESSION['form_errors']); ?>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data" id="registerForm">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['nombre_completo'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono *</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['telefono'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Correo Electrónico *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="foto_perfil" class="form-label">Foto de Perfil *</label>
                                    <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" 
                                           accept="image/*" required onchange="previewImage(this)">
                                    <div class="mt-2 text-center">
                                        <img id="preview" class="foto-preview" style="display: none;" alt="Vista previa">
                                    </div>
                                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">
                                        Mínimo 8 caracteres, debe incluir: mayúscula, minúscula, número y carácter especial.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección *</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2" required><?= htmlspecialchars($_SESSION['form_data']['direccion'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol Deseado *</label>
                                    <select class="form-select" id="rol" name="rol" required onchange="toggleLiderField()">
                                        <option value="">Seleccione un rol</option>
                                        <option value="Líder" <?= ($_SESSION['form_data']['rol'] ?? '') === 'Líder' ? 'selected' : '' ?>>Líder</option>
                                        <option value="Activista" <?= ($_SESSION['form_data']['rol'] ?? '') === 'Activista' ? 'selected' : '' ?>>Activista</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="lider_field" style="display: none;">
                                    <label for="lider_id" class="form-label">Selecciona tu Líder *</label>
                                    <select class="form-select" id="lider_id" name="lider_id">
                                        <option value="">Seleccione un líder</option>
                                        <?php if (isset($liders) && is_array($liders)): ?>
                                            <?php foreach ($liders as $lider): ?>
                                                <option value="<?= $lider['id'] ?>" 
                                                        <?= ($_SESSION['form_data']['lider_id'] ?? '') == $lider['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($lider['nombre_completo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="acepto_terminos" required>
                                    <label class="form-check-label" for="acepto_terminos">
                                        Acepto los términos y condiciones del sistema
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-user-plus me-2"></i>Registrarse
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <p class="mb-0">¿Ya tienes cuenta? 
                                    <a href="<?= url('login.php') ?>" class="text-primary text-decoration-none fw-bold">
                                        Inicia sesión aquí
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleLiderField() {
            const rol = document.getElementById('rol').value;
            const liderField = document.getElementById('lider_field');
            const liderSelect = document.getElementById('lider_id');
            
            if (rol === 'Activista') {
                liderField.style.display = 'block';
                liderSelect.required = true;
            } else {
                liderField.style.display = 'none';
                liderSelect.required = false;
                liderSelect.value = '';
            }
        }
        
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Inicializar campos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            toggleLiderField();
        });
    </script>
    
    <?php unset($_SESSION['form_data']); ?>
</body>
</html>