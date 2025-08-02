<?php
require_once 'conexion.php';

class CarritoPersistente {
    private $conn;
    
    public function __construct() {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }
    
    /**
     * Agregar producto al carrito persistente
     */
    public function agregarProducto($cliente_id, $producto_id, $cantidad = 1) {
        try {
            // Debug logging
            error_log("CarritoPersistente::agregarProducto - Cliente: $cliente_id, Producto: $producto_id, Cantidad: $cantidad");
            
            // First, verify the product exists and is active
            $stmt_verify = $this->conn->prepare("
                SELECT id, nombre, activo 
                FROM productos 
                WHERE id = ? AND activo = 1
            ");
            $stmt_verify->execute([$producto_id]);
            $product_exists = $stmt_verify->fetch();
            
            if (!$product_exists) {
                error_log("CarritoPersistente::agregarProducto - Product $producto_id not found or inactive");
                return false;
            }
            
            // Verificar si el producto ya existe en el carrito
            $stmt = $this->conn->prepare("
                SELECT id, cantidad 
                FROM carrito_persistente 
                WHERE cliente_id = ? AND producto_id = ?
            ");
            $stmt->execute([$cliente_id, $producto_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Actualizar cantidad existente
                $nueva_cantidad = $existing['cantidad'] + $cantidad;
                $stmt_update = $this->conn->prepare("
                    UPDATE carrito_persistente 
                    SET cantidad = ?, fecha_actualizado = NOW() 
                    WHERE id = ?
                ");
                $result = $stmt_update->execute([$nueva_cantidad, $existing['id']]);
                error_log("CarritoPersistente::agregarProducto - Actualizado. Resultado: " . ($result ? 'true' : 'false'));
                return $result;
            } else {
                // Insertar nuevo producto
                $stmt_insert = $this->conn->prepare("
                    INSERT INTO carrito_persistente (cliente_id, producto_id, cantidad, fecha_agregado, fecha_actualizado) 
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $result = $stmt_insert->execute([$cliente_id, $producto_id, $cantidad]);
                error_log("CarritoPersistente::agregarProducto - Insertado. Resultado: " . ($result ? 'true' : 'false'));
                return $result;
            }
        } catch (Exception $e) {
            error_log("Error en agregarProducto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar cantidad de producto en el carrito
     */
    public function actualizarCantidad($cliente_id, $producto_id, $cantidad) {
        try {
            if ($cantidad <= 0) {
                return $this->eliminarProducto($cliente_id, $producto_id);
            }
            
            $stmt = $this->conn->prepare("
                UPDATE carrito_persistente 
                SET cantidad = ?, fecha_actualizado = NOW() 
                WHERE cliente_id = ? AND producto_id = ?
            ");
            return $stmt->execute([$cantidad, $cliente_id, $producto_id]);
        } catch (Exception $e) {
            error_log("Error en actualizarCantidad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar producto específico del carrito
     */
    public function eliminarProducto($cliente_id, $producto_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM carrito_persistente 
                WHERE cliente_id = ? AND producto_id = ?
            ");
            return $stmt->execute([$cliente_id, $producto_id]);
        } catch (Exception $e) {
            error_log("Error en eliminarProducto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vaciar todo el carrito del cliente
     */
    public function vaciarCarrito($cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM carrito_persistente 
                WHERE cliente_id = ?
            ");
            return $stmt->execute([$cliente_id]);
        } catch (Exception $e) {
            error_log("Error en vaciarCarrito: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los productos del carrito del cliente
     */
    public function obtenerCarrito($cliente_id) {
        try {
            // First, get cart items
            $stmt = $this->conn->prepare("
                SELECT id, cliente_id, producto_id, cantidad, fecha_agregado, fecha_actualizado
                FROM carrito_persistente 
                WHERE cliente_id = ?
                ORDER BY fecha_agregado DESC
            ");
            $stmt->execute([$cliente_id]);
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cart_items)) {
                error_log("CarritoPersistente::obtenerCarrito - No cart items found for cliente_id: $cliente_id");
                return [];
            }
            
            error_log("CarritoPersistente::obtenerCarrito - Found " . count($cart_items) . " cart items for cliente_id: $cliente_id");
            
            // Then get product details for each item
            $result = [];
            foreach ($cart_items as $item) {
                $stmt_product = $this->conn->prepare("
                    SELECT p.id, p.nombre, p.precio, p.imagen_principal, p.stock, p.id_vendedor, p.activo
                    FROM productos p 
                    WHERE p.id = ? AND p.activo = 1
                ");
                $stmt_product->execute([$item['producto_id']]);
                $product = $stmt_product->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    // Merge cart item data with product data
                    $merged = array_merge($item, $product);
                    
                    // Map imagen_principal to imagen1 for compatibility
                    $merged['imagen1'] = $product['imagen_principal'];
                    
                    // Get vendor info
                    if ($product['id_vendedor']) {
                        $stmt_vendor = $this->conn->prepare("
                            SELECT nombre_empresa 
                            FROM vendedores 
                            WHERE id = ?
                        ");
                        $stmt_vendor->execute([$product['id_vendedor']]);
                        $vendor = $stmt_vendor->fetch(PDO::FETCH_ASSOC);
                        $merged['vendedor_nombre'] = $vendor ? $vendor['nombre_empresa'] : 'Vendedor no encontrado';
                    } else {
                        $merged['vendedor_nombre'] = 'Sin vendedor';
                    }
                    
                    $result[] = $merged;
                } else {
                    // Product doesn't exist or is inactive - remove from cart
                    error_log("CarritoPersistente::obtenerCarrito - Product not found or inactive for producto_id: " . $item['producto_id'] . " - removing from cart");
                    $this->eliminarProducto($cliente_id, $item['producto_id']);
                }
            }
            
            error_log("CarritoPersistente::obtenerCarrito - Returning " . count($result) . " products with details");
            return $result;
            
        } catch (Exception $e) {
            error_log("Error en obtenerCarrito: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener cantidad total de productos en el carrito
     */
    public function contarProductos($cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(cantidad) as total 
                FROM carrito_persistente 
                WHERE cliente_id = ?
            ");
            $stmt->execute([$cliente_id]);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error en contarProductos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Sincronizar carrito de sesión con base de datos (para migración)
     */
    public function sincronizarConSesion($cliente_id, $carrito_sesion) {
        try {
            foreach ($carrito_sesion as $producto_id => $cantidad) {
                $this->agregarProducto($cliente_id, $producto_id, $cantidad);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en sincronizarConSesion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convertir carrito de base de datos a formato de sesión (para compatibilidad)
     */
    public function convertirAFormatoSesion($cliente_id) {
        try {
            $carrito_db = $this->obtenerCarrito($cliente_id);
            $carrito_sesion = [];
            
            foreach ($carrito_db as $item) {
                $carrito_sesion[$item['producto_id']] = $item['cantidad'];
            }
            
            return $carrito_sesion;
        } catch (Exception $e) {
            error_log("Error en convertirAFormatoSesion: " . $e->getMessage());
            return [];
        }
    }
}
?>
