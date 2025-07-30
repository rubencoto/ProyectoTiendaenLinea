<?php
session_start(); // 🔐 Iniciar sesión

// 🚫 Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

// 📋 Obtener información del cliente
require_once '../modelo/conexion.php';
$cliente_id = $_SESSION['cliente_id'];

// Obtener información del cliente
$stmt = $conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    $nombre_completo = $cliente['nombre'] . ' ' . $cliente['apellido'];
} else {
    $nombre_completo = 'Cliente';
}
$stmt->close();

// Obtener las órdenes del cliente con paginación
$limite = 10; // Número de órdenes por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Contar total de órdenes
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM ordenes WHERE cliente_id = ?");
$stmt_count->bind_param("i", $cliente_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_ordenes = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_ordenes / $limite);
$stmt_count->close();

// Obtener órdenes del cliente
$stmt_ordenes = $conn->prepare("
    SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
           o.estado, o.fecha_orden
    FROM ordenes o 
    WHERE o.cliente_id = ? 
    ORDER BY o.fecha_orden DESC 
    LIMIT ? OFFSET ?
");
$stmt_ordenes->bind_param("iii", $cliente_id, $limite, $offset);
$stmt_ordenes->execute();
$result_ordenes = $stmt_ordenes->get_result();

$ordenes = [];
while ($row = $result_ordenes->fetch_assoc()) {
    $ordenes[] = $row;
}
$stmt_ordenes->close();

// Para cada orden, obtener los productos
foreach ($ordenes as &$orden) {
    $stmt_detalle = $conn->prepare("
        SELECT dp.cantidad, dp.precio_unitario, dp.subtotal,
               p.nombre as producto_nombre, p.imagen_principal
        FROM detalle_pedidos dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.orden_id = ?
    ");
    $stmt_detalle->bind_param("i", $orden['id']);
    $stmt_detalle->execute();
    $result_detalle = $stmt_detalle->get_result();
    
    $productos = [];
    while ($row_detalle = $result_detalle->fetch_assoc()) {
        if ($row_detalle['imagen_principal']) {
            $row_detalle['imagen_principal'] = base64_encode($row_detalle['imagen_principal']);
        }
        $productos[] = $row_detalle;
    }
    $orden['productos'] = $productos;
    $stmt_detalle->close();
}

$conn->close();
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
                                $<?php echo number_format($orden['total'], 2); ?>
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
                                        Precio unitario: $<?php echo number_format($producto['precio_unitario'], 2); ?>
                                    </div>
                                </div>
                                
                                <div class="producto-precio">
                                    $<?php echo number_format($producto['subtotal'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="orden-total">
                        <div class="total-linea">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($orden['subtotal'], 2); ?></span>
                        </div>
                        <div class="total-linea">
                            <span>Envío:</span>
                            <span>$<?php echo number_format($orden['envio'], 2); ?></span>
                        </div>
                        <div class="total-linea total-final">
                            <span>Total:</span>
                            <span>$<?php echo number_format($orden['total'], 2); ?></span>
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
    
</body>
</html>
