<?php
session_start();

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

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
        $tipo_mensaje = "success";
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

// Verificación automática por URL
if (isset($_GET['codigo']) && isset($_GET['correo'])) {
    $codigo = $_GET['codigo'];
    $correo = $_GET['correo'];
    
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ? AND codigo_verificacion = ? AND verificado = 0");
    $stmt->bind_param("ss", $correo, $codigo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $row = $resultado->fetch_assoc();
        $cliente_id = $row['id'];
        
        // Actualizar estado de verificación
        $stmt_update = $conn->prepare("UPDATE clientes SET verificado = 1, codigo_verificacion = NULL WHERE id = ?");
        $stmt_update->bind_param("i", $cliente_id);
        
        if ($stmt_update->execute()) {
            $mensaje = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al verificar la cuenta. Inténtalo de nuevo.";
            $tipo_mensaje = "error";
        }
        $stmt_update->close();
    } else {
        $mensaje = "Código de verificación inválido o cuenta ya verificada.";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Verificación manual por formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    
    if (!empty($correo) && !empty($codigo)) {
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ? AND codigo_verificacion = ? AND verificado = 0");
        $stmt->bind_param("ss", $correo, $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 1) {
            $row = $resultado->fetch_assoc();
            $cliente_id = $row['id'];
            
            // Actualizar estado de verificación
            $stmt_update = $conn->prepare("UPDATE clientes SET verificado = 1, codigo_verificacion = NULL WHERE id = ?");
            $stmt_update->bind_param("i", $cliente_id);
            
            if ($stmt_update->execute()) {
                $mensaje = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al verificar la cuenta. Inténtalo de nuevo.";
                $tipo_mensaje = "error";
            }
            $stmt_update->close();
        } else {
            $mensaje = "Código de verificación inválido o cuenta ya verificada.";
            $tipo_mensaje = "error";
        }
        $stmt->close();
    } else {
        $mensaje = "Por favor, completa todos los campos.";
        $tipo_mensaje = "error";
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .verification-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .verification-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
        }
        .alert-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
        }
        .alert-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        .alert-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="verification-card">
                <div class="verification-header">
                    <i class="fas fa-envelope-circle-check"></i>
                    <h2>Verificar Cuenta</h2>
                    <p class="text-muted">Ingresa el código que recibiste en tu correo</p>
                </div>
            
            <?php if (!empty($mensaje)): ?>
                <?php 
                // Map message types to Bootstrap alert classes
                $alert_class = 'alert-info'; // default
                switch($tipo_mensaje) {
                    case 'success':
                        $alert_class = 'alert-success';
                        break;
                    case 'error':
                        $alert_class = 'alert-danger';
                        break;
                    case 'warning':
                        $alert_class = 'alert-warning';
                        break;
                    case 'info':
                        $alert_class = 'alert-info';
                        break;
                }
                ?>
                <div class="alert <?= $alert_class ?>" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
                
                <?php if ($tipo_mensaje === 'success'): ?>
                    <div class="text-center">
                        <a href="<?= AppConfig::vistaUrl('loginCliente.php') ?>" class="btn btn-primary">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <?php if ($tipo_mensaje !== 'success'): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-2"></i>Correo Electrónico
                        </label>
                        <input type="email" class="form-control" name="correo" 
                               value="<?= $correo_prefill ?: htmlspecialchars($_GET['correo'] ?? '') ?>" 
                               placeholder="tu@correo.com" required>
                        <div class="invalid-feedback">
                            Por favor ingresa un correo válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-key me-2"></i>Código de Verificación
                        </label>
                        <input type="text" class="form-control text-center" name="codigo" 
                               placeholder="000000" maxlength="6" required
                               style="font-size: 1.2rem; letter-spacing: 0.5rem;">
                        <div class="form-text">Código de 6 caracteres enviado a tu correo</div>
                        <div class="invalid-feedback">
                            Por favor ingresa el código de verificación.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-check-circle me-2"></i>Verificar Cuenta
                    </button>
                </form>
                
                <div class="text-center">
                    <hr class="my-3">
                    <a href="<?= AppConfig::vistaUrl('loginCliente.php') ?>" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Volver al login
                    </a>
                    <span class="mx-2">|</span>
                    <a href="<?= AppConfig::vistaUrl('registroCliente.php') ?>" class="text-decoration-none">
                        <i class="fas fa-user-plus me-1"></i>Registrarse
                    </a>
                </div>
            <?php endif; ?>
            
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Auto-format verification code input
    const codigoInput = document.querySelector('input[name="codigo"]');
    if (codigoInput) {
        codigoInput.addEventListener('input', function(e) {
            // Only allow numbers
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits are entered
            if (e.target.value.length === 6) {
                // Optional: auto-submit the form
                // e.target.closest('form').submit();
            }
        });
        
        // Focus on load if email is prefilled
        const emailInput = document.querySelector('input[name="correo"]');
        if (emailInput && emailInput.value.trim() !== '') {
            codigoInput.focus();
        }
    }

    // Auto-hide alerts after 10 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (!alert.classList.contains('alert-success')) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        });
    }, 10000);
</script>
</body>
</html>
