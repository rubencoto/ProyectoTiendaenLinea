<?php
$correo = $_GET['correo'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Código - Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 450px; margin: 80px auto; }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background: #e9ecef;
            border-radius: 25px;
            font-size: 14px;
            margin: 0 5px;
        }
        .step.active {
            background: #007185;
            color: white;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
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
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="step1">
                    <i class="fas fa-envelope me-2"></i>1. Verificar Código
                </div>
                <div class="step" id="step2">
                    <i class="fas fa-lock me-2"></i>2. Nueva Contraseña
                </div>
            </div>
            
            <!-- Alert Message Area -->
            <div id="alertContainer" style="display: none;"></div>
            
            <!-- Step 1: Verify Code -->
            <div id="verifyCodeSection">
                <p class="text-muted text-center mb-4">
                    Ingresa el código de verificación que enviamos a:<br>
                    <strong><?= htmlspecialchars($correo) ?></strong>
                </p>
                
                <form id="verifyCodeForm">
                    <input type="hidden" name="action" value="verify_code">
                    <input type="hidden" name="correo" value="<?= htmlspecialchars($correo) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Código de verificación</label>
                        <input type="text" name="codigo" id="codigoInput" class="form-control text-center" 
                               maxlength="6" required autofocus 
                               placeholder="000000"
                               style="letter-spacing: 0.5em; font-size: 1.2em;">
                        <div class="form-text">Ingresa el código de 6 dígitos que recibiste por correo</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="verifyBtn">
                        <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="verifySpinner"></span>
                        <i class="fas fa-check me-1" id="verifyIcon"></i>
                        Verificar Código
                    </button>
                </form>
            </div>
            
            <!-- Step 2: Reset Password -->
            <div id="resetPasswordSection" style="display: none;">
                <p class="text-muted text-center mb-4">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Código verificado correctamente. Ahora establece tu nueva contraseña.
                </p>
                
                <form id="resetPasswordForm">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="correo" value="<?= htmlspecialchars($correo) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="nueva_contrasena" id="nuevaContrasena" class="form-control" 
                               required minlength="6">
                        <div class="password-requirements">
                            Mínimo 6 caracteres
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" name="confirmar_contrasena" id="confirmarContrasena" class="form-control" 
                               required minlength="6">
                        <div class="form-text" id="passwordMatch"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100" id="resetBtn">
                        <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="resetSpinner"></span>
                        <i class="fas fa-save me-1" id="resetIcon"></i>
                        Cambiar Contraseña
                    </button>
                </form>
            </div>
            
            <div class="text-center mt-3">
                <a href="recuperarContrasena.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Volver al inicio
                </a>
                <span class="mx-2">|</span>
                <a href="loginCliente.php" class="text-decoration-none">
                    <i class="fas fa-sign-in-alt me-1"></i>Ir al login
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle verify code form submission
    document.getElementById('verifyCodeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const verifyBtn = document.getElementById('verifyBtn');
        const verifySpinner = document.getElementById('verifySpinner');
        const verifyIcon = document.getElementById('verifyIcon');
        
        // Show loading state
        verifyBtn.disabled = true;
        verifySpinner.style.display = 'inline-block';
        verifyIcon.style.display = 'none';
        
        fetch('verificarCodigoResetController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.step === 'password_reset') {
                showAlert(data.message, 'success');
                
                // Move to step 2
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
                document.getElementById('verifyCodeSection').style.display = 'none';
                document.getElementById('resetPasswordSection').style.display = 'block';
                
                // Focus on new password field
                setTimeout(() => {
                    document.getElementById('nuevaContrasena').focus();
                }, 500);
                
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
            verifyBtn.disabled = false;
            verifySpinner.style.display = 'none';
            verifyIcon.style.display = 'inline';
        });
    });
    
    // Handle reset password form submission
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nuevaContrasena = document.getElementById('nuevaContrasena').value;
        const confirmarContrasena = document.getElementById('confirmarContrasena').value;
        
        if (nuevaContrasena !== confirmarContrasena) {
            showAlert('Las contraseñas no coinciden.', 'error');
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
        
        fetch('verificarCodigoResetController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.step === 'complete') {
                showAlert(data.message, 'success');
                
                // Redirect to login after success
                setTimeout(() => {
                    window.location.href = 'loginCliente.php';
                }, 2000);
                
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
    
    // Format code input as user types
    document.getElementById('codigoInput').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        if (value.length > 6) value = value.substring(0, 6); // Limit to 6 digits
        e.target.value = value;
    });
    
    // Check password match
    function checkPasswordMatch() {
        const nuevaContrasena = document.getElementById('nuevaContrasena').value;
        const confirmarContrasena = document.getElementById('confirmarContrasena').value;
        const matchDiv = document.getElementById('passwordMatch');
        
        if (confirmarContrasena.length > 0) {
            if (nuevaContrasena === confirmarContrasena) {
                matchDiv.innerHTML = '<i class="fas fa-check text-success"></i> Las contraseñas coinciden';
                matchDiv.className = 'form-text text-success';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times text-danger"></i> Las contraseñas no coinciden';
                matchDiv.className = 'form-text text-danger';
            }
        } else {
            matchDiv.innerHTML = '';
        }
    }
    
    document.getElementById('nuevaContrasena').addEventListener('input', checkPasswordMatch);
    document.getElementById('confirmarContrasena').addEventListener('input', checkPasswordMatch);
    
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
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                alertContainer.style.display = 'none';
            }, 5000);
        }
    }
});
</script>

</body>
</html>
