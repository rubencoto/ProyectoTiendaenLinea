<?php
include 'conexion.php';

function agregarProducto($nombre, $descripcion, $precio, $categoria) {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $categoria);

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
}
?>
