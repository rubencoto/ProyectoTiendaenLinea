<?php
/**
 * Controlador para gestión de carrito de compras
 * Maneja operaciones CRUD del carrito mediante AJAX
 */

session_start();

// Verificar autenticación del cliente
if (empty($_SESSION['cliente_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Sesión expirada. Por favor inicia sesión nuevamente.',
        'redirect' => 'loginCliente.php'
    ]);
    exit;
}

require_once '../modelo/conexion.php';
require_once '../modelo/carritoPersistente.php';

$cliente_id = $_SESSION['cliente_id'];
$carritoPersistente = new CarritoPersistente();

// Solo permitir métodos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Migrar carrito de sesión a base de datos si existe
    if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
        $carritoPersistente->sincronizarConSesion($cliente_id, $_SESSION['carrito']);
        unset($_SESSION['carrito']); // Limpiar sesión después de migrar
    }
    
    $accion = $_POST['accion'] ?? '';
    $producto_id = intval($_POST['producto_id'] ?? 0);
    
    // Validar producto_id para acciones que lo requieren
    if (in_array($accion, ['agregar', 'actualizar', 'eliminar']) && $producto_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'ID de producto inválido'
        ]);
        exit;
    }
    
    switch ($accion) {
        case 'agregar':
            // Verificar que el producto existe
            $stmt_producto = $conn->prepare("SELECT id, nombre, precio FROM productos WHERE id = ? AND disponible = 1");
            $stmt_producto->execute([$producto_id]);
            $producto = $stmt_producto->fetch();
            
            if (!$producto) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto no encontrado o no disponible'
                ]);
                exit;
            }
            
            $cantidad = intval($_POST['cantidad'] ?? 1);
            if ($cantidad <= 0 || $cantidad > 99) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Cantidad inválida (debe ser entre 1 y 99)'
                ]);
                exit;
            }
            
            $resultado = $carritoPersistente->agregarProducto($cliente_id, $producto_id, $cantidad);
            
            if ($resultado) {
                // Obtener cantidad total de productos en carrito para la respuesta
                $productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
                $total_items = array_sum(array_column($productos_carrito, 'cantidad'));
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto agregado al carrito exitosamente',
                    'data' => [
                        'producto_nombre' => $producto['nombre'],
                        'cantidad_agregada' => $cantidad,
                        'total_items' => $total_items
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al agregar producto al carrito'
                ]);
            }
            break;
            
        case 'actualizar':
            $cantidad = intval($_POST['cantidad'] ?? 1);
            if ($cantidad < 0 || $cantidad > 99) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Cantidad inválida (debe ser entre 0 y 99)'
                ]);
                exit;
            }
            
            // Si cantidad es 0, eliminar producto
            if ($cantidad === 0) {
                $resultado = $carritoPersistente->eliminarProducto($cliente_id, $producto_id);
                $mensaje = $resultado ? 'Producto eliminado del carrito' : 'Error al eliminar producto';
            } else {
                $resultado = $carritoPersistente->actualizarCantidad($cliente_id, $producto_id, $cantidad);
                $mensaje = $resultado ? 'Cantidad actualizada exitosamente' : 'Error al actualizar cantidad';
            }
            
            if ($resultado) {
                // Obtener carrito actualizado para calcular nuevo total
                $productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
                $total = 0;
                $total_items = 0;
                
                foreach ($productos_carrito as $producto) {
                    $total += $producto['precio'] * $producto['cantidad'];
                    $total_items += $producto['cantidad'];
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $mensaje,
                    'data' => [
                        'total' => number_format($total, 2),
                        'total_items' => $total_items
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $mensaje
                ]);
            }
            break;
            
        case 'eliminar':
            $resultado = $carritoPersistente->eliminarProducto($cliente_id, $producto_id);
            
            if ($resultado) {
                // Obtener carrito actualizado
                $productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
                $total = 0;
                $total_items = 0;
                
                foreach ($productos_carrito as $producto) {
                    $total += $producto['precio'] * $producto['cantidad'];
                    $total_items += $producto['cantidad'];
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto eliminado del carrito exitosamente',
                    'data' => [
                        'total' => number_format($total, 2),
                        'total_items' => $total_items,
                        'carrito_vacio' => count($productos_carrito) === 0
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar producto del carrito'
                ]);
            }
            break;
            
        case 'vaciar':
            $resultado = $carritoPersistente->vaciarCarrito($cliente_id);
            
            header('Content-Type: application/json');
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Carrito vaciado exitosamente',
                    'data' => [
                        'total' => '0.00',
                        'total_items' => 0,
                        'carrito_vacio' => true
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al vaciar el carrito'
                ]);
            }
            break;
            
        case 'obtener':
            // Obtener contenido actual del carrito
            $productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
            $total = 0;
            $total_items = 0;
            
            // Procesar productos del carrito
            foreach ($productos_carrito as $index => $producto) {
                $productos_carrito[$index]['subtotal'] = $producto['precio'] * $producto['cantidad'];
                $total += $productos_carrito[$index]['subtotal'];
                $total_items += $producto['cantidad'];
                
                // Convertir imagen a base64 si existe
                if (!empty($producto['imagen1'])) {
                    $productos_carrito[$index]['imagen_principal'] = base64_encode($producto['imagen1']);
                } else {
                    $productos_carrito[$index]['imagen_principal'] = '';
                }
            }
            unset($producto); // Romper la referencia
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'productos' => $productos_carrito,
                    'total' => number_format($total, 2),
                    'total_items' => $total_items,
                    'carrito_vacio' => count($productos_carrito) === 0
                ]
            ]);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en carritoController: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo más tarde.',
        'error' => $e->getMessage()
    ]);
}
?>
