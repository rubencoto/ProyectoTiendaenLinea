<?php
// Incluye el archivo de conexión a la base de datos
include 'conexion.php';

// Función para agregar un producto a la base de datos
function agregarProducto($nombre, $descripcion, $precio, $categoria) {
    global $conn; // Usa la conexión global

    // Prepara la consulta SQL para insertar un nuevo producto
    $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria) VALUES (?, ?, ?, ?)");
    // Asocia los parámetros a la consulta (s=string, d=double)
    $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $categoria);

    // Ejecuta la consulta y guarda el resultado (true/false)
    $resultado = $stmt->execute();
    // Cierra la consulta preparada
    $stmt->close();
    // Devuelve el resultado de la operación
    return $resultado;
}
?>
