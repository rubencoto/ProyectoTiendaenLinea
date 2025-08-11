<?php
session_start();

// Verificar si el vendedor está autenticado
if (!isset($_SESSION['vendedor_id'])) {
    header('Location: loginVendedor.php');
    exit;
}

require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

$vendedor_id = $_SESSION['vendedor_id'];

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
    $biografia = trim($_POST['biografia'] ?? '');
    $redes = trim($_POST['redes'] ?? '');
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre_empresa)) $errores[] = "El nombre de la empresa es obligatorio";
    if (empty($telefono)) $errores[] = "El teléfono es obligatorio";
    if (empty($categoria)) $errores[] = "La categoría es obligatoria";
    if (empty($cedula_juridica)) $errores[] = "La cédula jurídica es obligatoria";
    if (empty($biografia)) $errores[] = "La biografía es obligatoria";
    
    // Validar formato de teléfono (básico)
    if (!empty($telefono) && !preg_match('/^[0-9]{8}$/', $telefono)) {
        $errores[] = "El teléfono debe tener 8 dígitos";
    }
    
    if (empty($errores)) {
        try {
            // Actualizar información del vendedor
            $stmt_update = $conn->prepare("
                UPDATE vendedores SET 
                    nombre_empresa = ?, telefono = ?, direccion1 = ?, direccion2 = ?, 
                    categoria = ?, cedula_juridica = ?, biografia = ?, redes = ?
                WHERE id = ?
            ");
            
            $executed = $stmt_update->execute([
                $nombre_empresa, $telefono, $direccion1, $direccion2,
                $categoria, $cedula_juridica, $biografia, $redes, $vendedor_id
            ]);
            
            if ($executed) {
                // Actualizar la sesión si se cambió el nombre de la empresa
                $_SESSION['nombre_empresa'] = $nombre_empresa;
                
                $mensaje = "Perfil actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar el perfil";
                $tipo_mensaje = "danger";
            }
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "danger";
    }
}

// Obtener información actual del vendedor
try {
    $stmt_vendedor = $conn->prepare("
        SELECT nombre_empresa, correo, telefono, direccion1, direccion2, categoria, 
               cedula_juridica, biografia, redes, logo, fecha_registro
        FROM vendedores 
        WHERE id = ?
    ");
    $stmt_vendedor->execute([$vendedor_id]);
    $vendedor = $stmt_vendedor->fetch();
    
    if (!$vendedor) {
        header('Location: loginVendedor.php');
        exit;
    }
} catch (Exception $e) {
    die("Error al obtener información del vendedor: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Vendedor - <?= htmlspecialchars($vendedor['nombre_empresa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px 0;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.2);
            overflow: hidden;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .logo-placeholder {
            font-size: 3rem;
            color: rgba(255,255,255,0.7);
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .section-title {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-row {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .navbar-custom {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="inicioVendedor.php">
            <i class="fas fa-store me-2"></i>Panel Vendedor
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="productos.php"><i class="fas fa-box me-1"></i>Productos</a>
            <a class="nav-link" href="gestionPedidos.php"><i class="fas fa-clipboard-list me-1"></i>Pedidos</a>
            <a class="nav-link active" href="perfilVendedor.php"><i class="fas fa-user-circle me-1"></i>Perfil</a>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container" style="padding-top: 100px;">
    
    <!-- Mensajes -->
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tarjeta de Perfil -->
    <div class="profile-card">
        <!-- Header del Perfil -->
        <div class="profile-header">
            <div class="logo-container">
                <?php if (!empty($vendedor['logo'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($vendedor['logo']) ?>" alt="Logo de <?= htmlspecialchars($vendedor['nombre_empresa']) ?>">
                <?php else: ?>
                    <div class="logo-placeholder">
                        <i class="fas fa-store"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h2 class="mb-1"><?= htmlspecialchars($vendedor['nombre_empresa']) ?></h2>
            <p class="mb-0 opacity-75">
                <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($vendedor['correo']) ?>
            </p>
            <small class="opacity-75">
                <i class="fas fa-calendar me-1"></i>Miembro desde <?= date('F Y', strtotime($vendedor['fecha_registro'])) ?>
            </small>
        </div>

        <!-- Estadísticas -->
        <div class="stats-row">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php
                            // Contar productos
                            $stmt_productos = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ?");
                            $stmt_productos->execute([$vendedor_id]);
                            $productos_count = $stmt_productos->fetchColumn();
                            echo $productos_count;
                            ?>
                        </div>
                        <div class="stat-label">Productos</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php
                            // Contar pedidos
                            $stmt_pedidos = $conn->prepare("
                                SELECT COUNT(DISTINCT dp.orden_id) as total 
                                FROM detalle_pedidos dp 
                                JOIN productos p ON dp.producto_id = p.id 
                                WHERE p.vendedor_id = ?
                            ");
                            $stmt_pedidos->execute([$vendedor_id]);
                            $pedidos_count = $stmt_pedidos->fetchColumn();
                            echo $pedidos_count;
                            ?>
                        </div>
                        <div class="stat-label">Pedidos</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?= htmlspecialchars($vendedor['categoria']) ?>
                        </div>
                        <div class="stat-label">Categoría</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cuerpo del Perfil -->
        <div class="profile-body">
            
            <!-- Información de la Empresa -->
            <div class="info-card">
                <h4 class="section-title">
                    <i class="fas fa-building"></i>
                    Información de la Empresa
                </h4>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($vendedor['nombre_empresa']) ?></p>
                        <p><strong>Categoría:</strong> <?= htmlspecialchars($vendedor['categoria']) ?></p>
                        <p><strong>Cédula Jurídica:</strong> <?= htmlspecialchars($vendedor['cedula_juridica']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Teléfono:</strong> <?= htmlspecialchars($vendedor['telefono']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($vendedor['correo']) ?></p>
                        <?php if (!empty($vendedor['redes'])): ?>
                        <p><strong>Redes Sociales:</strong> <?= htmlspecialchars($vendedor['redes']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($vendedor['biografia'])): ?>
                <div class="mt-3">
                    <strong>Biografía:</strong>
                    <p class="mt-2"><?= nl2br(htmlspecialchars($vendedor['biografia'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Formulario de Edición -->
            <form method="POST" enctype="multipart/form-data">
                <h4 class="section-title">
                    <i class="fas fa-edit"></i>
                    Editar Información
                </h4>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre_empresa" class="form-label">
                            <i class="fas fa-building me-1"></i>Nombre de la Empresa *
                        </label>
                        <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" 
                               value="<?= htmlspecialchars($vendedor['nombre_empresa']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">
                            <i class="fas fa-phone me-1"></i>Teléfono *
                        </label>
                        <input type="text" class="form-control" id="telefono" name="telefono" 
                               value="<?= htmlspecialchars($vendedor['telefono']) ?>" 
                               pattern="[0-9]{8}" title="Debe tener 8 dígitos" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="categoria" class="form-label">
                            <i class="fas fa-tags me-1"></i>Categoría *
                        </label>
                        <select class="form-select" id="categoria" name="categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <option value="Tecnología" <?= $vendedor['categoria'] === 'Tecnología' ? 'selected' : '' ?>>Tecnología</option>
                            <option value="Ropa y Accesorios" <?= $vendedor['categoria'] === 'Ropa y Accesorios' ? 'selected' : '' ?>>Ropa y Accesorios</option>
                            <option value="Hogar y Jardín" <?= $vendedor['categoria'] === 'Hogar y Jardín' ? 'selected' : '' ?>>Hogar y Jardín</option>
                            <option value="Deportes" <?= $vendedor['categoria'] === 'Deportes' ? 'selected' : '' ?>>Deportes</option>
                            <option value="Salud y Belleza" <?= $vendedor['categoria'] === 'Salud y Belleza' ? 'selected' : '' ?>>Salud y Belleza</option>
                            <option value="Libros y Educación" <?= $vendedor['categoria'] === 'Libros y Educación' ? 'selected' : '' ?>>Libros y Educación</option>
                            <option value="Arte y Manualidades" <?= $vendedor['categoria'] === 'Arte y Manualidades' ? 'selected' : '' ?>>Arte y Manualidades</option>
                            <option value="Alimentación" <?= $vendedor['categoria'] === 'Alimentación' ? 'selected' : '' ?>>Alimentación</option>
                            <option value="Mascotas" <?= $vendedor['categoria'] === 'Mascotas' ? 'selected' : '' ?>>Mascotas</option>
                            <option value="Otros" <?= $vendedor['categoria'] === 'Otros' ? 'selected' : '' ?>>Otros</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cedula_juridica" class="form-label">
                            <i class="fas fa-id-card me-1"></i>Cédula Jurídica *
                        </label>
                        <input type="text" class="form-control" id="cedula_juridica" name="cedula_juridica" 
                               value="<?= htmlspecialchars($vendedor['cedula_juridica']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="direccion1" class="form-label">
                        <i class="fas fa-map-marker-alt me-1"></i>Dirección Principal
                    </label>
                    <input type="text" class="form-control" id="direccion1" name="direccion1" 
                           value="<?= htmlspecialchars($vendedor['direccion1']) ?>"
                           placeholder="Dirección principal de la empresa">
                </div>

                <div class="mb-3">
                    <label for="direccion2" class="form-label">
                        <i class="fas fa-map-marker-alt me-1"></i>Dirección Secundaria
                    </label>
                    <input type="text" class="form-control" id="direccion2" name="direccion2" 
                           value="<?= htmlspecialchars($vendedor['direccion2']) ?>"
                           placeholder="Dirección secundaria (opcional)">
                </div>

                <div class="mb-3">
                    <label for="biografia" class="form-label">
                        <i class="fas fa-info-circle me-1"></i>Descripción/Biografía de la Empresa *
                    </label>
                    <textarea class="form-control" id="biografia" name="biografia" rows="4" 
                              placeholder="Cuéntanos sobre tu empresa, productos y servicios..." required><?= htmlspecialchars($vendedor['biografia']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="redes" class="form-label">
                        <i class="fas fa-share-alt me-1"></i>Redes Sociales
                    </label>
                    <input type="text" class="form-control" id="redes" name="redes" 
                           value="<?= htmlspecialchars($vendedor['redes']) ?>"
                           placeholder="Enlaces a redes sociales, sitio web, etc.">
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                    <a href="inicioVendedor.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </form>
            
            <!-- Sección para cambiar logo -->
            <div class="info-card mt-4">
                <h5 class="section-title">
                    <i class="fas fa-image"></i>
                    Cambiar Logo
                </h5>
                <p class="text-muted mb-3">Para cambiar el logo de su empresa, debe contactar al administrador del sistema.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    El logo actual <?= !empty($vendedor['logo']) ? 'se estableció' : 'no se ha configurado' ?> durante el registro. 
                    Para actualizarlo, envíe un correo a <strong>soporte@tiendaenlinea.com</strong>
                </div>
            </div>
            
            <!-- Enlaces útiles -->
            <div class="row mt-4">
                <div class="col-md-4 mb-3">
                    <a href="productos.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-box me-2"></i>Gestionar Productos
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="gestionPedidos.php" class="btn btn-outline-success w-100">
                        <i class="fas fa-clipboard-list me-2"></i>Ver Pedidos
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="cambiarContrasenaVendedor.php" class="btn btn-outline-warning w-100">
                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Validación adicional del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const telefono = document.getElementById('telefono').value;
    
    if (telefono && !/^[0-9]{8}$/.test(telefono)) {
        e.preventDefault();
        alert('El teléfono debe tener exactamente 8 dígitos.');
        document.getElementById('telefono').focus();
    }
});

// Auto-dismiss alerts después de 5 segundos
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</body>
</html>
