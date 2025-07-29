<?php
session_start();
require_once '../modelo/conexion.php';

$mensaje = '';
$tipo_mensaje = '';
$correo = $_GET['correo'] ?? '';
$codigo_verificado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    
    // Paso 1: Verificar código
    if (isset($_POST['verificar_codigo'])) {
        $codigo = $_POST['codigo'] ?? '';
        
        if (empty($codigo)) {
            $mensaje = "Por favor, ingresa el código de verificación.";
            $tipo_mensaje = "error";
        } else {
            // Limpiar el código ingresado
            $codigo_limpio = preg_replace('/[^0-9]/', '', trim($codigo));
            
            // Verificar el código
            $stmt = $conn->prepare("SELECT id, codigo_verificacion FROM clientes WHERE correo = ?");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows === 1) {
                $row = $resultado->fetch_assoc();
                $codigo_db = preg_replace('/[^0-9]/', '', trim($row['codigo_verificacion']));
                
                // Debug: mostrar códigos para comparación (remover en producción)
                error_log("Codigo ingresado limpio: '" . $codigo_limpio . "'");
                error_log("Codigo en DB limpio: '" . $codigo_db . "'");
                
                // Comparar códigos limpios
                if ($codigo_limpio === $codigo_db && !empty($codigo_limpio)) {
                    $codigo_verificado = true;
                    $mensaje = "Código verificado correctamente. Ahora puedes establecer tu nueva contraseña.";
                    $tipo_mensaje = "success";
                    // Guardar el código en la sesión para el siguiente paso
                    $_SESSION['codigo_verificado'] = $codigo_limpio;
                    $_SESSION['correo_reset'] = $correo;
                } else {
                    $mensaje = "Código de verificación inválido. Revisa el código en tu correo e inténtalo de nuevo.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "No se encontró el correo en el sistema.";
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
    }
    
    // Paso 2: Cambiar contraseña
    if (isset($_POST['cambiar_password'])) {
        $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
        $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
        $codigo_sesion = $_SESSION['codigo_verificado'] ?? '';
        $correo_sesion = $_SESSION['correo_reset'] ?? '';
        
        if ($nueva_contrasena !== $confirmar_contrasena) {
            $mensaje = "Las contraseñas no coinciden.";
            $tipo_mensaje = "error";
            $codigo_verificado = true; // Mantener en el paso 2
        } elseif (empty($nueva_contrasena) || strlen($nueva_contrasena) < 6) {
            $mensaje = "La contraseña debe tener al menos 6 caracteres.";
            $tipo_mensaje = "error";
            $codigo_verificado = true; // Mantener en el paso 2
        } else {
            // Verificar nuevamente el código desde la sesión
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ? AND codigo_verificacion = ?");
            $stmt->bind_param("ss", $correo_sesion, $codigo_sesion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows === 1) {
                $row = $resultado->fetch_assoc();
                $cliente_id = $row['id'];
                
                // Actualizar la contraseña y limpiar el código
                $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);
                $stmt_update = $conn->prepare("UPDATE clientes SET contrasena = ?, codigo_verificacion = NULL WHERE id = ?");
                $stmt_update->bind_param("si", $nueva_contrasena_hash, $cliente_id);
                
                if ($stmt_update->execute()) {
                    $mensaje = "¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.";
                    $tipo_mensaje = "success";
                    $mostrar_enlace_login = true;
                    // Limpiar la sesión
                    unset($_SESSION['codigo_verificado']);
                    unset($_SESSION['correo_reset']);
                } else {
                    $mensaje = "Error al actualizar la contraseña. Inténtalo de nuevo.";
                    $tipo_mensaje = "error";
                    $codigo_verificado = true; // Mantener en el paso 2
                }
                $stmt_update->close();
            } else {
                $mensaje = "Sesión expirada. Por favor, solicita un nuevo código.";
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
    }
}

// Verificar si ya hay una sesión activa de código verificado
if (isset($_SESSION['codigo_verificado']) && isset($_SESSION['correo_reset'])) {
    $codigo_verificado = true;
    $correo = $_SESSION['correo_reset'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn-primary {
            background-color: #007185;
            border-color: #007185;
        }
        .btn-primary:hover {
            background-color: #005d6b;
            border-color: #005d6b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Restablecer Contraseña</h2>
        <p>Ingresa el código de verificación que recibiste por correo</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'error' ?> mb-3">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($mostrar_enlace_login) && $mostrar_enlace_login): ?>
        <div class="text-center">
            <div class="alert alert-success mb-4">
                <h4>¡Listo!</h4>
                <p><?= htmlspecialchars($mensaje) ?></p>
            </div>
            <a href="loginCliente.php" class="btn btn-primary btn-lg">Ir al Login</a>
        </div>
    <?php elseif (!$codigo_verificado): ?>
        <!-- PASO 1: Verificar Código -->
        <div class="header">
            <h2>Paso 1: Verificar Código</h2>
            <p>Ingresa el código de verificación que recibiste por correo</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'error' ?> mb-3">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="correo" value="<?= htmlspecialchars($correo) ?>">
            
            <div class="mb-3">
                <label class="form-label">Correo electrónico:</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($correo) ?>" readonly>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Código de verificación *</label>
                <input type="text" name="codigo" class="form-control text-center" required 
                       placeholder="Ingresa el código de 6 dígitos" maxlength="6" style="font-size: 1.5em; letter-spacing: 0.2em;">
            </div>
            
            <button type="submit" name="verificar_codigo" class="btn btn-primary w-100 btn-lg">Verificar Código</button>
        </form>

        <div class="text-center mt-3">
            <p class="small">
                <a href="loginCliente.php">← Volver al login</a> | 
                <a href="recuperarContrasena.php">Solicitar nuevo código</a>
            </p>
        </div>
        
    <?php else: ?>
        <!-- PASO 2: Cambiar Contraseña -->
        <div class="header">
            <h2>Paso 2: Nueva Contraseña</h2>
            <p>Código verificado. Ahora establece tu nueva contraseña</p>
        </div>

        <?php if ($mensaje && $tipo_mensaje === 'error'): ?>
            <div class="alert alert-error mb-3">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Correo electrónico:</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($correo) ?>" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nueva contraseña *</label>
                <input type="password" name="nueva_contrasena" class="form-control" required minlength="6" 
                       placeholder="Mínimo 6 caracteres">
            </div>
            
            <div class="mb-4">
                <label class="form-label">Confirmar nueva contraseña *</label>
                <input type="password" name="confirmar_contrasena" class="form-control" required minlength="6" 
                       placeholder="Repite la contraseña">
            </div>
            
            <button type="submit" name="cambiar_password" class="btn btn-primary w-100 btn-lg">Actualizar Contraseña</button>
        </form>

        <div class="text-center mt-3">
            <p class="small">
                <a href="loginCliente.php">← Volver al login</a>
            </p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
</html>
