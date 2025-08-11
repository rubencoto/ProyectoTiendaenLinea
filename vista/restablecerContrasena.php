<?php
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token no proporcionado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 500px; margin: 80px auto; }
        .loading { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="card">
        <div class="card-header text-center">
            <h4><i class="fas fa-key me-2"></i>Restablecer Contraseña</h4>
        </div>
        <div class="card-body">
            
            <!-- Alert Message Area -->
            <div id="alertContainer" style="display: none;"></div>
            
            <!-- Success Container -->
            <div id="successContainer" style="display: none;">
                <div class="text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-success">¡Contraseña Actualizada!</h5>
                    <p class="text-muted">Tu contraseña ha sido cambiada exitosamente.</p>
                    <a href="loginCliente.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                    </a>
                </div>
            </div>
            
            <!-- Password Reset Form -->
            <div id="resetForm">
                <p class="text-muted text-center mb-4">Ingresa tu nueva contraseña</p>
                
                <form id="restablecerForm">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="nueva_contrasena" id="nuevaContrasena" class="form-control" 
                                   minlength="6" required placeholder="Mínimo 6 caracteres">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="confirmar_contrasena" id="confirmarContrasena" class="form-control" 
                                   minlength="6" required placeholder="Repite la contraseña">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100" id="resetBtn">
                        <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="resetSpinner"></span>
                        <i class="fas fa-save me-1" id="resetIcon"></i>
                        Cambiar Contraseña
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="loginCliente.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Volver al login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    document.getElementById('restablecerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nuevaContrasena = document.getElementById('nuevaContrasena').value;
        const confirmarContrasena = document.getElementById('confirmarContrasena').value;
        
        // Client-side validation
        if (nuevaContrasena !== confirmarContrasena) {
            showAlert('Las contraseñas no coinciden.', 'error');
            return;
        }
        
        if (nuevaContrasena.length < 6) {
            showAlert('La contraseña debe tener al menos 6 caracteres.', 'error');
            return;
        }
        
        const formData = new FormData(this);
        const resetBtn = document.getElementById('resetBtn');
        const resetSpinner = document.getElementById('resetSpinner');
        const resetIcon = document.getElementById('resetIcon');
        
        // Show loading state
        resetBtn.disabled = true;
        resetSpinner.style.display = 'inline-block';
        resetIcon.style.display = 'none';
        
        fetch('../controlador/restablecerContrasenaController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide form and show success message
                document.getElementById('resetForm').style.display = 'none';
                document.getElementById('successContainer').style.display = 'block';
                
                // Redirect after 3 seconds
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 3000);
                }
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
        })
        .finally(() => {
            // Hide loading state
            resetBtn.disabled = false;
            resetSpinner.style.display = 'none';
            resetIcon.style.display = 'inline';
        });
    });
    
    // Toggle password visibility
    document.getElementById('togglePassword1').addEventListener('click', function() {
        togglePasswordVisibility('nuevaContrasena', this);
    });
    
    document.getElementById('togglePassword2').addEventListener('click', function() {
        togglePasswordVisibility('confirmarContrasena', this);
    });
    
    // Password matching validation
    document.getElementById('confirmarContrasena').addEventListener('input', function() {
        const password = document.getElementById('nuevaContrasena').value;
        const confirm = this.value;
        
        if (confirm && confirm !== password) {
            this.setCustomValidity('Las contraseñas no coinciden');
        } else {
            this.setCustomValidity('');
        }
    });
    
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        
        alertContainer.innerHTML = `
            <div class="alert ${alertClass}" role="alert">
                <i class="${iconClass} me-2"></i>${message}
            </div>
        `;
        alertContainer.style.display = 'block';
        
        // Auto-hide success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                alertContainer.style.display = 'none';
            }, 3000);
        }
    }
});
</script>

</body>
</html>