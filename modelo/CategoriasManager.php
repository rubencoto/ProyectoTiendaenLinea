<?php
/**
 * Manejador de Categorías para Productos
 * Maneja todas las operaciones relacionadas con categorías y su asignación a productos
 */

require_once 'conexion.php';

class CategoriasManager {
    private $conn;

    public function __construct() {
        $db = DatabaseConnection::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * Obtener todas las categorías activas
     */
    public function obtenerCategoriasActivas() {
        try {
            $stmt = $this->conn->prepare("
                SELECT id_categoria, nombre_categoria, descripcion 
                FROM categorias 
                WHERE activa = 1 
                ORDER BY nombre_categoria
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener categorías activas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una categoría por su ID
     */
    public function obtenerCategoriaPorId($id_categoria) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id_categoria, nombre_categoria, descripcion, activa 
                FROM categorias 
                WHERE id_categoria = ?
            ");
            $stmt->execute([$id_categoria]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error al obtener categoría por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Asignar una categoría a un producto
     */
    public function asignarCategoriaAProducto($id_producto, $id_categoria) {
        try {
            $stmt = $this->conn->prepare("
                INSERT IGNORE INTO productos_categorias (id_producto, id_categoria) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$id_producto, $id_categoria]);
        } catch (Exception $e) {
            error_log("Error al asignar categoría a producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todas las categorías de un producto específico
     */
    public function obtenerCategoriasPorProducto($id_producto) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.id_categoria, c.nombre_categoria, c.descripcion
                FROM categorias c
                INNER JOIN productos_categorias pc ON c.id_categoria = pc.id_categoria
                WHERE pc.id_producto = ? AND c.activa = 1
                ORDER BY c.nombre_categoria
            ");
            $stmt->execute([$id_producto]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener categorías del producto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los productos de una categoría específica
     */
    public function obtenerProductosPorCategoria($id_categoria, $busqueda = '', $orden = 'reciente', $limite = null) {
        try {
            $sql = "
                SELECT p.*, v.nombre_empresa as vendedor_nombre
                FROM productos p
                INNER JOIN productos_categorias pc ON p.id = pc.id_producto
                INNER JOIN vendedores v ON p.id_vendedor = v.id
                WHERE pc.id_categoria = ? AND p.activo = 1
            ";
            
            $params = [$id_categoria];
            
            // Agregar filtro de búsqueda si se especifica
            if (!empty($busqueda)) {
                $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR v.nombre_empresa LIKE ?)";
                $busqueda_param = '%' . $busqueda . '%';
                $params[] = $busqueda_param;
                $params[] = $busqueda_param;
                $params[] = $busqueda_param;
            }
            
            // Agregar orden
            switch ($orden) {
                case 'asc':
                    $sql .= " ORDER BY p.nombre ASC";
                    break;
                case 'desc':
                    $sql .= " ORDER BY p.nombre DESC";
                    break;
                case 'precio_asc':
                    $sql .= " ORDER BY p.precio ASC";
                    break;
                case 'precio_desc':
                    $sql .= " ORDER BY p.precio DESC";
                    break;
                case 'reciente':
                default:
                    $sql .= " ORDER BY p.id DESC";
                    break;
            }
            
            if ($limite) {
                $sql .= " LIMIT " . intval($limite);
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener productos por categoría: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar productos por nombre de categoría
     */
    public function buscarProductosPorNombreCategoria($nombre_categoria) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, v.nombre_empresa as vendedor_nombre, c.nombre_categoria
                FROM productos p
                INNER JOIN productos_categorias pc ON p.id = pc.id_producto
                INNER JOIN categorias c ON pc.id_categoria = c.id_categoria
                INNER JOIN vendedores v ON p.id_vendedor = v.id
                WHERE c.nombre_categoria LIKE ? AND p.activo = 1 AND c.activa = 1
                ORDER BY p.id DESC
            ");
            $stmt->execute(['%' . $nombre_categoria . '%']);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al buscar productos por nombre de categoría: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar productos por categoría
     */
    public function contarProductosPorCategoria($id_categoria) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM productos_categorias pc
                INNER JOIN productos p ON pc.id_producto = p.id
                WHERE pc.id_categoria = ? AND p.activo = 1
            ");
            $stmt->execute([$id_categoria]);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error al contar productos por categoría: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Crear una nueva categoría
     */
    public function crearCategoria($nombre_categoria, $descripcion = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO categorias (nombre_categoria, descripcion) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$nombre_categoria, $descripcion]);
        } catch (Exception $e) {
            error_log("Error al crear categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar una categoría existente
     */
    public function actualizarCategoria($id_categoria, $nombre_categoria, $descripcion = null, $activa = 1) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE categorias 
                SET nombre_categoria = ?, descripcion = ?, activa = ?
                WHERE id_categoria = ?
            ");
            return $stmt->execute([$nombre_categoria, $descripcion, $activa, $id_categoria]);
        } catch (Exception $e) {
            error_log("Error al actualizar categoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de categorías (con conteo de productos)
     */
    public function obtenerEstadisticasCategorias() {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.id_categoria, c.nombre_categoria, c.descripcion,
                       COUNT(p.id) as total_productos
                FROM categorias c
                LEFT JOIN productos_categorias pc ON c.id_categoria = pc.id_categoria
                LEFT JOIN productos p ON pc.id_producto = p.id
                WHERE c.activa = 1
                GROUP BY c.id_categoria, c.nombre_categoria
                ORDER BY total_productos DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas de categorías: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remover una categoría específica de un producto
     */
    public function removerCategoriaDelProducto($id_producto, $id_categoria = null) {
        try {
            if ($id_categoria) {
                // Remover una categoría específica
                $sql = "DELETE FROM productos_categorias 
                       WHERE id_producto = ? AND id_categoria = ?";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$id_producto, $id_categoria]);
            } else {
                // Remover todas las categorías del producto
                $sql = "DELETE FROM productos_categorias WHERE id_producto = ?";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$id_producto]);
            }
        } catch (Exception $e) {
            error_log("Error removiendo categoría del producto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar la categoría de un producto (reemplaza la existente)
     */
    public function actualizarCategoriaProducto($id_producto, $nueva_id_categoria) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Remover categorías existentes
            $this->removerCategoriaDelProducto($id_producto);
            
            // Asignar nueva categoría
            $resultado = $this->asignarCategoriaAProducto($id_producto, $nueva_id_categoria);
            
            if ($resultado) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error actualizando categoría del producto: " . $e->getMessage());
            return false;
        }
    }
}
?>