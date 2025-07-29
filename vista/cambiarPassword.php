<?php
session_start(); // 🔐 Iniciar sesión

// 🚫 Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/conexion.php';
$cliente_id = $_SESSION['cliente_id'];

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    // Validaciones
    $errores = [];
    
    if (empty($password_actual)) $errores[] = "La contraseña actual es obligatoria";
    if (empty($password_nueva)) $errores[] = "La nueva contraseña es obligatoria";
    if (empty($password_confirmar)) $errores[] = "Debes confirmar la nueva contraseña";
    
    if (strlen($password_nueva) < 6) {
        $errores[] = "La nueva contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password_nueva !== $password_confirmar) {
        $errores[] = "Las contraseñas nuevas no coinciden";
    }
    
    if (empty($errores)) {
        try {
            // Verificar contraseña actual
            $stmt = $conn->prepare("SELECT contrasena FROM clientes WHERE id = ?");
            $stmt->bind_param("i", $cliente_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cliente = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($password_actual, $cliente['contrasena'])) {
                $errores[] = "La contraseña actual es incorrecta";
            } else {
                // Actualizar contraseña
                $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE clientes SET contrasena = ? WHERE id = ?");
                $stmt_update->bind_param("si", $password_hash, $cliente_id);
                
                if ($stmt_update->execute()) {
                    $mensaje = "Contraseña actualizada exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar la contraseña";
                    $tipo_mensaje = "error";
                }
                $stmt_update->close();
            }
            
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
    
    if (!empty($errores)) {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}

// Obtener información básica del cliente
$stmt = $conn->prepare("SELECT nombre, apellidos, correo FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - <?php echo htmlspecialchars($cliente['nombre']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f2f2f2;
            line-height: 1.6;
        }

        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .navegacion {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .navegacion a {
            color: #007185;
            text-decoration: none;
            margin-right: 15px;
            font-weight: bold;
        }

        .navegacion a:hover {
            text-decoration: underline;
        }

        .password-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .password-header {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .password-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .password-body {
            padding: 30px;
        }

        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: #007185;
            box-shadow: 0 0 0 2px rgba(0, 113, 133, 0.1);
        }

        .password-requirements {
            background: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 14px;
        }

        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #6c757d;
        }

        .password-strength {
            margin-top: 10px;
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: width 0.3s, background-color 0.3s;
            width: 0%;
            background: #dc3545;
        }

        .password-strength.weak .password-strength-bar { width: 25%; background: #dc3545; }
        .password-strength.fair .password-strength-bar { width: 50%; background: #fd7e14; }
        .password-strength.good .password-strength-bar { width: 75%; background: #ffc107; }
        .password-strength.strong .password-strength-bar { width: 100%; background: #28a745; }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 16px;
            margin: 5px;
        }

        .btn-primary {
            background-color: #007185;
            color: white;
        }

        .btn-primary:hover {
            background-color: #005d6b;
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
            color: white;
            text-decoration: none;
        }

        .security-tips {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .security-tips h4 {
            margin: 0 0 15px 0;
            color: #856404;
        }

        .show-password {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: #6c757d;
        }

        .show-password input[type="checkbox"] {
            width: auto;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .password-header {
                padding: 20px;
            }
            
            .password-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>🔒 Cambiar Contraseña</h1>
    </div>

    <div class="container">
        <div class="navegacion">
            <a href="perfil.php">← Volver al Perfil</a>
            <a href="inicioCliente.php">Panel Principal</a>
        </div>

        <div class="password-card">
            <div class="password-header">
                <div class="password-icon">🔐</div>
                <h2>Actualizar Contraseña</h2>
                <p>Mantén tu cuenta segura con una contraseña fuerte</p>
            </div>

            <div class="password-body">
                <?php if ($mensaje): ?>
                    <div class="mensaje <?php echo $tipo_mensaje; ?>">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="cambiarPassword.php" id="passwordForm">
                    <div class="form-group">
                        <label for="password_actual">Contraseña Actual *</label>
                        <input type="password" id="password_actual" name="password_actual" required>
                        <div class="show-password">
                            <input type="checkbox" id="show_current">
                            <label for="show_current">Mostrar contraseña</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_nueva">Nueva Contraseña *</label>
                        <input type="password" id="password_nueva" name="password_nueva" required>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="show-password">
                            <input type="checkbox" id="show_new">
                            <label for="show_new">Mostrar contraseña</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmar">Confirmar Nueva Contraseña *</label>
                        <input type="password" id="password_confirmar" name="password_confirmar" required>
                        <div class="show-password">
                            <input type="checkbox" id="show_confirm">
                            <label for="show_confirm">Mostrar contraseña</label>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <h4>📋 Requisitos de la contraseña:</h4>
                        <ul>
                            <li>Mínimo 6 caracteres</li>
                            <li>Se recomienda incluir letras mayúsculas y minúsculas</li>
                            <li>Se recomienda incluir números</li>
                            <li>Se recomienda incluir símbolos especiales</li>
                        </ul>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" id="submitBtn">🔒 Cambiar Contraseña</button>
                        <a href="perfil.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>

                <div class="security-tips">
                    <h4>💡 Consejos de Seguridad</h4>
                    <ul>
                        <li>Usa una contraseña única para esta cuenta</li>
                        <li>No compartas tu contraseña con nadie</li>
                        <li>Cambia tu contraseña regularmente</li>
                        <li>Evita usar información personal en tu contraseña</li>
                        <li>Considera usar un gestor de contraseñas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseñas
        document.getElementById('show_current').addEventListener('change', function() {
            const input = document.getElementById('password_actual');
            input.type = this.checked ? 'text' : 'password';
        });

        document.getElementById('show_new').addEventListener('change', function() {
            const input = document.getElementById('password_nueva');
            input.type = this.checked ? 'text' : 'password';
        });

        document.getElementById('show_confirm').addEventListener('change', function() {
            const input = document.getElementById('password_confirmar');
            input.type = this.checked ? 'text' : 'password';
        });

        // Verificar fortaleza de contraseña
        document.getElementById('password_nueva').addEventListener('input', function() {
            const password = this.value;
            const strengthElement = document.getElementById('passwordStrength');
            let strength = 0;

            // Criterios de fortaleza
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;

            // Aplicar clase según fortaleza
            strengthElement.className = 'password-strength';
            if (strength <= 1) strengthElement.classList.add('weak');
            else if (strength <= 2) strengthElement.classList.add('fair');
            else if (strength <= 3) strengthElement.classList.add('good');
            else strengthElement.classList.add('strong');
        });

        // Validar que las contraseñas coincidan
        function validatePasswords() {
            const newPassword = document.getElementById('password_nueva').value;
            const confirmPassword = document.getElementById('password_confirmar').value;
            const submitBtn = document.getElementById('submitBtn');

            if (newPassword && confirmPassword) {
                if (newPassword === confirmPassword) {
                    submitBtn.disabled = false;
                    document.getElementById('password_confirmar').style.borderColor = '#28a745';
                } else {
                    submitBtn.disabled = true;
                    document.getElementById('password_confirmar').style.borderColor = '#dc3545';
                }
            } else {
                submitBtn.disabled = false;
                document.getElementById('password_confirmar').style.borderColor = '#ddd';
            }
        }

        document.getElementById('password_nueva').addEventListener('input', validatePasswords);
        document.getElementById('password_confirmar').addEventListener('input', validatePasswords);

        // Confirmación antes de enviar
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que quieres cambiar tu contraseña?')) {
                e.preventDefault();
            }
        });

        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(function() {
            const mensaje = document.querySelector('.mensaje');
            if (mensaje) {
                mensaje.style.transition = 'opacity 0.5s';
                mensaje.style.opacity = '0';
                setTimeout(() => mensaje.remove(), 500);
            }
        }, 5000);
    </script>

</body>
</html>
