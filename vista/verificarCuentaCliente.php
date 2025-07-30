<?php
session_start();
require_once '../modelo/conexion.php';

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
    <title>Verificar Cuenta - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center">Verificar Cuenta</h2>
            
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?>" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
                
                <?php if ($tipo_mensaje === 'success'): ?>
                    <div class="text-center">
                        <a href="loginCliente.php" class="btn btn-primary">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <?php if ($tipo_mensaje !== 'success'): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>Correo Electrónico</label>
                        <input type="email" class="form-control" name="correo" 
                               value="<?= $correo_prefill ?: htmlspecialchars($_GET['correo'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Código de Verificación</label>
                        <input type="text" class="form-control" name="codigo" 
                               placeholder="Ingresa el código de 6 caracteres" maxlength="6" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Verificar Cuenta</button>
                </form>
                
                <div class="text-center mt-3">
                    <p><a href="loginCliente.php">Volver al login</a></p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>
</body>
</html>
