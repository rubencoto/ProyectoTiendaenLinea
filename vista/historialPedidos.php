<?php
session_start();

// Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/conexion.php';

$cliente_id = $_SESSION['cliente_id'];

// Obtener información del cliente
$stmt = $conn->prepare("SELECT nombre, apellidos FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    $nombre_completo = $cliente['nombre'] . ' ' . $cliente['apellidos'];
} else {
    $nombre_completo = 'Cliente';
}
$stmt->close();

// Crear tabla de ordenes si no existe
$crear_tabla_ordenes = "
CREATE TABLE IF NOT EXISTS ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_orden VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    envio DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    fecha_orden DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
)";

$conn->query($crear_tabla_ordenes);

// Crear tabla de detalles de ordenes si no existe
$crear_tabla_detalles = "
CREATE TABLE IF NOT EXISTS orden_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
)";

$conn->query($crear_tabla_detalles);

// Consultar historial de pedidos del cliente
$stmt = $conn->prepare("
    SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, o.estado, o.fecha_orden,
           COUNT(od.id) as total_productos
    FROM ordenes o
    LEFT JOIN orden_detalles od ON o.id = od.orden_id
    WHERE o.cliente_id = ?
    GROUP BY o.id
    ORDER BY o.fecha_orden DESC
");

$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$ordenes = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pedidos</title>
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
            max-width: 1200px;
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
        }

        .navigation a:hover {
            background-color: #007185;
            color: white;
        }

        .pedidos-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .pedidos-header {
            background-color: #007185;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .no-pedidos {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .pedido-card {
            border-bottom: 1px solid #eee;
            padding: 20px;
            transition: background-color 0.2s;
        }

        .pedido-card:hover {
            background-color: #f9f9f9;
        }

        .pedido-card:last-child {
            border-bottom: none;
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .numero-orden {
            font-weight: bold;
            color: #007185;
            font-size: 18px;
        }

        .estado {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
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

        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: bold;
            color: #333;
        }

        .ver-detalles {
            margin-top: 15px;
            text-align: right;
        }

        .ver-detalles a {
            color: #007185;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 16px;
            border: 1px solid #007185;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .ver-detalles a:hover {
            background-color: #007185;
            color: white;
        }

        @media (max-width: 768px) {
            .pedido-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .pedido-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Historial de Pedidos</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($nombre_completo); ?></p>
    </div>

    <div class="container">
        <div class="navigation">
            <a href="inicioCliente.php">← Volver al Panel</a>
        </div>

        <div class="pedidos-container">
            <div class="pedidos-header">
                <h2>Mis Pedidos</h2>
            </div>

            <?php if ($ordenes->num_rows == 0): ?>
                <div class="no-pedidos">
                    <h3>No tienes pedidos aún</h3>
                    <p>Cuando realices tu primera compra, aparecerá aquí.</p>
                    <a href="catalogo.php" style="color: #007185; text-decoration: none; font-weight: bold;">Ver Productos</a>
                </div>
            <?php else: ?>
                <?php while ($orden = $ordenes->fetch_assoc()): ?>
                    <div class="pedido-card">
                        <div class="pedido-header">
                            <div class="numero-orden">
                                Orden #<?php echo htmlspecialchars($orden['numero_orden']); ?>
                            </div>
                            <div class="estado <?php echo $orden['estado']; ?>">
                                <?php echo ucfirst($orden['estado']); ?>
                            </div>
                        </div>

                        <div class="pedido-info">
                            <div class="info-item">
                                <div class="info-label">Fecha del Pedido</div>
                                <div class="info-value">
                                    <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total de Productos</div>
                                <div class="info-value">
                                    <?php echo $orden['total_productos']; ?> producto(s)
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Subtotal</div>
                                <div class="info-value">
                                    CRC <?php echo number_format($orden['subtotal'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Envío</div>
                                <div class="info-value">
                                    CRC <?php echo number_format($orden['envio'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total</div>
                                <div class="info-value">
                                    CRC <?php echo number_format($orden['total'], 0, ',', '.'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="ver-detalles">
                            <a href="detallesPedido.php?orden_id=<?php echo $orden['id']; ?>">Ver Detalles</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
