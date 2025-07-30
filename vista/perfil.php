<?php
session_start(); // 🔐 Iniciar sesión

// 🚫 Verificar si el cliente está autenticado
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/conexion.php';
$cliente_id = $_SESSION['cliente_id'];

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? ''); // Form uses apellidos, map to apellido column
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (empty($apellidos)) $errores[] = "Los apellidos son obligatorios";
    if (empty($telefono)) $errores[] = "El teléfono es obligatorio";
    if (empty($direccion)) $errores[] = "La dirección es obligatoria";
    if (empty($provincia)) $errores[] = "La provincia es obligatoria";
    
    // Validar formato de teléfono (básico)
    if (!preg_match('/^[0-9]{8}$/', $telefono)) {
        $errores[] = "El teléfono debe tener 8 dígitos";
    }
    
    if (empty($errores)) {
        try {
            // Actualizar información del cliente
            $stmt_update = $conn->prepare("
                UPDATE clientes SET 
                    nombre = ?, apellido = ?, telefono = ?, 
                    direccion = ?, provincia = ?, fecha_nacimiento = ?, 
                    genero = ?, newsletter = ?
                WHERE id = ?
            ");
            
            $stmt_update->bind_param(
                "sssssssii",
                $nombre, $apellidos, $telefono,
                $direccion, $provincia, $fecha_nacimiento,
                $genero, $newsletter, $cliente_id
            );
                
            if ($stmt_update->execute()) {
                $mensaje = "Perfil actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar el perfil";
                $tipo_mensaje = "error";
            }
            $stmt_update->close();
            
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}

// Obtener información actual del cliente
$stmt = $conn->prepare("
    SELECT nombre, apellido, correo, telefono, direccion, 
           provincia, fecha_nacimiento, genero, newsletter, 
           verificado, fecha_registro
    FROM clientes 
    WHERE id = ?
");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
} else {
    header('Location: loginCliente.php');
    exit;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></title>
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
            max-width: 800px;
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

        .perfil-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .perfil-header {
            background: linear-gradient(135deg, #007185, #005d6b);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            font-size: 48px;
        }

        .perfil-nombre {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .perfil-email {
            font-size: 16px;
            opacity: 0.9;
        }

        .estado-verificacion {
            margin-top: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
        }

        .verificado {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .no-verificado {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .perfil-body {
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #007185;
            box-shadow: 0 0 0 2px rgba(0, 113, 133, 0.1);
        }

        input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

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

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
            color: white;
            text-decoration: none;
        }

        .info-adicional {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .cambiar-password {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .perfil-header {
                padding: 20px;
            }
            
            .perfil-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Mi Perfil</h1>
    </div>

    <div class="container">
        <div class="navegacion">
            <a href="inicioCliente.php">← Volver al Panel</a>
            <a href="misPedidos.php">Mis Pedidos</a>
            <a href="index.php">Catálogo</a>
            <a href="carrito.php">Carrito</a>
        </div>

        <div class="perfil-card">
            <div class="perfil-header">
                <div class="avatar">
                    👤
                </div>
                <div class="perfil-nombre">
                    <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                </div>
                <div class="perfil-email">
                    <?php echo htmlspecialchars($cliente['correo']); ?>
                </div>
                <div class="estado-verificacion <?php echo $cliente['verificado'] ? 'verificado' : 'no-verificado'; ?>">
                    <?php echo $cliente['verificado'] ? '✅ Cuenta Verificada' : '⚠️ Cuenta No Verificada'; ?>
                </div>
            </div>

            <div class="perfil-body">
                <?php if ($mensaje): ?>
                    <div class="mensaje <?php echo $tipo_mensaje; ?>">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="perfil.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="apellidos">Apellidos *</label>
                            <input type="text" id="apellidos" name="apellidos" 
                                   value="<?php echo htmlspecialchars($cliente['apellido']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($cliente['correo']); ?>" readonly>
                        <small style="color: #666; font-size: 14px;">
                            El correo no se puede cambiar. Si necesitas modificarlo, contacta soporte.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono *</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($cliente['telefono']); ?>" 
                                   pattern="[0-9]{8}" title="Debe tener 8 dígitos" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección *</label>
                        <textarea id="direccion" name="direccion" rows="3" required><?php echo htmlspecialchars($cliente['direccion']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="provincia">Provincia *</label>
                            <select id="provincia" name="provincia" required>
                                <option value="">Seleccionar provincia</option>
                                <option value="San José" <?php echo $cliente['provincia'] === 'San José' ? 'selected' : ''; ?>>San José</option>
                                <option value="Alajuela" <?php echo $cliente['provincia'] === 'Alajuela' ? 'selected' : ''; ?>>Alajuela</option>
                                <option value="Cartago" <?php echo $cliente['provincia'] === 'Cartago' ? 'selected' : ''; ?>>Cartago</option>
                                <option value="Heredia" <?php echo $cliente['provincia'] === 'Heredia' ? 'selected' : ''; ?>>Heredia</option>
                                <option value="Guanacaste" <?php echo $cliente['provincia'] === 'Guanacaste' ? 'selected' : ''; ?>>Guanacaste</option>
                                <option value="Puntarenas" <?php echo $cliente['provincia'] === 'Puntarenas' ? 'selected' : ''; ?>>Puntarenas</option>
                                <option value="Limón" <?php echo $cliente['provincia'] === 'Limón' ? 'selected' : ''; ?>>Limón</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" 
                                   value="<?php echo $cliente['fecha_nacimiento']; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="genero">Género</label>
                        <select id="genero" name="genero">
                            <option value="">No especificar</option>
                            <option value="masculino" <?php echo $cliente['genero'] === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="femenino" <?php echo $cliente['genero'] === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                            <option value="otro" <?php echo $cliente['genero'] === 'otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="newsletter" name="newsletter" 
                                   <?php echo $cliente['newsletter'] ? 'checked' : ''; ?>>
                            <label for="newsletter">📧 Recibir boletín informativo y ofertas especiales</label>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                        <a href="inicioCliente.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>

                <div class="info-adicional">
                    <h3>📊 Información de la Cuenta</h3>
                    <div class="info-item">
                        <span><strong>Fecha de Registro:</strong></span>
                        <span><?php echo date('d/m/Y H:i', strtotime($cliente['fecha_registro'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span><strong>Estado de Verificación:</strong></span>
                        <span><?php echo $cliente['verificado'] ? 'Verificado ✅' : 'Pendiente ⏳'; ?></span>
                    </div>
                    <div class="info-item">
                        <span><strong>Newsletter:</strong></span>
                        <span><?php echo $cliente['newsletter'] ? 'Suscrito 📧' : 'No suscrito 📭'; ?></span>
                    </div>
                </div>

                <div class="cambiar-password">
                    <h4>🔒 Cambiar Contraseña</h4>
                    <p>¿Quieres cambiar tu contraseña? Por seguridad, esto requiere verificación adicional.</p>
                    <a href="cambiarPassword.php" class="btn btn-secondary">🔑 Cambiar Contraseña</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validación en tiempo real
        document.getElementById('telefono').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            if (e.target.value.length > 8) {
                e.target.value = e.target.value.substring(0, 8);
            }
        });

        // Confirmación antes de guardar
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que quieres guardar los cambios en tu perfil?')) {
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
