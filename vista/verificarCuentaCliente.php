<?php
session_start();

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

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
        $tipo_mensaje = "info"; // Changed from "success" to "info" so form still shows
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
    $stmt->execute([$correo, $codigo]);
    $row = $stmt->fetch();
    
    if ($row) {
        $cliente_id = $row['id'];
        
        // Actualizar estado de verificación
        $stmt_update = $conn->prepare("UPDATE clientes SET verificado = 1, codigo_verificacion = NULL WHERE id = ?");
        
        if ($stmt_update->execute([$cliente_id])) {
            $mensaje = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al verificar la cuenta. Inténtalo de nuevo.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Código de verificación inválido o cuenta ya verificada.";
        $tipo_mensaje = "error";
    }
}

// Handle resend verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    $correo = $_POST['correo'] ?? '';
    
    if (!empty($correo)) {
        // Check if user exists and is not verified
        $stmt = $conn->prepare("SELECT id, nombre, apellido FROM clientes WHERE correo = ? AND verificado = 0");
        $stmt->execute([$correo]);
        $row = $stmt->fetch();
        
        if ($row) {
            $cliente_id = $row['id'];
            $nombre = $row['nombre'];
            $apellido = $row['apellido'];
            
            // Generate new verification code
            $nuevo_codigo = substr(bin2hex(random_bytes(4)), 0, 6);
            
            // Update verification code in database
            $stmt_update = $conn->prepare("UPDATE clientes SET codigo_verificacion = ? WHERE id = ?");
            
            if ($stmt_update->execute([$nuevo_codigo, $cliente_id])) {
                // Send new verification email
                require_once '../modelo/enviarCorreo.php';
                
                $verification_url = AppConfig::emailUrl('verificarCuentaCliente.php', [
                    'codigo' => $nuevo_codigo,
                    'correo' => $correo
                ]);

                $asunto = "Nuevo código de verificación - Tienda en Línea";
                $mensaje_email = "
                <h2>Nuevo código de verificación</h2>
                <p>Hola $nombre $apellido,</p>
                <p>Has solicitado un nuevo código de verificación para tu cuenta.</p>
                <p>Tu nuevo código de verificación es:</p>
                <h3 style='background-color: #f0f0f0; padding: 10px; text-align: center; border-radius: 5px;'>$nuevo_codigo</h3>
                <p>Haz clic en el siguiente enlace para verificar tu cuenta:</p>
                <p><a href='$verification_url' 
                   style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                   Verificar Cuenta
                </a></p>
                <p><small>Este código expira en 24 horas.</small></p>
                ";

                if (enviarCorreo($correo, $asunto, $mensaje_email)) {
                    $mensaje = "Se ha enviado un nuevo código de verificación a tu correo electrónico. Ingresa el nuevo código abajo.";
                    $tipo_mensaje = "resend_success";
                } else {
                    $mensaje = "Se generó un nuevo código pero hubo un problema al enviar el correo. Puedes intentar verificar manualmente con el código: $nuevo_codigo";
                    $tipo_mensaje = "warning";
                }
                $correo_prefill = $correo;
            } else {
                $mensaje = "Error al generar nuevo código. Inténtalo de nuevo.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No se encontró una cuenta pendiente de verificación con este correo.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor, ingresa tu correo electrónico.";
        $tipo_mensaje = "error";
    }
}

// Verificación manual por formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['resend_code'])) {
    $correo = $_POST['correo'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    
    if (!empty($correo) && !empty($codigo)) {
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ? AND codigo_verificacion = ? AND verificado = 0");
        $stmt->execute([$correo, $codigo]);
        $row = $stmt->fetch();
        
        if ($row) {
            $cliente_id = $row['id'];
            
            // Actualizar estado de verificación
            $stmt_update = $conn->prepare("UPDATE clientes SET verificado = 1, codigo_verificacion = NULL WHERE id = ?");
            
            if ($stmt_update->execute([$cliente_id])) {
                $mensaje = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al verificar la cuenta. Inténtalo de nuevo.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Código de verificación inválido o cuenta ya verificada.";
            $tipo_mensaje = "error";
        }
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
            
            <?php if (!empty($mensaje)): ?>
                <?php 
                // Map message types to Bootstrap alert classes
                $alert_class = 'alert-info'; // default
                switch($tipo_mensaje) {
                    case 'success':
                        $alert_class = 'alert-success';
                        break;
                    case 'resend_success':
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
                    <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                </div>
                
                <?php if ($tipo_mensaje === 'success'): ?>
                    <div class="text-center">
                        <a href="<?= AppConfig::vistaUrl('loginCliente.php') ?>" class="btn btn-primary">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <?php if ($tipo_mensaje !== 'success'): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" name="correo" 
                               value="<?= $correo_prefill ?: htmlspecialchars($_GET['correo'] ?? '') ?>" 
                               placeholder="tu@correo.com" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Código de Verificación</label>
                        <input type="text" class="form-control" name="codigo" 
                               placeholder="Ingresa el código de 6 dígitos" maxlength="6" required>
                        <div class="form-text">
                            <?php if ($tipo_mensaje === 'resend_success'): ?>
                                Revisa tu correo - se ha enviado un nuevo código
                            <?php else: ?>
                                Revisa tu correo electrónico para obtener el código
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        Verificar Cuenta
                    </button>
                </form>
                
                <!-- Resend Code Form -->
                <div class="text-center">
                    <hr>
                    <p class="text-muted">¿No recibiste el código?</p>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="correo" value="<?= $correo_prefill ?: htmlspecialchars($_GET['correo'] ?? '') ?>">
                        <button type="submit" name="resend_code" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i>Reenviar código
                        </button>
                    </form>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?= AppConfig::vistaUrl('loginCliente.php') ?>" class="text-decoration-none me-3">
                        <i class="fas fa-arrow-left me-1"></i>Volver al login
                    </a>
                    <a href="<?= AppConfig::vistaUrl('registroCliente.php') ?>" class="text-decoration-none">
                        <i class="fas fa-user-plus me-1"></i>Registrarse
                    </a>
                </div>
            <?php endif; ?>
            
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
