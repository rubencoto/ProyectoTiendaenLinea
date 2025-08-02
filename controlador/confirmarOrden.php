<?php
session_start();
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';
require_once '../modelo/carritoPersistente.php';

// Verificar que el usuario esté autenticado
if (empty($_SESSION['cliente_id'])) {
    header('Location: ../vista/index.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$carritoPersistente = new CarritoPersistente();

// Verificar que hay productos en el carrito
$productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
if (empty($productos_carrito)) {
    header('Location: ../vista/carrito.php');
    exit;
}

try {
    // Debug: Log start of order processing
    error_log("ConfirmarOrden: Starting order processing for cliente_id: $cliente_id");
    
    // Obtener información del cliente
    $stmt = $conn->prepare("SELECT nombre, apellido, correo, direccion, telefono FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        throw new Exception("Cliente no encontrado");
    }
    
    error_log("ConfirmarOrden: Cliente found: " . $cliente['nombre'] . " " . $cliente['apellido']);

    // Los productos del carrito ya están cargados desde la base de datos
    $productos_comprados = [];
    $vendedores_notificar = [];
    $total = 0;
    
    foreach ($productos_carrito as $producto) {
        $producto['subtotal'] = $producto['precio'] * $producto['cantidad'];
        $productos_comprados[] = $producto;
        $total += $producto['subtotal'];
        
        // Obtener información del vendedor para notificaciones
        $stmt_vendedor = $conn->prepare("SELECT nombre_empresa, correo FROM vendedores WHERE id = ?");
        $stmt_vendedor->execute([$producto['id_vendedor']]);
        $vendedor = $stmt_vendedor->fetch();
        
        // Agrupar productos por vendedor para las notificaciones
        if (!isset($vendedores_notificar[$producto['id_vendedor']])) {
            $vendedores_notificar[$producto['id_vendedor']] = [
                'nombre_empresa' => $vendedor['nombre_empresa'],
                'correo' => $vendedor['correo'],
                'productos' => []
            ];
        }
        $vendedores_notificar[$producto['id_vendedor']]['productos'][] = $producto;
    }
    $stmt->close();

    // Generar número de orden único
    $numero_orden = 'ORD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
    
    // Verificar que el número de orden sea único
    do {
        $stmt_check = $conn->prepare("SELECT id FROM ordenes WHERE numero_orden = ?");
        $stmt_check->bind_param("s", $numero_orden);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $numero_orden = 'ORD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
        }
        $stmt_check->close();
    } while ($result_check->num_rows > 0);
    
    // Calcular costos
    $envio = 2500;
    $total_final = $total + $envio;
    
    // Get the actual PDO connection for transactions
    $db_instance = DatabaseConnection::getInstance();
    $pdo_conn = $db_instance->getConnection();
    
    // Iniciar transacción para garantizar consistencia
    $pdo_conn->beginTransaction();
    
    try {
        // Insertar orden en la tabla pedidos (not ordenes)
        $stmt_orden = $pdo_conn->prepare("
            INSERT INTO pedidos (cliente_id, total, estado, fecha_pedido, direccion_envio, telefono_contacto) 
            VALUES (?, ?, 'pendiente', NOW(), ?, ?)
        ");
        
        // Get client contact info for the order
        $direccion_envio = $cliente['direccion'] ?? 'Por definir';
        $telefono_contacto = $cliente['telefono'] ?? 'Por definir';
        
        $stmt_orden->execute([$cliente_id, $total_final, $direccion_envio, $telefono_contacto]);
        $orden_id = $pdo_conn->lastInsertId();
        
        error_log("ConfirmarOrden: Order created with ID: $orden_id");
        
        // Insertar detalles de la orden en detalle_pedidos
        $stmt_detalle = $pdo_conn->prepare("
            INSERT INTO detalle_pedidos (orden_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($productos_comprados as $producto) {
            $stmt_detalle->execute([$orden_id, $producto['producto_id'], $producto['cantidad'], $producto['precio'], $producto['subtotal']]);
        }
        
        // Actualizar stock de productos
        $stmt_stock = $pdo_conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        foreach ($productos_comprados as $producto) {
            $stmt_stock->execute([$producto['cantidad'], $producto['producto_id']]);
        }
        
        error_log("ConfirmarOrden: Stock updated for " . count($productos_comprados) . " products");
        
        // Confirmar transacción
        $pdo_conn->commit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo_conn->rollBack();
        throw new Exception("Error al guardar la orden en la base de datos: " . $e->getMessage());
    }
    
    // Enviar correo de confirmación al cliente
    $productos_lista = '';
    foreach ($productos_comprados as $producto) {
        $productos_lista .= "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$producto['nombre']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>{$producto['cantidad']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>CRC " . number_format($producto['precio'], 0, ',', '.') . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>CRC " . number_format($producto['subtotal'], 0, ',', '.') . "</td>
        </tr>";
    }

    // El envío y total final ya se calcularon antes de la inserción
    $mensaje_cliente = "
    <h2 style='color: #007185;'>Gracias por tu compra</h2>
    <p>Hola <strong>{$cliente['nombre']} {$cliente['apellido']}</strong>,</p>
    <p>Tu orden ha sido confirmada exitosamente. A continuacion, los detalles de tu compra:</p>
    
    <h3>Detalles de la Orden</h3>
    <p><strong>Numero de Orden:</strong> {$numero_orden}</p>
    <p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
    
    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
        <thead>
            <tr style='background-color: #f8f9fa;'>
                <th style='padding: 12px; border-bottom: 2px solid #007185; text-align: left;'>Producto</th>
                <th style='padding: 12px; border-bottom: 2px solid #007185; text-align: center;'>Cantidad</th>
                <th style='padding: 12px; border-bottom: 2px solid #007185; text-align: right;'>Precio Unit.</th>
                <th style='padding: 12px; border-bottom: 2px solid #007185; text-align: right;'>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            {$productos_lista}
        </tbody>
        <tfoot>
            <tr>
                <td colspan='3' style='padding: 10px; text-align: right; font-weight: bold;'>Subtotal:</td>
                <td style='padding: 10px; text-align: right; font-weight: bold;'>CRC " . number_format($total, 0, ',', '.') . "</td>
            </tr>
            <tr>
                <td colspan='3' style='padding: 10px; text-align: right; font-weight: bold;'>Envio:</td>
                <td style='padding: 10px; text-align: right; font-weight: bold;'>CRC " . number_format($envio, 0, ',', '.') . "</td>
            </tr>
            <tr style='background-color: #f8f9fa;'>
                <td colspan='3' style='padding: 15px; text-align: right; font-weight: bold; font-size: 18px; color: #007185;'>TOTAL:</td>
                <td style='padding: 15px; text-align: right; font-weight: bold; font-size: 18px; color: #007185;'>CRC " . number_format($total_final, 0, ',', '.') . "</td>
            </tr>
        </tfoot>
    </table>
    
    <h3>Proximos Pasos</h3>
    <ul>
        <li>Los vendedores han sido notificados de tu compra</li>
        <li>Recibiras actualizaciones sobre el estado de tu pedido</li>
        <li>El tiempo estimado de entrega es de 3-5 dias habiles</li>
    </ul>
    
    <p style='margin-top: 30px;'>Gracias por confiar en nosotros!</p>
    <p><strong>Equipo de Tienda en Linea</strong></p>
    ";

    $asunto_cliente = "Confirmacion de Orden #{$numero_orden}";
    $envio_cliente = enviarCorreo($cliente['correo'], $asunto_cliente, $mensaje_cliente);

    // Enviar correos a los vendedores
    $envios_vendedores = [];
    foreach ($vendedores_notificar as $vendedor) {
        $productos_vendedor = '';
        foreach ($vendedor['productos'] as $producto) {
            $productos_vendedor .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$producto['nombre']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>{$producto['cantidad']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>CRC " . number_format($producto['precio'], 0, ',', '.') . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>CRC " . number_format($producto['subtotal'], 0, ',', '.') . "</td>
            </tr>";
        }

        $mensaje_vendedor = "
        <h2 style='color: #28a745;'>Nueva Venta Realizada</h2>
        <p>Hola <strong>{$vendedor['nombre_empresa']}</strong>,</p>
        <p>Te informamos que se ha realizado una nueva compra de tus productos:</p>
        
        <h3>Detalles de la Venta</h3>
        <p><strong>Numero de Orden:</strong> {$numero_orden}</p>
        <p><strong>Cliente:</strong> {$cliente['nombre']} {$cliente['apellido']}</p>
        <p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
        
        <h3>Productos Vendidos</h3>
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <thead>
                <tr style='background-color: #f8f9fa;'>
                    <th style='padding: 12px; border-bottom: 2px solid #28a745; text-align: left;'>Producto</th>
                    <th style='padding: 12px; border-bottom: 2px solid #28a745; text-align: center;'>Cantidad</th>
                    <th style='padding: 12px; border-bottom: 2px solid #28a745; text-align: right;'>Precio Unit.</th>
                    <th style='padding: 12px; border-bottom: 2px solid #28a745; text-align: right;'>Total</th>
                </tr>
            </thead>
            <tbody>
                {$productos_vendedor}
            </tbody>
        </table>
        
        <h3>Proximos Pasos</h3>
        <ul>
            <li>Prepara los productos para envio</li>
            <li>Coordina la entrega con el cliente si es necesario</li>
            <li>Manten actualizado el estado del pedido</li>
        </ul>
        
        <p style='margin-top: 30px;'>Felicitaciones por tu venta</p>
        <p><strong>Equipo de Tienda en Linea</strong></p>
        ";

        $asunto_vendedor = "Nueva Venta - Orden #{$numero_orden}";
        $envios_vendedores[] = enviarCorreo($vendedor['correo'], $asunto_vendedor, $mensaje_vendedor);
    }

    // Limpiar el carrito después del envío exitoso
    $carritoPersistente->vaciarCarrito($cliente_id);

    // Mostrar página de confirmación
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Orden Confirmada</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            .success-icon {
                font-size: 4em;
                color: #28a745;
                margin-bottom: 20px;
            }
            .order-number {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                font-family: monospace;
                font-size: 1.2em;
            }
            .btn {
                background-color: #007185;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 5px;
                display: inline-block;
                margin: 10px;
            }
            .btn:hover {
                background-color: #005d6b;
                color: white;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success-icon">✓</div>
            <h1>¡Orden Confirmada!</h1>
            <p>Tu compra ha sido procesada exitosamente.</p>
            
            <div class="order-number">
                <strong>Numero de Orden:</strong> ' . $numero_orden . '<br>
                <strong>ID de Orden:</strong> #' . $orden_id . '
            </div>
            
            <p>✅ <strong>Orden guardada exitosamente en la base de datos</strong></p>
            <p>Hemos enviado un correo de confirmacion a: <strong>' . htmlspecialchars($cliente['correo']) . '</strong></p>
            <p>Los vendedores han sido notificados de tu compra.</p>
            
            <hr style="margin: 30px 0;">
            
            <h3>Estado de Notificaciones:</h3>
            <p>Cliente: ' . ($envio_cliente ? '<span style="color: green;">Enviado</span>' : '<span style="color: red;">Error</span>') . '</p>
            <p>Vendedores: ' . (array_sum($envios_vendedores) === count($envios_vendedores) ? '<span style="color: green;">Todos notificados</span>' : '<span style="color: orange;">Algunos errores</span>') . '</p>
            
            <div style="margin-top: 30px;">
                <a href="../vista/catalogo.php" class="btn">Seguir Comprando</a>
                <a href="../vista/misPedidos.php" class="btn">Ver Mis Pedidos</a>
                <a href="../vista/index.php.php" class="btn">Ir al Inicio</a>
            </div>
        </div>
    </body>
    </html>';

} catch (Exception $e) {
    error_log("Error en confirmación de orden: " . $e->getMessage());
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error en la Orden</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; text-align: center; }
            .error-icon { font-size: 4em; color: #dc3545; margin-bottom: 20px; }
            .btn { background-color: #007185; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; }
            .error-details { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-icon">✗</div>
            <h1>Error al Procesar la Orden</h1>
            <p>Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.</p>
            <div class="error-details">
                <strong>Debug Error:</strong><br>
                ' . htmlspecialchars($e->getMessage()) . '
            </div>
            <a href="../vista/carrito.php" class="btn">Volver al Carrito</a>
        </div>
    </body>
    </html>';
}

// Connection managed by singleton, no need to close explicitly
?>
