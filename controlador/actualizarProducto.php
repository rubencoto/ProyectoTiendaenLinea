<?php
session_start();
require_once '../modelo/conexion.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
    $types = "ssdsssissds";

    if (!empty($_FILES['imagen_principal']['tmp_name'])) {
        $imagen_principal = file_get_contents($_FILES['imagen_principal']['tmp_name']);
        $sql .= ", imagen_principal = ?";
        $params[] = $imagen_principal;
        $types .= "b";
    }

    if (!empty($_FILES['imagen_secundaria1']['tmp_name'])) {
        $imagen_secundaria1 = file_get_contents($_FILES['imagen_secundaria1']['tmp_name']);
        $sql .= ", imagen_secundaria1 = ?";
        $params[] = $imagen_secundaria1;
        $types .= "b";
    }

    if (!empty($_FILES['imagen_secundaria2']['tmp_name'])) {
        $imagen_secundaria2 = file_get_contents($_FILES['imagen_secundaria2']['tmp_name']);
        $sql .= ", imagen_secundaria2 = ?";
        $params[] = $imagen_secundaria2;
        $types .= "b";
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = " Producto actualizado correctamente.";
        header("Location: ../vista/productoDetalle.php?id=" . $id);
        exit;
    } else {
        echo " Error al actualizar el producto: " . $stmt->error;
    }

    $stmt->close();
    // Connection managed by singleton, no need to close explicitly

} else {
    echo "Método no permitido.";
}
?>
