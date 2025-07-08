<?php
include '../modelo/conexion.php';

// Función para convertir imagen en binario
function obtenerContenidoImagen($campo) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        return file_get_contents($_FILES[$campo]['tmp_name']);
    }
    return null;
}

// Recibir los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre            = $_POST["nombre"];
    $descripcion       = $_POST["descripcion"];
    $precio            = $_POST["precio"];
    $categoria         = $_POST["categoria"];
    $tallas            = $_POST["tallas"];
    $color             = $_POST["color"];
    $unidades          = $_POST["unidades"];
    $garantia          = $_POST["garantia"];
    $dimensiones       = $_POST["dimensiones"];
    $peso              = $_POST["peso"];
    $tamano_empaque    = $_POST["tamano_empaque"];

    // Convertir imágenes a binario
    $imagenPrincipal     = obtenerContenidoImagen("imagen_principal");
    $imagenSecundaria1   = obtenerContenidoImagen("imagen_secundaria1");
    $imagenSecundaria2   = obtenerContenidoImagen("imagen_secundaria2");

    // Preparar inserción con bind_param para BLOBs
    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssdssssssissds",
        $nombre,
        $descripcion,
        $precio,
        $categoria,
        $imagenPrincipal,
        $imagenSecundaria1,
        $imagenSecundaria2,
        $tallas,
        $color,
        $unidades,
        $garantia,
        $dimensiones,
        $peso,
        $tamano_empaque
    );

    if ($stmt->execute()) {
        echo "✅ Producto agregado con éxito. <a href='../vista/agregarProducto.php'>Agregar otro</a>";
    } else {
        echo "❌ Error al guardar producto: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
