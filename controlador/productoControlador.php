<?php
require_once '../modelo/conexion.php';

class ProductoControlador {
    public function obtenerProductoPorId($id) {
        global $conn;

        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $producto = $resultado->fetch_assoc();
        $stmt->close();

        return $producto;
    }
}
