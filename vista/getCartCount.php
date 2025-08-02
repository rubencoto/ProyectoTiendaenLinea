<?php
session_start();

header('Content-Type: application/json');

$cantidad_total = 0;
if (isset($_SESSION['cliente_id'])) {
    require_once '../modelo/carritoPersistente.php';
    $carritoPersistente = new CarritoPersistente();
    $cantidad_total = $carritoPersistente->contarProductos($_SESSION['cliente_id']);
}

echo json_encode(['count' => $cantidad_total]);
?>
