<?php
session_start();
require_once '../modelo/config.php';

// Redirect if already logged in
if (isset($_SESSION['cliente_id'])) {
    header("Location: " . AppConfig::link('index.php'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 400px; margin: 80px auto; }
        .loading { opacity: 0.7; pointer-events: none; }
        .password-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h3><i class="fas fa-user me-2"></i>Iniciar Sesión - Cliente</h3>
            </div>
            <div class="card-body">
                
                <!-- Alert Message Area -->
                <div id="alertContainer" style="display: none;"></div>
                
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="correo" id="correoInput" class="form-control" 
                                   placeholder="tu@correo.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="contrasena" id="contrasenaInput" class="form-control" 
                                   placeholder="Tu contraseña" required>
                            <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                        <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="loginSpinner"></span>
                        <i class="fas fa-sign-in-alt me-1" id="loginIcon"></i>
                        Ingresar
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <div class="mb-2">
                        <a href="<?= AppConfig::link('recuperarContrasena.php') ?>" class="text-decoration-none">
                            <i class="fas fa-key me-1"></i>¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    
                    <div class="mb-3">
                        <span class="text-muted">¿No tienes cuenta?</span>
                        <a href="<?= AppConfig::link('registroCliente.php') ?>" class="text-decoration-none">
                            <i class="fas fa-user-plus me-1"></i>Regístrate como cliente
                        </a>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="mb-2">
                        <span class="text-muted small">¿Eres vendedor?</span><br>
                        <a href="<?= AppConfig::link('loginVendedor.php') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-store me-1"></i>Iniciar sesión como vendedor
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?= AppConfig::link('index.php') ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Volver al catálogo
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
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const loginBtn = document.getElementById('loginBtn');
        const loginSpinner = document.getElementById('loginSpinner');
        const loginIcon = document.getElementById('loginIcon');
        
        // Show loading state
        loginBtn.disabled = true;
        loginSpinner.style.display = 'inline-block';
        loginIcon.style.display = 'none';
        loginBtn.querySelector('span:last-child') ? loginBtn.querySelector('span:last-child').textContent = 'Ingresando...' : null;
        
        fetch('../controlador/procesarLoginClienteController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Redirect after successful login
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
            } else {
                showAlert(data.message, 'error');
                
                // Handle specific error cases
                if (data.verification_needed) {
                    setTimeout(() => {
                        if (confirm('¿Quieres ir a la página de verificación ahora?')) {
                            window.location.href = data.redirect;
                        }
                    }, 2000);
                } else if (data.register_suggestion) {
                    setTimeout(() => {
                        if (confirm('¿Quieres registrarte como cliente?')) {
                            window.location.href = data.register_url;
                        }
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
        })
        .finally(() => {
            // Hide loading state
            loginBtn.disabled = false;
            loginSpinner.style.display = 'none';
            loginIcon.style.display = 'inline';
            const btnText = loginBtn.querySelector('span:last-child');
            if (btnText) btnText.textContent = 'Ingresar';
        });
    });
    
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('contrasenaInput');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Clear alerts when user starts typing
    document.getElementById('correoInput').addEventListener('input', clearAlerts);
    document.getElementById('contrasenaInput').addEventListener('input', clearAlerts);
    
    function clearAlerts() {
        const alertContainer = document.getElementById('alertContainer');
        alertContainer.style.display = 'none';
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
