<?php
session_start();

// Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

// Verificar que se haya proporcionado el ID de la orden
if (!isset($_GET['orden_id']) || !is_numeric($_GET['orden_id'])) {
    header('Location: historialPedidos.php');
    exit;
}

require_once '../modelo/conexion.php';

$cliente_id = $_SESSION['cliente_id'];
$orden_id = intval($_GET['orden_id']);

// Verificar que la orden pertenece al cliente
$stmt = $conn->prepare("
    SELECT o.*, c.nombre, c.apellidos 
    FROM ordenes o 
    JOIN clientes c ON o.cliente_id = c.id 
    WHERE o.id = ? AND o.cliente_id = ?
");
$stmt->bind_param("ii", $orden_id, $cliente_id);
$stmt->execute();
$orden_result = $stmt->get_result();

if ($orden_result->num_rows == 0) {
    header('Location: historialPedidos.php');
    exit;
}

$orden = $orden_result->fetch_assoc();
$stmt->close();

// Obtener los detalles de los productos de la orden
$stmt = $conn->prepare("
    SELECT od.*, p.nombre, p.descripcion, p.imagen_principal, v.nombre_empresa as vendedor
    FROM orden_detalles od
    JOIN productos p ON od.producto_id = p.id
    JOIN vendedores v ON p.id_vendedor = v.id
    WHERE od.orden_id = ?
    ORDER BY od.id
");
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$detalles = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido #<?php echo htmlspecialchars($orden['numero_orden']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f2f2f2;
        }

        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .navigation {
            margin-bottom: 20px;
        }

        .navigation a {
            color: #007185;
            text-decoration: none;
            padding: 8px 16px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #007185;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .navigation a:hover {
            background-color: #007185;
            color: white;
        }

        .orden-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .orden-header {
            background-color: #007185;
            color: white;
            padding: 20px;
        }

        .orden-info {
            padding: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .estado {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        .estado.pendiente {
            background-color: #fff3cd;
            color: #856404;
        }

        .estado.procesando {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .estado.enviado {
            background-color: #d4edda;
            color: #155724;
        }

        .estado.entregado {
            background-color: #d4edda;
            color: #155724;
        }

        .estado.cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .productos-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .productos-header {
            background-color: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .producto-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .producto-imagen {
            flex-shrink: 0;
        }

        .producto-imagen img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .producto-info {
            flex-grow: 1;
        }

        .producto-nombre {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }

        .producto-vendedor {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .producto-descripcion {
            color: #888;
            font-size: 14px;
            line-height: 1.4;
        }

        .producto-precio {
            text-align: right;
            flex-shrink: 0;
        }

        .cantidad {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .precio-unitario {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .subtotal {
            font-weight: bold;
            font-size: 16px;
            color: #007185;
        }

        .resumen-total {
            background-color: #f8f9fa;
            padding: 20px;
            border-top: 2px solid #007185;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .total-row.final {
            border-top: 2px solid #007185;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #007185;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .producto-item {
                flex-direction: column;
                text-align: center;
            }

            .producto-precio {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detalles del Pedido</h1>
        <p><?php echo htmlspecialchars($orden['nombre'] . ' ' . $orden['apellidos']); ?></p>
    </div>

    <div class="container">
        <div class="navigation">
            <a href="historialPedidos.php">← Volver al Historial</a>
            <a href="inicioCliente.php">Menu Principal</a>
        </div>

        <!-- Información de la Orden -->
        <div class="orden-container">
            <div class="orden-header">
                <h2>Orden #<?php echo htmlspecialchars($orden['numero_orden']); ?></h2>
            </div>
            
            <div class="orden-info">
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Fecha del Pedido</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i:s', strtotime($orden['fecha_orden'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">Estado del Pedido</div>
                        <div class="info-value">
                            <span class="estado <?php echo $orden['estado']; ?>">
                                <?php echo ucfirst($orden['estado']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">Total de Productos</div>
                        <div class="info-value">
                            <?php echo $detalles->num_rows; ?> producto(s)
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">Total del Pedido</div>
                        <div class="info-value">
                            CRC <?php echo number_format($orden['total'], 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Productos -->
        <div class="productos-container">
            <div class="productos-header">
                <h3>Productos del Pedido</h3>
            </div>

            <?php while ($detalle = $detalles->fetch_assoc()): ?>
                <div class="producto-item">
                    <div class="producto-imagen">
                        <?php if (!empty($detalle['imagen_principal'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($detalle['imagen_principal']); ?>" 
                                 alt="<?php echo htmlspecialchars($detalle['nombre']); ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/80x80/f0f0f0/666666?text=Sin+Imagen" 
                                 alt="Sin imagen">
                        <?php endif; ?>
                    </div>
                    
                    <div class="producto-info">
                        <div class="producto-nombre">
                            <?php echo htmlspecialchars($detalle['nombre']); ?>
                        </div>
                        <div class="producto-vendedor">
                            Vendido por: <?php echo htmlspecialchars($detalle['vendedor']); ?>
                        </div>
                        <?php if (!empty($detalle['descripcion'])): ?>
                            <div class="producto-descripcion">
                                <?php echo htmlspecialchars(substr($detalle['descripcion'], 0, 100)); ?>
                                <?php if (strlen($detalle['descripcion']) > 100) echo '...'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="producto-precio">
                        <div class="cantidad">
                            Cantidad: <?php echo $detalle['cantidad']; ?>
                        </div>
                        <div class="precio-unitario">
                            Precio: CRC <?php echo number_format($detalle['precio_unitario'], 0, ',', '.'); ?>
                        </div>
                        <div class="subtotal">
                            CRC <?php echo number_format($detalle['subtotal'], 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Resumen Total -->
            <div class="resumen-total">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>CRC <?php echo number_format($orden['subtotal'], 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span>Envío:</span>
                    <span>CRC <?php echo number_format($orden['envio'], 0, ',', '.'); ?></span>
                </div>
                <div class="total-row final">
                    <span>TOTAL:</span>
                    <span>CRC <?php echo number_format($orden['total'], 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
