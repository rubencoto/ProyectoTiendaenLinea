<?php
session_start();
require_once '../modelo/conexion.php';

$error = '';

// 🔍 Detectar petición AJAX
$esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    $stmt = $conn->prepare("SELECT id, contrasena, verificado FROM vendedores WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hash, $verificado);
        $stmt->fetch();

        if (!$verificado) {
            $error = "La cuenta aún no ha sido verificada.";
        } elseif (password_verify($contrasena, $hash)) {
            $_SESSION['id'] = $id;
            
            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'redirect' => 'inicioVendedor.php'
                ]);
                exit;
            }
            
            header("Location: inicioVendedor.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Correo no registrado.";
    }

    $stmt->close();
    $conn->close();
    
    // 🚀 Respuesta AJAX para errores
    if ($esAjax && $error) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 400px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="mb-4 text-center">Iniciar Sesión</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form id="loginForm" method="POST">
            <div class="mb-3">
                <label>Correo electrónico</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Contraseña</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="btnLogin">Ingresar</button>
        </form>

        <!-- 🔔 Área para mensajes dinámicos -->
        <div id="mensajeLogin" class="mt-3"></div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-3">
            <p class="small">¿No tienes cuenta? <a href="registroVendedor.php">Regístrate como vendedor</a></p>
            <p class="small"><a href="loginCliente.php">¿Eres cliente? Ingresa aquí</a></p>
        </div>
    </div>

    <script>
    // 🚀 LOGIN CON AJAX
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnLogin = document.getElementById('btnLogin');
        const mensajeDiv = document.getElementById('mensajeLogin');
        const originalText = btnLogin.textContent;
        
        // 🔄 Estado de carga
        btnLogin.disabled = true;
        btnLogin.textContent = 'Ingresando...';
        mensajeDiv.innerHTML = '';
        
        try {
            const formData = new FormData(this);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                // ✅ Login exitoso
                mensajeDiv.innerHTML = `
                    <div class="alert alert-success">
                        <strong>✅ ${result.message}</strong><br>
                        Redirigiendo al panel...
                    </div>
                `;
                
                // Redireccionar después de 1 segundo
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
                
            } else {
                // ❌ Error
                mensajeDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>❌ Error:</strong> ${result.error}
                    </div>
                `;
            }
            
        } catch (error) {
            // 🚨 Error de conexión
            mensajeDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>🚨 Error:</strong> No se pudo conectar con el servidor.
                </div>
            `;
            console.error('Error:', error);
        } finally {
            // 🔄 Restaurar botón
            btnLogin.disabled = false;
            btnLogin.textContent = originalText;
        }
    });
    </script>
</body>
</html>
