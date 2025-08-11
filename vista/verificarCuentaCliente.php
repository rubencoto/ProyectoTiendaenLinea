<?php
session_start();

require_once '../modelo/config.php';

// Get initial message parameters from URL
$mensaje = '';
$tipo_mensaje = '';
$correo_prefill = '';

// Handle registration success message
if (isset($_GET['registro']) && $_GET['registro'] == 1) {
    if (isset($_GET['correo_error']) && $_GET['correo_error'] == 1) {
        $mensaje = "¡Registro exitoso! Hubo un problema al enviar el correo de verificación, pero puedes verificar tu cuenta manualmente ingresando el código que se generó.";
        $tipo_mensaje = "warning";
    } else {
        $mensaje = "¡Registro exitoso! Se ha enviado un código de verificación a tu correo electrónico. Por favor, ingresa el código para activar tu cuenta.";
        $tipo_mensaje = "info";
    }
    
    if (isset($_GET['correo'])) {
        $correo_prefill = htmlspecialchars($_GET['correo']);
    }
}

// Handle pending verification case
if (isset($_GET['pendiente']) && $_GET['pendiente'] == 1) {
    $mensaje = "Ya tienes una cuenta registrada con este correo, pero aún no está verificada. Por favor, ingresa el código de verificación que se envió a tu correo.";
    $tipo_mensaje = "info";
    
    if (isset($_GET['correo'])) {
        $correo_prefill = htmlspecialchars($_GET['correo']);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Cuenta - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4><i class="fas fa-envelope-check me-2"></i>Verificar Cuenta</h4>
                </div>
                <div class="card-body">
            
                    <!-- Alert Message Area -->
                    <div id="alertContainer" style="display: none;"></div>
                    
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert <?= $tipo_mensaje === 'success' ? 'alert-success' : ($tipo_mensaje === 'error' ? 'alert-danger' : ($tipo_mensaje === 'warning' ? 'alert-warning' : 'alert-info')) ?>" role="alert">
                            <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Success Redirect Area -->
                    <div id="successContainer" style="display: none;">
                        <div class="text-center">
                            <a href="<?= AppConfig::vistaUrl('loginCliente.php') ?>" class="btn btn-primary">Iniciar Sesión</a>
                        </div>
                    </div>
                    
                    <!-- Verification Form -->
                    <div id="verificationForm" style="<?= $tipo_mensaje === 'success' ? 'display: none;' : '' ?>">
                        <form id="verifyForm">
                            <input type="hidden" name="action" value="verify_code">
                            
                            <div class="mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" name="correo" id="correoInput"
                                       value="<?= $correo_prefill ?: htmlspecialchars($_GET['correo'] ?? '') ?>" 
                                       placeholder="tu@correo.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Código de Verificación</label>
                                <input type="text" class="form-control" name="codigo" id="codigoInput"
                                       placeholder="Ingresa el código de 6 dígitos" maxlength="6" required>
                                <div class="form-text" id="codeHelp">
                                    Revisa tu correo electrónico para obtener el código
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3" id="verifyBtn">
                                <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="verifySpinner"></span>
                                Verificar Cuenta
                            </button>
                        </form>
                        
                        <!-- Resend Code Section -->
                        <div class="text-center">
                            <hr>
                            <p class="text-muted">¿No recibiste el código?</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resendBtn">
                                <span class="spinner-border spinner-border-sm me-1" style="display: none;" id="resendSpinner"></span>
                                <i class="fas fa-paper-plane me-1" id="resendIcon"></i>Reenviar código
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="<?= AppConfig::vistaUrl('loginCliente.php') ?>" class="text-decoration-none me-3">
                                <i class="fas fa-arrow-left me-1"></i>Volver al login
                            </a>
                            <a href="<?= AppConfig::vistaUrl('registroCliente.php') ?>" class="text-decoration-none">
                                <i class="fas fa-user-plus me-1"></i>Registrarse
                            </a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for URL verification parameters
    const urlParams = new URLSearchParams(window.location.search);
    const codigo = urlParams.get('codigo');
    const correo = urlParams.get('correo');
    
    if (codigo && correo) {
        // Perform automatic verification
        verifyFromUrl(codigo, correo);
    }
    
    // Handle verification form submission
    document.getElementById('verifyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const verifyBtn = document.getElementById('verifyBtn');
        const verifySpinner = document.getElementById('verifySpinner');
        
        // Show loading state
        verifyBtn.disabled = true;
        verifySpinner.style.display = 'inline-block';
        
        fetch('../controlador/verificarCuentaClienteController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.type || 'info');
            
            if (data.success && data.type === 'success') {
                document.getElementById('verificationForm').style.display = 'none';
                document.getElementById('successContainer').style.display = 'block';
                
                // Redirect after 2 seconds
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
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
            verifyBtn.disabled = false;
            verifySpinner.style.display = 'none';
        });
    });
    
    // Handle resend code button
    document.getElementById('resendBtn').addEventListener('click', function() {
        const correo = document.getElementById('correoInput').value;
        
        if (!correo) {
            showAlert('Por favor, ingresa tu correo electrónico primero.', 'error');
            return;
        }
        
        const resendBtn = this;
        const resendSpinner = document.getElementById('resendSpinner');
        const resendIcon = document.getElementById('resendIcon');
        
        // Show loading state
        resendBtn.disabled = true;
        resendSpinner.style.display = 'inline-block';
        resendIcon.style.display = 'none';
        
        const formData = new FormData();
        formData.append('action', 'resend_code');
        formData.append('correo', correo);
        
        fetch('../controlador/verificarCuentaClienteController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.type || 'info');
            
            if (data.success) {
                document.getElementById('codeHelp').textContent = 'Revisa tu correo - se ha enviado un nuevo código';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
        })
        .finally(() => {
            // Hide loading state
            resendBtn.disabled = false;
            resendSpinner.style.display = 'none';
            resendIcon.style.display = 'inline';
        });
    });
    
    function verifyFromUrl(codigo, correo) {
        const formData = new FormData();
        formData.append('action', 'verify_url');
        formData.append('codigo', codigo);
        formData.append('correo', correo);
        
        fetch('../controlador/verificarCuentaClienteController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.type || 'info');
            
            if (data.success && data.type === 'success') {
                document.getElementById('verificationForm').style.display = 'none';
                document.getElementById('successContainer').style.display = 'block';
                
                // Redirect after 2 seconds
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
        });
    }
    
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const iconClass = type === 'success' ? 'fas fa-check-circle' : 
                         type === 'error' ? 'fas fa-exclamation-circle' : 
                         type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
        
        alertContainer.innerHTML = `
            <div class="alert ${alertClass}" role="alert">
                <i class="${iconClass} me-2"></i>${message}
            </div>
        `;
        alertContainer.style.display = 'block';
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success' || type === 'resend_success') {
            setTimeout(() => {
                alertContainer.style.display = 'none';
            }, 5000);
        }
    }
});
</script>

</body>
</html>
