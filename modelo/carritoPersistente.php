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
            $stmt = $this->conn->prepare("
                SELECT cp.id, cp.cliente_id, cp.producto_id, cp.cantidad, cp.fecha_agregado, cp.fecha_actualizado,
                       p.nombre, p.precio, p.imagen1, p.stock, p.id_vendedor, 
                       v.nombre_empresa as vendedor_nombre
                FROM carrito_persistente cp
                INNER JOIN productos p ON cp.producto_id = p.id
                LEFT JOIN vendedores v ON p.id_vendedor = v.id
                WHERE cp.cliente_id = ?
                ORDER BY cp.fecha_agregado DESC
            ");
            $stmt->execute([$cliente_id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug logging
            error_log("CarritoPersistente::obtenerCarrito - Cliente ID: $cliente_id, Productos encontrados: " . count($result));
            
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
