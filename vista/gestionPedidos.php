<?php
session_start();
require_once '../modelo/config.php';
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';

// Verify if vendor is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ' . AppConfig::vistaUrl('loginVendedor.php'));
    exit;
}

$vendedor_id = $_SESSION['id'];

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Get vendor information
$stmt_vendor = $conn->prepare("SELECT nombre_empresa FROM vendedores WHERE id = ?");
$stmt_vendor->execute([$vendedor_id]);
$vendedor = $stmt_vendor->fetch();
$nombre_empresa = $vendedor ? $vendedor['nombre_empresa'] : 'Vendedor';

// Handle status updates
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
    $orden_id = intval($_POST['orden_id']);
    $nuevo_estado = $_POST['nuevo_estado'];
    
    // Validate status values
    $estados_validos = ['pendiente', 'cancelado', 'en transito', 'entregado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        // Verify that the order contains products from this vendor and get customer info
        $stmt_verify = $conn->prepare("
            SELECT COUNT(*) as count, 
                   c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.correo as cliente_correo,
                   o.numero_orden, v.nombre_empresa
            FROM pedidos o
            JOIN clientes c ON o.cliente_id = c.id
            JOIN vendedores v ON v.id = ?
            JOIN detalle_pedidos dp ON o.id = dp.orden_id 
            JOIN productos p ON dp.producto_id = p.id 
            WHERE o.id = ? AND p.id_vendedor = ?
            GROUP BY c.nombre, c.apellido, c.correo, o.numero_orden, v.nombre_empresa
        ");
        $stmt_verify->execute([$vendedor_id, $orden_id, $vendedor_id]);
        $verification = $stmt_verify->fetch();
        
        if ($verification && $verification['count'] > 0) {
            $stmt_update = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            if ($stmt_update->execute([$nuevo_estado, $orden_id])) {
                // Send email notification to customer
                $nombreCompleto = $verification['cliente_nombre'] . ' ' . $verification['cliente_apellido'];
                $envioExitoso = enviarNotificacionCambioEstado(
                    $verification['cliente_correo'],
                    $nombreCompleto,
                    $verification['numero_orden'],
                    $nuevo_estado,
                    $verification['nombre_empresa']
                );
                
                if ($envioExitoso) {
                    $mensaje = "Estado de la orden actualizado correctamente y se ha enviado una notificación al cliente.";
                } else {
                    $mensaje = "Estado de la orden actualizado correctamente, pero no se pudo enviar la notificación por correo.";
                }
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar el estado de la orden.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No tienes permisos para modificar esta orden.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Estado no válido.";
        $tipo_mensaje = "error";
    }
}

// Pagination
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Count total orders for this vendor
$stmt_count = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) as total 
    FROM pedidos o
    JOIN detalle_pedidos dp ON o.id = dp.orden_id 
    JOIN productos p ON dp.producto_id = p.id 
    WHERE p.id_vendedor = ?
");
$stmt_count->execute([$vendedor_id]);
$count_result = $stmt_count->fetch();
$total_ordenes = $count_result['total'];
$total_paginas = ceil($total_ordenes / $limite);

// Get orders with products from this vendor
$stmt_ordenes = $conn->prepare("
    SELECT DISTINCT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
           o.estado, o.fecha_orden, c.nombre as cliente_nombre, c.apellido as cliente_apellido,
           c.correo as cliente_correo, c.telefono as cliente_telefono
    FROM pedidos o
    JOIN clientes c ON o.cliente_id = c.id
    JOIN detalle_pedidos dp ON o.id = dp.orden_id 
    JOIN productos p ON dp.producto_id = p.id 
    WHERE p.id_vendedor = ?
    ORDER BY o.fecha_orden DESC 
    LIMIT $limite OFFSET $offset
");
$stmt_ordenes->execute([$vendedor_id]);

$ordenes = [];
while ($row = $stmt_ordenes->fetch()) {
    $ordenes[] = $row;
}

// Get order details for each order (only vendor's products)
foreach ($ordenes as &$orden) {
    $stmt_detalle = $conn->prepare("
        SELECT dp.cantidad, dp.precio_unitario, dp.subtotal,
               p.nombre as producto_nombre, p.imagen_principal
        FROM detalle_pedidos dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.orden_id = ? AND p.id_vendedor = ?
    ");
    $stmt_detalle->execute([$orden['id'], $vendedor_id]);
    
    $productos = [];
    while ($row_detalle = $stmt_detalle->fetch()) {
        if ($row_detalle['imagen_principal']) {
            $row_detalle['imagen_principal'] = base64_encode($row_detalle['imagen_principal']);
        }
        $productos[] = $row_detalle;
    }
    $orden['productos'] = $productos;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - <?= htmlspecialchars($nombre_empresa) ?></title>
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

        .mensaje {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        .estado.en.transito { background: #cce5ff; color: #004085; }
        .estado.entregado { background: #d4edda; color: #155724; }
        .estado.cancelado { background: #f8d7da; color: #721c24; }

        .estado-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .estado-form select {
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
        }

        .estado-form button {
            background: #007185;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .estado-form button:hover {
            background: #005a6b;
        }

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
            background: #f0f0f0;
            flex-shrink: 0;
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
            font-size: 16px;
            font-weight: bold;
            color: #007185;
            text-align: right;
        }

        .orden-total {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
        }

        .total-linea {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #007185;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            margin-top: 10px;
        }

        .cliente-info {
            background: #e9ecef;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .cliente-info strong {
            color: #495057;
        }

        .mensaje-vacio {
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .paginacion {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 10px;
        }

        .paginacion a, .paginacion span {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007185;
        }

        .paginacion a:hover {
            background: #007185;
            color: white;
        }

        .paginacion .current {
            background: #007185;
            color: white;
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
        <h1>Gestión de Pedidos - <?= htmlspecialchars($nombre_empresa) ?></h1>
    </div>

    <div class="container">
        <div class="navegacion">
            <a href="inicioVendedor.php">← Volver al Panel</a>
            <a href="productos.php">Mis Productos</a>
            <a href="agregarproducto.php">Agregar Producto</a>
            <a href="testEmailNotifications.php" style="color: #28a745;">📧 Probar Notificaciones</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= $tipo_mensaje ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <?php if (empty($ordenes)): ?>
            <div class="mensaje-vacio">
                <h2>No tienes pedidos aún</h2>
                <p>Cuando los clientes compren tus productos, aparecerán aquí para que puedas gestionar su estado.</p>
                <a href="productos.php" style="color: #007185; text-decoration: none; font-weight: bold;">
                    Ver Mis Productos
                </a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 20px; color: #666;">
                <p>Mostrando <?= count($ordenes) ?> de <?= $total_ordenes ?> pedidos con tus productos</p>
            </div>

            <?php foreach ($ordenes as $orden): ?>
                <div class="orden-card">
                    <div class="orden-header">
                        <div class="orden-info">
                            <strong>Número de Orden</strong>
                            <span><?= htmlspecialchars($orden['numero_orden']) ?></span>
                        </div>
                        <div class="orden-info">
                            <strong>Fecha del Pedido</strong>
                            <span><?= date('d/m/Y H:i', strtotime($orden['fecha_orden'])) ?></span>
                        </div>
                        <div class="orden-info">
                            <strong>Estado Actual</strong>
                            <span class="estado <?= str_replace(' ', '.', $orden['estado']) ?>">
                                <?= ucfirst($orden['estado']) ?>
                            </span>
                            <?php if ($orden['estado'] === 'entregado'): ?>
                                <div style="margin-top: 10px; padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">
                                    <strong>✅ Orden completada</strong><br>
                                    <small>Este pedido ya ha sido entregado y no se puede modificar</small>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="estado-form" id="form-<?= $orden['id'] ?>" onsubmit="return confirmarActualizacion(event, '<?= $orden['id'] ?>')">
                                    <input type="hidden" name="accion" value="actualizar_estado">
                                    <input type="hidden" name="orden_id" value="<?= $orden['id'] ?>">
                                    <select name="nuevo_estado" required id="select-<?= $orden['id'] ?>">
                                        <option value="">Cambiar estado...</option>
                                        <option value="pendiente" <?= $orden['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="en transito" <?= $orden['estado'] === 'en transito' ? 'selected' : '' ?>>En Tránsito</option>
                                        <option value="entregado" <?= $orden['estado'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                        <option value="cancelado" <?= $orden['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                    <button type="submit" title="Se enviará notificación por correo al cliente">📧 Actualizar</button>
                                </form>
                                <small style="color: #666; font-size: 11px; margin-top: 5px; display: block;">
                                    📧 El cliente recibirá una notificación por correo
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="orden-info">
                            <strong>Total de la Orden</strong>
                            <span style="font-size: 18px; font-weight: bold; color: #007185;">
                                ₡<?= number_format($orden['total'], 2) ?>
                            </span>
                        </div>
                    </div>

                    <div class="cliente-info">
                        <strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre'] . ' ' . $orden['cliente_apellido']) ?> |
                        <strong>Email:</strong> <?= htmlspecialchars($orden['cliente_correo']) ?> |
                        <strong>Teléfono:</strong> <?= htmlspecialchars($orden['cliente_telefono']) ?>
                    </div>

                    <div class="productos-lista">
                        <h4 style="margin-top: 0; color: #333;">Tus productos en este pedido:</h4>
                        <?php foreach ($orden['productos'] as $producto): ?>
                            <div class="producto-item">
                                <?php if (!empty($producto['imagen_principal'])): ?>
                                    <img src="data:image/jpeg;base64,<?= $producto['imagen_principal'] ?>" 
                                         alt="<?= htmlspecialchars($producto['producto_nombre']) ?>" 
                                         class="producto-imagen">
                                <?php else: ?>
                                    <div class="producto-imagen" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                        Sin imagen
                                    </div>
                                <?php endif; ?>
                                
                                <div class="producto-info">
                                    <div class="producto-nombre">
                                        <?= htmlspecialchars($producto['producto_nombre']) ?>
                                    </div>
                                    <div class="producto-detalles">
                                        Cantidad: <?= $producto['cantidad'] ?> | 
                                        Precio unitario: ₡<?= number_format($producto['precio_unitario'], 2) ?>
                                    </div>
                                </div>
                                
                                <div class="producto-precio">
                                    ₡<?= number_format($producto['subtotal'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?= $pagina - 1 ?>">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?>">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

<script>
function confirmarActualizacion(event, ordenId) {
    const selectElement = document.getElementById('select-' + ordenId);
    const nuevoEstado = selectElement.value;
    
    if (nuevoEstado === 'entregado') {
        const confirmacion = confirm(
            '⚠️ CONFIRMACIÓN REQUERIDA\n\n' +
            '¿Está seguro que desea marcar esta orden como ENTREGADA?\n\n' +
            '⚠️ IMPORTANTE: Una vez marcada como entregada, NO PODRÁ cambiar el estado nuevamente.\n\n' +
            'Esta acción enviará una notificación por correo al cliente.\n\n' +
            '¿Desea continuar?'
        );
        
        if (!confirmacion) {
            event.preventDefault();
            return false;
        }
    }
    
    return true;
}
</script>

</body>
</html>
