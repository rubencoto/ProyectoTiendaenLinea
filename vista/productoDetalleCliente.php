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
if ($conn->isConnected() && $conn->getConnectionType() === 'pdo') {
    $stmt = $conn->getConnection()->prepare("
        SELECT p.*, v.nombre_empresa as vendedor_nombre, v.telefono as vendedor_telefono, v.correo as vendedor_correo 
        FROM productos p 
        JOIN vendedores v ON p.id_vendedor = v.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Fallback method using unified connection
    $stmt = $conn->prepare("
        SELECT p.*, v.nombre_empresa as vendedor_nombre, v.telefono as vendedor_telefono, v.correo as vendedor_correo 
        FROM productos p 
        JOIN vendedores v ON p.id_vendedor = v.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();
}
// PDO automatically handles cleanup - no need to close

if (!$producto) {
    echo "Producto no encontrado";
    exit;
}

// Obtener reseñas del producto
if ($conn->isConnected() && $conn->getConnectionType() === 'pdo') {
    $stmt_reviews = $conn->getConnection()->prepare("
        SELECT r.*, c.nombre as cliente_nombre 
        FROM reseñas r 
        JOIN clientes c ON r.cliente_id = c.id 
        WHERE r.producto_id = ? 
        ORDER BY r.fecha DESC
    ");
    $stmt_reviews->execute([$id]);
    $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fallback for other connection types
    $reviews = [];
}

// Calcular promedio de estrellas
$total_reviews = count($reviews);
$average_rating = 0;
if ($total_reviews > 0) {
    $sum_stars = array_sum(array_column($reviews, 'estrellas'));
    $average_rating = round($sum_stars / $total_reviews, 1);
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
        .reviews-section {
            background-color: #fff;
            padding: 30px;
            border-top: 1px solid #e9ecef;
        }
        .reviews-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .average-rating {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .rating-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
        }
        .stars-display {
            color: #ffc107;
            font-size: 1.5em;
        }
        .review-count {
            color: #666;
            font-size: 1.1em;
        }
        .review-item {
            border-bottom: 1px solid #e9ecef;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        .review-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .reviewer-name {
            font-weight: bold;
            color: #333;
        }
        .review-stars {
            color: #ffc107;
            font-size: 1.2em;
        }
        .review-date {
            color: #666;
            font-size: 0.9em;
        }
        .review-comment {
            color: #555;
            line-height: 1.6;
            margin-top: 10px;
        }
        .no-reviews {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px 0;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Detalle del Producto</h1>
</div>

<a href="index.php" class="volver-btn">← Volver al Catálogo</a>

<div style="text-align: right; margin-bottom: 10px;">
    <a href="carrito.php" style="background-color: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold;">
        Ver Carrito
        <?php 
        $cantidad_total = 0;
        if (isset($_SESSION['carrito'])) {
            $cantidad_total = array_sum($_SESSION['carrito']);
        }
        ?>
        <span id="cart-count" style="background-color: #dc3545; border-radius: 50%; padding: 2px 6px; font-size: 0.8em; margin-left: 5px; <?= $cantidad_total > 0 ? '' : 'display: none;' ?>"><?= $cantidad_total ?></span>
    </a>
</div>

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
            
            <?php if ($producto['stock'] > 0): ?>
                <div class="disponibilidad">Disponible (<?= $producto['stock'] ?> unidades)</div>
            <?php else: ?>
                <div style="color: #dc3545; font-weight: bold;">Agotado</div>
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
                <?php if ($producto['stock'] > 0): ?>
                    <button onclick="agregarAlCarrito(<?= $producto['id'] ?>)" class="btn btn-primary">Agregar al Carrito</button>
                <?php else: ?>
                    <button class="btn btn-primary" disabled style="background-color: #6c757d; cursor: not-allowed;">Producto Agotado</button>
                <?php endif; ?>
            </div>
            
            <!-- Información del vendedor -->
            <div class="vendedor-info">
                <h4>Vendido por:</h4>
                <p><strong><?= htmlspecialchars($producto['vendedor_nombre']) ?></strong></p>
                <?php if ($producto['vendedor_telefono']): ?>
                    <p><?= htmlspecialchars($producto['vendedor_telefono']) ?></p>
                <?php endif; ?>
                <p><?= htmlspecialchars($producto['vendedor_correo']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Descripción completa -->
    <?php if ($producto['descripcion']): ?>
    <div class="descripcion-completa">
        <h3>Descripción del Producto</h3>
        <p><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Sección de Reseñas -->
    <div class="reviews-section">
        <div class="reviews-header">
            <h3>Reseñas de Clientes</h3>
            <?php if ($total_reviews > 0): ?>
                <div class="average-rating">
                    <span class="rating-number"><?= $average_rating ?></span>
                    <div>
                        <div class="stars-display">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($average_rating)) {
                                    echo '★';
                                } elseif ($i <= $average_rating) {
                                    echo '☆';
                                } else {
                                    echo '☆';
                                }
                            }
                            ?>
                        </div>
                        <div class="review-count"><?= $total_reviews ?> reseña<?= $total_reviews != 1 ? 's' : '' ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_reviews > 0): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <span class="reviewer-name"><?= htmlspecialchars($review['cliente_nombre']) ?></span>
                            <div class="review-stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['estrellas'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                        </div>
                        <span class="review-date"><?= date('d/m/Y', strtotime($review['fecha'])) ?></span>
                    </div>
                    <?php if (!empty($review['comentario'])): ?>
                        <div class="review-comment"><?= nl2br(htmlspecialchars($review['comentario'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-reviews">
                <p>Este producto aún no tiene reseñas.</p>
                <p>¡Sé el primero en dejarnos tu opinión!</p>
            </div>
        <?php endif; ?>
    </div>
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
            mostrarToast(data.mensaje);
            // Update cart count immediately
            updateCartCount();
        } else {
            mostrarToast("Error al agregar al carrito");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast("Error al agregar al carrito");
    });
}

function updateCartCount() {
    fetch('../controlador/getCartCount.php')
    .then(response => response.json())
    .then(data => {
        const cartCountElement = document.getElementById('cart-count');
        if (data.count > 0) {
            cartCountElement.textContent = data.count;
            cartCountElement.style.display = 'inline';
        } else {
            cartCountElement.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
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
