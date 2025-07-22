<?php
session_start();

// Si no hay cliente autenticado, redirige al login
if (empty($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/conexion.php';

if (!isset($_GET['id'])) {
    echo "ID no especificado";
    exit;
}

$id = intval($_GET['id']);

// Obtener producto con información del vendedor
$stmt = $conn->prepare("
    SELECT p.*, v.nombre_empresa as vendedor_nombre, v.telefono as vendedor_telefono, v.correo as vendedor_correo 
    FROM productos p 
    JOIN vendedores v ON p.id_vendedor = v.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();
$stmt->close();
$conn->close();

if (!$producto) {
    echo "Producto no encontrado";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['nombre']) ?> - Detalle del Producto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
            margin: -20px -20px 20px -20px;
        }
        .volver-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .volver-btn:hover {
            background-color: #5a6268;
            color: white;
            text-decoration: none;
        }
        .producto-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .producto-detalle {
            display: flex;
            gap: 40px;
            padding: 30px;
            flex-wrap: wrap;
        }
        .galeria {
            flex: 1;
            min-width: 300px;
        }
        .imagen-principal {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .imagenes-secundarias {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .imagen-secundaria {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .imagen-secundaria:hover {
            border-color: #007185;
        }
        .info-producto {
            flex: 1;
            min-width: 300px;
        }
        .titulo-producto {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }
        .precio-producto {
            font-size: 2.5em;
            font-weight: bold;
            color: #007185;
            margin-bottom: 20px;
        }
        .info-item {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            min-width: 120px;
        }
        .info-value {
            color: #333;
        }
        .vendedor-info {
            background-color: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .vendedor-info h4 {
            color: #007185;
            margin-bottom: 10px;
        }
        .acciones {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
            font-size: 1.1em;
        }
        .btn-primary {
            background-color: #28a745;
            color: white;
        }
        .btn-primary:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background-color: #007185;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #005d6b;
            color: white;
            text-decoration: none;
        }
        .disponibilidad {
            color: #28a745;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        .descripcion-completa {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>🛍️ Detalle del Producto</h1>
</div>

<a href="catalogo.php" class="volver-btn">← Volver al Catálogo</a>

<div class="producto-container">
    <div class="producto-detalle">
        <!-- Galería de imágenes -->
        <div class="galeria">
            <?php if (!empty($producto['imagen_principal'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>" 
                     alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                     class="imagen-principal" id="imagenPrincipal">
            <?php endif; ?>
            
            <div class="imagenes-secundarias">
                <?php if (!empty($producto['imagen_principal'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>" 
                         alt="Imagen 1" class="imagen-secundaria" onclick="cambiarImagen(this.src)">
                <?php endif; ?>
                <?php if (!empty($producto['imagen_secundaria1'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria1']) ?>" 
                         alt="Imagen 2" class="imagen-secundaria" onclick="cambiarImagen(this.src)">
                <?php endif; ?>
                <?php if (!empty($producto['imagen_secundaria2'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria2']) ?>" 
                         alt="Imagen 3" class="imagen-secundaria" onclick="cambiarImagen(this.src)">
                <?php endif; ?>
            </div>
        </div>

        <!-- Información del producto -->
        <div class="info-producto">
            <h1 class="titulo-producto"><?= htmlspecialchars($producto['nombre']) ?></h1>
            <div class="precio-producto">₡<?= number_format($producto['precio'], 0, ',', '.') ?></div>
            
            <?php if ($producto['unidades'] > 0): ?>
                <div class="disponibilidad">✅ Disponible (<?= $producto['unidades'] ?> unidades)</div>
            <?php else: ?>
                <div style="color: #dc3545; font-weight: bold;">❌ Agotado</div>
            <?php endif; ?>
            
            <div class="info-item">
                <span class="info-label">Categoría:</span>
                <span class="info-value"><?= htmlspecialchars($producto['categoria']) ?></span>
            </div>
            
            <?php if ($producto['tallas']): ?>
            <div class="info-item">
                <span class="info-label">Tallas:</span>
                <span class="info-value"><?= htmlspecialchars($producto['tallas']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($producto['color']): ?>
            <div class="info-item">
                <span class="info-label">Color:</span>
                <span class="info-value"><?= htmlspecialchars($producto['color']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($producto['garantia']): ?>
            <div class="info-item">
                <span class="info-label">Garantía:</span>
                <span class="info-value"><?= htmlspecialchars($producto['garantia']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($producto['dimensiones']): ?>
            <div class="info-item">
                <span class="info-label">Dimensiones:</span>
                <span class="info-value"><?= htmlspecialchars($producto['dimensiones']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($producto['peso']): ?>
            <div class="info-item">
                <span class="info-label">Peso:</span>
                <span class="info-value"><?= number_format($producto['peso'], 2) ?> kg</span>
            </div>
            <?php endif; ?>
            
            <div class="acciones">
                <?php if ($producto['unidades'] > 0): ?>
                    <button onclick="agregarAlCarrito(<?= $producto['id'] ?>)" class="btn btn-primary">🛒 Agregar al Carrito</button>
                <?php endif; ?>
                <a href="carrito.php" class="btn btn-secondary">🛒 Ver Carrito</a>
            </div>
            
            <!-- Información del vendedor -->
            <div class="vendedor-info">
                <h4>🏪 Vendido por:</h4>
                <p><strong><?= htmlspecialchars($producto['vendedor_nombre']) ?></strong></p>
                <?php if ($producto['vendedor_telefono']): ?>
                    <p>📱 <?= htmlspecialchars($producto['vendedor_telefono']) ?></p>
                <?php endif; ?>
                <p>📧 <?= htmlspecialchars($producto['vendedor_correo']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Descripción completa -->
    <?php if ($producto['descripcion']): ?>
    <div class="descripcion-completa">
        <h3>📝 Descripción del Producto</h3>
        <p><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
function cambiarImagen(src) {
    document.getElementById('imagenPrincipal').src = src;
}

function agregarAlCarrito(productoId) {
    const form = new FormData();
    form.append('accion', 'agregar');
    form.append('producto_id', productoId);
    
    fetch('carrito.php', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            mostrarToast("🛒 " + data.mensaje);
        } else {
            mostrarToast("❌ Error al agregar al carrito");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast("❌ Error al agregar al carrito");
    });
}

function mostrarToast(msg) {
    const toast = document.createElement("div");
    toast.textContent = msg;
    Object.assign(toast.style, {
        position: "fixed",
        bottom: "20px",
        right: "20px",
        background: "#28a745",
        color: "white",
        padding: "12px 20px",
        borderRadius: "6px",
        boxShadow: "0 2px 6px rgba(0,0,0,0.2)",
        zIndex: "1000"
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

</body>
</html>
