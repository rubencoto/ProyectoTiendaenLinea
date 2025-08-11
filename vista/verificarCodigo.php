<?php
$token = $_GET['token'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Código</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 400px; margin: 80px auto; }
        .loading { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body class="bg-light">
<div class="container">
    <div class="card">
        <div class="card-header text-center">
            <h4><i class="fas fa-shield-alt me-2"></i>Verificar Código</h4>
        </div>
        <div class="card-body">
            
            <!-- Alert Message Area -->
            <div id="alertContainer" style="display: none;"></div>
            
            <p class="text-muted text-center mb-4">Ingresa el código de verificación que recibiste en tu correo.</p>
            
            <form id="verificarForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Código de verificación</label>
                    <input type="text" name="codigo" id="codigoInput" class="form-control text-center" 
                           maxlength="6" required autofocus 
                           placeholder="000000"
                           style="letter-spacing: 0.5em; font-size: 1.2em;">
                    <div class="form-text">Ingresa el código de 6 dígitos</div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" id="verificarBtn">
                    <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="verificarSpinner"></span>
                    <i class="fas fa-check me-1" id="verificarIcon"></i>
                    Verificar Código
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="recuperarContrasena.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    document.getElementById('verificarForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const verificarBtn = document.getElementById('verificarBtn');
        const verificarSpinner = document.getElementById('verificarSpinner');
        const verificarIcon = document.getElementById('verificarIcon');
        
        // Show loading state
        verificarBtn.disabled = true;
        verificarSpinner.style.display = 'inline-block';
        verificarIcon.style.display = 'none';
        
        fetch('../controlador/verificarCodigoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Redirect after successful verification
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
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
            verificarBtn.disabled = false;
            verificarSpinner.style.display = 'none';
            verificarIcon.style.display = 'inline';
        });
    });
    
    // Format input as user types
    document.getElementById('codigoInput').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        if (value.length > 6) value = value.substring(0, 6); // Limit to 6 digits
        e.target.value = value;
    });
    
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
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</div>
</body>
</html>