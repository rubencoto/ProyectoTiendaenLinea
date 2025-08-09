<?php
session_start(); // 🔐 Iniciar sesión

// 🚫 Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

// 📋 Obtener información del cliente
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';
$cliente_id = $_SESSION['cliente_id'];

// Get PDO connection for consistency
$db = DatabaseConnection::getInstance();
$pdo_conn = $db->getConnection();

// Obtener información del cliente
$stmt = $pdo_conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente_data = $stmt->fetch();

if ($cliente_data) {
    $nombre_completo = $cliente_data['nombre'] . ' ' . $cliente_data['apellido'];
} else {
    $nombre_completo = 'Cliente';
}

// Obtener las órdenes del cliente con paginación
$limite = 10; // Número de órdenes por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Contar total de órdenes
$stmt_count = $pdo_conn->prepare("SELECT COUNT(*) as total FROM ordenes WHERE cliente_id = ?");
$stmt_count->execute([$cliente_id]);
$count_result = $stmt_count->fetch();
$total_ordenes = $count_result['total'];
$total_paginas = ceil($total_ordenes / $limite);

// Obtener órdenes del cliente
$stmt_ordenes = $pdo_conn->prepare("
    SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
           o.estado, o.fecha_orden
    FROM ordenes o 
    WHERE o.cliente_id = ? 
    ORDER BY o.fecha_orden DESC 
    LIMIT $limite OFFSET $offset
");
$stmt_ordenes->execute([$cliente_id]);

$ordenes = [];
while ($row = $stmt_ordenes->fetch()) {
    error_log("MisPedidos: Found order ID: " . $row['id'] . " with numero_orden: " . $row['numero_orden'] . " for cliente_id: $cliente_id");
    $ordenes[] = $row;
}

error_log("MisPedidos: Total orders found for cliente_id $cliente_id: " . count($ordenes));

// Clean up orphaned details that don't have corresponding orders in the ordenes table
$cleanup_stmt = $pdo_conn->prepare("
    DELETE dp FROM detalle_pedidos dp 
    LEFT JOIN ordenes o ON dp.orden_id = o.id 
    WHERE o.id IS NULL
");
$cleanup_result = $cleanup_stmt->execute();
$affected_rows = $cleanup_stmt->rowCount();
if ($affected_rows > 0) {
    error_log("MisPedidos: Cleaned up $affected_rows orphaned detail records");
}

// Para cada orden, obtener los productos
foreach ($ordenes as &$orden) {
    $stmt_detalle = $pdo_conn->prepare("
        SELECT dp.cantidad, dp.precio_unitario, dp.subtotal, dp.producto_id,
               p.nombre as producto_nombre, p.imagen_principal
        FROM detalle_pedidos dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.orden_id = ? 
        AND EXISTS (SELECT 1 FROM ordenes o WHERE o.id = dp.orden_id)
    ");
    $stmt_detalle->execute([$orden['id']]);
    
    $productos = [];
    error_log("MisPedidos: Looking for details of order ID: " . $orden['id']);
    while ($row_detalle = $stmt_detalle->fetch()) {
        error_log("MisPedidos: Found product: " . $row_detalle['producto_nombre'] . " cantidad: " . $row_detalle['cantidad']);
        if ($row_detalle['imagen_principal']) {
            $row_detalle['imagen_principal'] = base64_encode($row_detalle['imagen_principal']);
        }
        
        // Check if customer has already reviewed this product
        $stmt_review = $pdo_conn->prepare("
            SELECT id, estrellas, comentario, fecha 
            FROM reseñas 
            WHERE cliente_id = ? AND producto_id = ?
        ");
        $stmt_review->execute([$cliente_id, $row_detalle['producto_id']]);
        $existing_review = $stmt_review->fetch();
        
        $row_detalle['existing_review'] = $existing_review;
        $productos[] = $row_detalle;
    }
    error_log("MisPedidos: Order ID " . $orden['id'] . " has " . count($productos) . " products");
    $orden['productos'] = $productos;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Historial de Compras</title>
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
            max-width: 1200px;
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

        .orden-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .orden-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .orden-info {
            display: flex;
            flex-direction: column;
        }

        .orden-info strong {
            color: #333;
            margin-bottom: 5px;
        }

        .estado {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            width: fit-content;
        }

        .estado.pendiente { background: #fff3cd; color: #856404; }
        .estado.procesando { background: #d1ecf1; color: #0c5460; }
        .estado.enviado { background: #d4edda; color: #155724; }
        .estado.entregado { background: #d4edda; color: #155724; }
        .estado.cancelado { background: #f8d7da; color: #721c24; }

        .productos-lista {
            padding: 20px;
        }

        .producto-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .producto-imagen {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
            border: 1px solid #ddd;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .producto-detalles {
            color: #666;
            font-size: 14px;
        }

        .producto-precio {
            text-align: right;
            font-weight: bold;
            color: #007185;
        }

        .orden-total {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            text-align: right;
        }

        .total-linea {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }

        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }

        .paginacion {
            text-align: center;
            margin: 30px 0;
        }

        .paginacion a, .paginacion .actual {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            color: #007185;
            text-decoration: none;
            border-radius: 4px;
        }

        .paginacion a:hover {
            background-color: #f5f5f5;
        }

        .paginacion .actual {
            background-color: #007185;
            color: white;
            border-color: #007185;
        }

        .mensaje-vacio {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .mensaje-vacio img {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .orden-header {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .producto-item {
                flex-direction: column;
                align-items: flex-start;
                text-align: center;
            }
            
            .producto-imagen {
                margin: 0 auto 10px auto;
            }
            
            .producto-precio {
                text-align: center;
                margin-top: 10px;
            }
        }

        /* Review Styles */
        .review-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .review-form {
            margin-top: 10px;
        }

        .stars-rating {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }

        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star:hover,
        .star.active {
            color: #ffc107;
        }

        .review-textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
        }

        .review-btn {
            background: #007185;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .review-btn:hover {
            background: #005d6b;
        }

        .review-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .existing-review {
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-top: 10px;
        }

        .review-stars {
            color: #ffc107;
            margin-bottom: 5px;
        }

        .review-text {
            color: #333;
            font-style: italic;
        }

        .review-date {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Mis Pedidos - <?php echo htmlspecialchars($nombre_completo); ?></h1>
    </div>

    <div class="container">
        <div class="navegacion">
            <a href="inicioCliente.php">← Volver al Panel</a>
            <a href="index.php">Continuar Comprando</a>
            <a href="carrito.php">Ver Carrito</a>
        </div>

        <?php if (empty($ordenes)): ?>
            <div class="mensaje-vacio">
                <h2>No tienes pedidos aún</h2>
                <p>¡Empieza a explorar nuestros productos y realiza tu primera compra!</p>
                <a href="index.php" style="color: #007185; text-decoration: none; font-weight: bold;">
                    Ver Catálogo de Productos
                </a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 20px; color: #666;">
                <p>Mostrando <?php echo count($ordenes); ?> de <?php echo $total_ordenes; ?> pedidos</p>
            </div>

            <?php foreach ($ordenes as $orden): ?>
                <div class="orden-card">
                    <div class="orden-header">
                        <div class="orden-info">
                            <strong>Número de Orden</strong>
                            <span><?php echo htmlspecialchars($orden['numero_orden']); ?></span>
                        </div>
                        <div class="orden-info">
                            <strong>Fecha del Pedido</strong>
                            <span><?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></span>
                        </div>
                        <div class="orden-info">
                            <strong>Estado</strong>
                            <span class="estado <?php echo $orden['estado']; ?>">
                                <?php echo ucfirst($orden['estado']); ?>
                            </span>
                        </div>
                        <div class="orden-info">
                            <strong>Total</strong>
                            <span style="font-size: 18px; font-weight: bold; color: #007185;">
                                ₡<?php echo number_format($orden['total'], 2); ?>
                            </span>
                        </div>
                    </div>

                    <div class="productos-lista">
                        <h4 style="margin-top: 0; color: #333;">Productos en este pedido:</h4>
                        <?php foreach ($orden['productos'] as $producto): ?>
                            <div class="producto-item">
                                <?php if (!empty($producto['imagen_principal'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo $producto['imagen_principal']; ?>" 
                                         alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>" 
                                         class="producto-imagen">
                                <?php else: ?>
                                    <div class="producto-imagen" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                        Sin imagen
                                    </div>
                                <?php endif; ?>
                                
                                <div class="producto-info">
                                    <div class="producto-nombre">
                                        <?php echo htmlspecialchars($producto['producto_nombre']); ?>
                                    </div>
                                    <div class="producto-detalles">
                                        Cantidad: <?php echo $producto['cantidad']; ?> | 
                                        Precio unitario: ₡<?php echo number_format($producto['precio_unitario'], 2); ?>
                                    </div>
                                </div>
                                
                                <div class="producto-precio">
                                    ₡<?php echo number_format($producto['subtotal'], 2); ?>
                                </div>
                            </div>

                            <!-- Review Section - Only show for completed orders -->
                            <?php if ($orden['estado'] === 'entregado' || $orden['estado'] === 'completado'): ?>
                                <div class="review-section" id="review-section-<?php echo $producto['producto_id']; ?>">
                                    <?php if ($producto['existing_review']): ?>
                                        <!-- Show existing review -->
                                        <h5 style="margin: 0 0 10px 0; color: #333;">Tu reseña:</h5>
                                        <div class="existing-review">
                                            <div class="review-stars">
                                                <?php 
                                                $estrellas = $producto['existing_review']['estrellas'];
                                                echo str_repeat('★', $estrellas) . str_repeat('☆', 5 - $estrellas);
                                                ?>
                                            </div>
                                            <div class="review-text">
                                                "<?php echo htmlspecialchars($producto['existing_review']['comentario']); ?>"
                                            </div>
                                            <div class="review-date">
                                                Reseñado el <?php echo date('d/m/Y', strtotime($producto['existing_review']['fecha'])); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Show review form -->
                                        <h5 style="margin: 0 0 10px 0; color: #333;">Reseñar este producto:</h5>
                                        <form class="review-form" id="review-form-<?php echo $producto['producto_id']; ?>">
                                            <input type="hidden" name="producto_id" value="<?php echo $producto['producto_id']; ?>">
                                            <input type="hidden" name="orden_id" value="<?php echo $orden['id']; ?>">
                                            <input type="hidden" name="rating" value="" class="rating-input">
                                            
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Calificación:</label>
                                                <div class="stars-rating" id="stars-<?php echo $producto['producto_id']; ?>">
                                                    <span class="star" data-rating="1">☆</span>
                                                    <span class="star" data-rating="2">☆</span>
                                                    <span class="star" data-rating="3">☆</span>
                                                    <span class="star" data-rating="4">☆</span>
                                                    <span class="star" data-rating="5">☆</span>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label style="display: block; margin: 10px 0 5px 0; font-weight: bold;">Comentario:</label>
                                                <textarea name="comentario" class="review-textarea" 
                                                          placeholder="Cuéntanos tu experiencia con este producto (mínimo 10 caracteres)..."
                                                          required></textarea>
                                            </div>
                                            
                                            <button type="button" class="review-btn" 
                                                    onclick="submitReview(<?php echo $producto['producto_id']; ?>, <?php echo $orden['id']; ?>)">
                                                Enviar Reseña
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="orden-total">
                        <div class="total-linea">
                            <span>Subtotal:</span>
                            <span>₡<?php echo number_format($orden['subtotal'], 2); ?></span>
                        </div>
                        <div class="total-linea">
                            <span>Envío:</span>
                            <span>₡<?php echo number_format($orden['envio'], 2); ?></span>
                        </div>
                        <div class="total-linea total-final">
                            <span>Total del Pedido:</span>
                            <span>₡<?php echo number_format($orden['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?php echo $pagina - 1; ?>">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="actual"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina + 1; ?>">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ✅ Script general JS -->
    <script src="../app.js"></script>
    <script>
        // Star rating functionality
        function initializeStarRating(containerId) {
            const container = document.getElementById(containerId);
            if (!container) {
                console.error('Container not found:', containerId);
                return;
            }
            
            const stars = container.querySelectorAll('.star');
            // Find the hidden input in the parent form
            const form = container.closest('form');
            const hiddenInput = form ? form.querySelector('.rating-input') : null;
            
            if (!hiddenInput) {
                console.error('Hidden input not found for container:', containerId);
                return;
            }
            
            console.log('Initializing stars for:', containerId, 'Found', stars.length, 'stars');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', (e) => {
                    e.preventDefault();
                    const rating = index + 1;
                    hiddenInput.value = rating;
                    
                    console.log('Star clicked:', rating, 'Hidden input value set to:', hiddenInput.value);
                    
                    // Update visual state
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.classList.add('active');
                            s.textContent = '★';
                            s.style.color = '#ffc107';
                        } else {
                            s.classList.remove('active');
                            s.textContent = '☆';
                            s.style.color = '#ddd';
                        }
                    });
                });
                
                // Hover effect
                star.addEventListener('mouseenter', () => {
                    stars.forEach((s, i) => {
                        if (i <= index) {
                            s.style.color = '#ffc107';
                            s.textContent = '★';
                        } else {
                            s.style.color = '#ddd';
                            s.textContent = '☆';
                        }
                    });
                });
            });
            
            // Reset on mouse leave
            container.addEventListener('mouseleave', () => {
                const currentRating = parseInt(hiddenInput.value) || 0;
                stars.forEach((s, i) => {
                    if (i < currentRating) {
                        s.style.color = '#ffc107';
                        s.classList.add('active');
                        s.textContent = '★';
                    } else {
                        s.style.color = '#ddd';
                        s.classList.remove('active');
                        s.textContent = '☆';
                    }
                });
            });
        }

        // Initialize all star ratings when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing star ratings...');
            
            // Find all star rating containers
            const ratingContainers = document.querySelectorAll('.stars-rating');
            console.log('Found', ratingContainers.length, 'rating containers');
            
            ratingContainers.forEach(container => {
                if (container.id) {
                    console.log('Initializing container:', container.id);
                    initializeStarRating(container.id);
                } else {
                    console.warn('Container without ID found:', container);
                }
            });
        });

        // Submit review function
        function submitReview(productoId, ordenId) {
            const formId = `review-form-${productoId}`;
            const form = document.getElementById(formId);
            
            if (!form) {
                console.error('Form not found:', formId);
                alert('Error: No se pudo encontrar el formulario.');
                return;
            }
            
            const formData = new FormData(form);
            
            // Debug: Log form data
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Validate rating
            const rating = formData.get('rating');
            console.log('Rating value:', rating);
            
            if (!rating || rating < 1 || rating > 5) {
                alert('Por favor selecciona una calificación de 1 a 5 estrellas.');
                return;
            }
            
            // Validate comment
            const comment = formData.get('comentario').trim();
            console.log('Comment:', comment);
            
            if (comment.length < 10) {
                alert('El comentario debe tener al menos 10 caracteres.');
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('.review-btn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            
            // Submit via fetch
            fetch('../controlador/procesarResena.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    // Replace form with success message
                    const reviewSection = document.getElementById(`review-section-${productoId}`);
                    reviewSection.innerHTML = `
                        <h5 style="margin: 0 0 10px 0; color: #333;">Tu reseña:</h5>
                        <div class="existing-review">
                            <div class="review-stars">${'★'.repeat(rating)}${'☆'.repeat(5-rating)}</div>
                            <div class="review-text">${comment}</div>
                            <div class="review-date">Reseña enviada</div>
                        </div>
                    `;
                } else {
                    alert(data.message || 'Error al enviar la reseña. Inténtalo de nuevo.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar la reseña. Inténtalo de nuevo.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }
    </script>
    
</body>
</html>
