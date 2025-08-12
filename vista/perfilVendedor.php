<?php
session_start(); // 🔐 Iniciar sesión

// 🚫 Verificar si el vendedor está autenticado
if (!isset($_SESSION['id'])) {
    header('Location: loginVendedor.php');
    exit;
}

require_once '../modelo/conexion.php';
$vendedor_id = $_SESSION['id'];

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion1 = trim($_POST['direccion1'] ?? '');
    $direccion2 = trim($_POST['direccion2'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $cedula_juridica = trim($_POST['cedula_juridica'] ?? '');
    $descripcion_tienda = trim($_POST['descripcion_tienda'] ?? '');
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre_empresa)) $errores[] = "El nombre de la empresa es obligatorio";
    if (empty($telefono)) $errores[] = "El teléfono es obligatorio";
    if (empty($direccion1)) $errores[] = "La dirección principal es obligatoria";
    if (empty($categoria)) $errores[] = "La categoría es obligatoria";
    if (empty($cedula_juridica)) $errores[] = "La cédula jurídica es obligatoria";
    if (empty($descripcion_tienda)) $errores[] = "La descripción de la tienda es obligatoria";
    
    // Validar formato de teléfono (básico)
    if (!preg_match('/^[0-9]{8}$/', $telefono)) {
        $errores[] = "El teléfono debe tener 8 dígitos";
    }
    
    if (empty($errores)) {
        try {
            // Actualizar información del vendedor
            $stmt_update = $conn->prepare("
                UPDATE vendedores SET 
                    nombre_empresa = ?, telefono = ?, direccion1 = ?, direccion2 = ?, 
                    categoria = ?, cedula_juridica = ?, descripcion_tienda = ?
                WHERE id = ?
            ");
            
            $executed = $stmt_update->execute([
                $nombre_empresa, $telefono, $direccion1, $direccion2, 
                $categoria, $cedula_juridica, $descripcion_tienda, $vendedor_id
            ]);
                
            if ($executed) {
                $mensaje = "Perfil actualizado exitosamente";
                $tipo_mensaje = "success";
                
                // Refresh vendor data after successful update
                $stmt_refresh = $conn->prepare("
                    SELECT nombre, apellido, nombre_empresa, correo, telefono, direccion1, direccion2, 
                           categoria, cedula_juridica, descripcion_tienda, fecha_registro, verificado
                    FROM vendedores 
                    WHERE id = ?
                ");
                $stmt_refresh->execute([$vendedor_id]);
                $vendedor = $stmt_refresh->fetch();
            } else {
                $mensaje = "Error al actualizar el perfil";
                $tipo_mensaje = "error";
            }
            
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}

// Obtener información actual del vendedor (only if not already loaded from update)
if (!isset($vendedor)) {
    $stmt = $conn->prepare("
        SELECT nombre, apellido, nombre_empresa, correo, telefono, direccion1, direccion2, 
               categoria, cedula_juridica, descripcion_tienda, fecha_registro, verificado
        FROM vendedores 
        WHERE id = ?
    ");
    $stmt->execute([$vendedor_id]);
    $vendedor = $stmt->fetch();

    if (!$vendedor) {
        header('Location: loginVendedor.php');
        exit;
    }
}

// Obtener estadísticas del vendedor
$stmt_productos = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE id_vendedor = ?");
$stmt_productos->execute([$vendedor_id]);
$productos_count = $stmt_productos->fetchColumn();

$stmt_pedidos = $conn->prepare("
    SELECT COUNT(DISTINCT dp.orden_id) as total 
    FROM detalle_pedidos dp 
    JOIN productos p ON dp.producto_id = p.id 
    WHERE p.id_vendedor = ?
");
$stmt_pedidos->execute([$vendedor_id]);
$pedidos_count = $stmt_pedidos->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo htmlspecialchars($vendedor['nombre_empresa']); ?></title>
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

        .estadisticas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            display: block;
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

        @media (max-width: 768px) {
            .form-row, .estadisticas {
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
        <h1>Mi Perfil de Vendedor</h1>
    </div>

    <div class="container">
        <div class="navegacion">
            <a href="inicioVendedor.php">← Volver al Panel</a>
            <a href="productos.php">Mis Productos</a>
            <a href="gestionPedidos.php">Gestión de Pedidos</a>
            <a href="agregarproducto.php">Agregar Producto</a>
        </div>

        <div class="perfil-card">
            <div class="perfil-header">
                <div class="avatar">
                    🏪
                </div>
                <div class="perfil-nombre">
                    <?php echo htmlspecialchars($vendedor['nombre_empresa']); ?>
                </div>
                <div class="perfil-email">
                    <?php echo htmlspecialchars($vendedor['correo']); ?>
                </div>
                <div class="estado-verificacion <?php echo $vendedor['verificado'] ? 'verificado' : 'no-verificado'; ?>">
                    <?php echo $vendedor['verificado'] ? '✅ Vendedor Verificado' : '⚠️ Pendiente de Verificación'; ?>
                </div>
                
                <div class="estadisticas">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $productos_count; ?></span>
                        <span>Productos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $pedidos_count; ?></span>
                        <span>Pedidos</span>
                    </div>
                </div>
            </div>

            <div class="perfil-body">
                <?php if ($mensaje): ?>
                    <div class="mensaje <?php echo $tipo_mensaje; ?>">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="perfilVendedor.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($vendedor['nombre']); ?>" readonly>
                            <small style="color: #666; font-size: 14px;">
                                El nombre no se puede cambiar. Si necesitas modificarlo, contacta soporte.
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" 
                                   value="<?php echo htmlspecialchars($vendedor['apellido']); ?>" readonly>
                            <small style="color: #666; font-size: 14px;">
                                El apellido no se puede cambiar. Si necesitas modificarlo, contacta soporte.
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($vendedor['correo']); ?>" readonly>
                        <small style="color: #666; font-size: 14px;">
                            El correo no se puede cambiar. Si necesitas modificarlo, contacta soporte.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="nombre_empresa">Nombre de la Empresa *</label>
                        <input type="text" id="nombre_empresa" name="nombre_empresa" 
                               value="<?php echo htmlspecialchars($vendedor['nombre_empresa']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono *</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($vendedor['telefono']); ?>" 
                                   pattern="[0-9]{8}" title="Debe tener 8 dígitos" required>
                        </div>
                        <div class="form-group">
                            <label for="cedula_juridica">Cédula Jurídica *</label>
                            <input type="text" id="cedula_juridica" name="cedula_juridica" 
                                   value="<?php echo htmlspecialchars($vendedor['cedula_juridica']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="Ropa y Accesorios" <?php echo $vendedor['categoria'] === 'Ropa y Accesorios' ? 'selected' : ''; ?>>Ropa y Accesorios</option>
                            <option value="Electrónicos" <?php echo $vendedor['categoria'] === 'Electrónicos' ? 'selected' : ''; ?>>Electrónicos</option>
                            <option value="Hogar y Jardín" <?php echo $vendedor['categoria'] === 'Hogar y Jardín' ? 'selected' : ''; ?>>Hogar y Jardín</option>
                            <option value="Deportes" <?php echo $vendedor['categoria'] === 'Deportes' ? 'selected' : ''; ?>>Deportes</option>
                            <option value="Belleza y Salud" <?php echo $vendedor['categoria'] === 'Belleza y Salud' ? 'selected' : ''; ?>>Belleza y Salud</option>
                            <option value="Alimentación" <?php echo $vendedor['categoria'] === 'Alimentación' ? 'selected' : ''; ?>>Alimentación</option>
                            <option value="Libros y Medios" <?php echo $vendedor['categoria'] === 'Libros y Medios' ? 'selected' : ''; ?>>Libros y Medios</option>
                            <option value="Juguetes" <?php echo $vendedor['categoria'] === 'Juguetes' ? 'selected' : ''; ?>>Juguetes</option>
                            <option value="Automóviles" <?php echo $vendedor['categoria'] === 'Automóviles' ? 'selected' : ''; ?>>Automóviles</option>
                            <option value="Otros" <?php echo $vendedor['categoria'] === 'Otros' ? 'selected' : ''; ?>>Otros</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="direccion1">Dirección Principal *</label>
                            <input type="text" id="direccion1" name="direccion1" 
                                   value="<?php echo htmlspecialchars($vendedor['direccion1']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="direccion2">Dirección Secundaria</label>
                            <input type="text" id="direccion2" name="direccion2" 
                                   value="<?php echo htmlspecialchars($vendedor['direccion2']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion_tienda">Descripción de la Tienda *</label>
                        <textarea id="descripcion_tienda" name="descripcion_tienda" rows="4" 
                                  placeholder="Cuéntanos sobre tu empresa, productos y servicios..." required><?php echo htmlspecialchars($vendedor['descripcion_tienda']); ?></textarea>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                        <a href="inicioVendedor.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>

                <div class="info-adicional">
                    <h3>📊 Información de la Cuenta</h3>
                    <div class="info-item">
                        <span><strong>Fecha de Registro:</strong></span>
                        <span><?php echo date('d/m/Y H:i', strtotime($vendedor['fecha_registro'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span><strong>Estado de Verificación:</strong></span>
                        <span><?php echo $vendedor['verificado'] ? 'Verificado ✅' : 'Pendiente ⏳'; ?></span>
                    </div>
                    <div class="info-item">
                        <span><strong>Total de Productos:</strong></span>
                        <span><?php echo $productos_count; ?> productos</span>
                    </div>
                    <div class="info-item">
                        <span><strong>Total de Pedidos:</strong></span>
                        <span><?php echo $pedidos_count; ?> pedidos</span>
                    </div>
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