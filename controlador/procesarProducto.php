<?php
// Incluye el archivo de conexión a la base de datos
include '../modelo/conexion.php';

// Función para convertir la imagen subida a binario
function obtenerContenidoImagen($campo) {
    // Verifica si el archivo fue subido correctamente
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        // Devuelve el contenido binario del archivo
        return file_get_contents($_FILES[$campo]['tmp_name']);
    }
    // Si no hay archivo, retorna null
    return null;
}

// Recibe los datos del formulario solo si la petición es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtiene los valores enviados desde el formulario
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

    // Convierte las imágenes subidas a binario
    $imagenPrincipal     = obtenerContenidoImagen("imagen_principal");
    $imagenSecundaria1   = obtenerContenidoImagen("imagen_secundaria1");
    $imagenSecundaria2   = obtenerContenidoImagen("imagen_secundaria2");

    // Prepara la consulta SQL para insertar el producto, incluyendo imágenes y otros datos
    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Asocia los parámetros a la consulta preparada
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

    // Ejecuta la consulta y verifica si fue exitosa
    if ($stmt->execute()) {
        echo "✅ Producto agregado con éxito. <a href='../vista/agregarProducto.php'>Agregar otro</a>";
    } else {
        echo "❌ Error al guardar producto: " . $stmt->error;
    }

    // Cierra la consulta y la conexión
    $stmt->close();
    $conn->close();
}
?>
