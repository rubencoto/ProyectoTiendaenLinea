<?php
session_start();
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';

// Verificar que el usuario esté autenticado
if (empty($_SESSION['cliente_id'])) {
    header('Location: ../vista/loginCliente.php');
    exit;
}

// Verificar que hay productos en el carrito
if (empty($_SESSION['carrito'])) {
    header('Location: ../vista/carrito.php');
    exit;
}

try {
    // Obtener información del cliente
    $stmt = $conn->prepare("SELECT nombre, apellidos, correo FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['cliente_id']);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cliente) {
        throw new Exception("Cliente no encontrado");
    }

    // Obtener productos del carrito con información del vendedor
    $ids = array_keys($_SESSION['carrito']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $conn->prepare("
        SELECT p.id, p.nombre, p.precio, p.imagen_principal, v.nombre_empresa as vendedor_nombre, v.correo as vendedor_correo, p.id_vendedor
        FROM productos p 
        JOIN vendedores v ON p.id_vendedor = v.id 
        WHERE p.id IN ($placeholders)
    ");
    
    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $productos_comprados = [];
    $vendedores_notificar = [];
    $total = 0;
    
    while ($row = $resultado->fetch_assoc()) {
        $row['cantidad'] = $_SESSION['carrito'][$row['id']];
        $row['subtotal'] = $row['precio'] * $row['cantidad'];
        $productos_comprados[] = $row;
        $total += $row['subtotal'];
        
        // Agrupar productos por vendedor para las notificaciones
        if (!isset($vendedores_notificar[$row['id_vendedor']])) {
            $vendedores_notificar[$row['id_vendedor']] = [
                'nombre_empresa' => $row['vendedor_nombre'],
                'correo' => $row['vendedor_correo'],
                'productos' => []
            ];
        }
        $vendedores_notificar[$row['id_vendedor']]['productos'][] = $row;
    }
    $stmt->close();

    // Insertar orden en la base de datos (opcional - puedes crear una tabla 'ordenes' si quieres)
    $numero_orden = 'ORD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
    
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

    $envio = 2500;
    $total_final = $total + $envio;

    $mensaje_cliente = "
    <h2 style='color: #007185;'>Gracias por tu compra</h2>
    <p>Hola <strong>{$cliente['nombre']} {$cliente['apellidos']}</strong>,</p>
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
        <p><strong>Cliente:</strong> {$cliente['nombre']} {$cliente['apellidos']}</p>
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
    $_SESSION['carrito'] = [];

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
            <div class="success-icon">✅</div>
            <h1>¡Orden Confirmada!</h1>
            <p>Tu compra ha sido procesada exitosamente.</p>
            
            <div class="order-number">
                <strong>Numero de Orden:</strong> ' . $numero_orden . '
            </div>
            
            <p>Hemos enviado un correo de confirmacion a: <strong>' . htmlspecialchars($cliente['correo']) . '</strong></p>
            <p>Los vendedores han sido notificados de tu compra.</p>
            
            <hr style="margin: 30px 0;">
            
            <h3>📧 Estado de Notificaciones:</h3>
            <p>📬 Cliente: ' . ($envio_cliente ? '<span style="color: green;">✅ Enviado</span>' : '<span style="color: red;">❌ Error</span>') . '</p>
            <p>📬 Vendedores: ' . (array_sum($envios_vendedores) === count($envios_vendedores) ? '<span style="color: green;">✅ Todos notificados</span>' : '<span style="color: orange;">⚠️ Algunos errores</span>') . '</p>
            
            <div style="margin-top: 30px;">
                <a href="../vista/catalogo.php" class="btn">🛍️ Seguir Comprando</a>
                <a href="../vista/inicioCliente.php" class="btn">🏠 Ir al Inicio</a>
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
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-icon">❌</div>
            <h1>Error al Procesar la Orden</h1>
            <p>Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.</p>
            <a href="../vista/carrito.php" class="btn">🛒 Volver al Carrito</a>
        </div>
    </body>
    </html>';
}

$conn->close();
?>
