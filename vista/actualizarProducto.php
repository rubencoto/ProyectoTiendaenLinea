<?php
session_start();

// Turn off error reporting to prevent HTML error output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../modelo/conexion.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];
    $tallas = $_POST['tallas'];
    $color = $_POST['color'];
    $stock = $_POST['stock'];
    $garantia = $_POST['garantia'];
    $dimensiones = $_POST['dimensiones'];
    $peso = $_POST['peso'];
    $tamano_empaque = $_POST['tamano_empaque'];

    $sql = "UPDATE productos SET 
                nombre = ?, 
                descripcion = ?, 
                precio = ?, 
                categoria = ?, 
                tallas = ?, 
                color = ?, 
                stock = ?, 
                garantia = ?, 
                dimensiones = ?, 
                peso = ?, 
                tamano_empaque = ?";

    $params = [$nombre, $descripcion, $precio, $categoria, $tallas, $color, $stock, $garantia, $dimensiones, $peso, $tamano_empaque];

    // Handle image uploads
    if (!empty($_FILES['imagen_principal']['tmp_name'])) {
        $imagen_principal = file_get_contents($_FILES['imagen_principal']['tmp_name']);
        $sql .= ", imagen_principal = ?";
        $params[] = $imagen_principal;
    }

    if (!empty($_FILES['imagen_secundaria1']['tmp_name'])) {
        $imagen_secundaria1 = file_get_contents($_FILES['imagen_secundaria1']['tmp_name']);
        $sql .= ", imagen_secundaria1 = ?";
        $params[] = $imagen_secundaria1;
    }

    if (!empty($_FILES['imagen_secundaria2']['tmp_name'])) {
        $imagen_secundaria2 = file_get_contents($_FILES['imagen_secundaria2']['tmp_name']);
        $sql .= ", imagen_secundaria2 = ?";
        $params[] = $imagen_secundaria2;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta");
        }

        if ($stmt->execute($params)) {
            $_SESSION['mensaje_exito'] = "Producto actualizado correctamente.";
            // Fix: Redirect to productodetalle.php (lowercase)
            header("Location: productodetalle.php?id=" . $id);
            exit;
        } else {
            $_SESSION['mensaje_error'] = "Error al actualizar el producto.";
            header("Location: editarProducto.php?id=" . $id);
            exit;
        }

    } catch (Exception $e) {
        error_log("Error in actualizarProducto.php: " . $e->getMessage());
        $_SESSION['mensaje_error'] = "Error interno del servidor.";
        header("Location: editarProducto.php?id=" . $id);
        exit;
    }

} else {
    echo "Método no permitido.";
}
?>
