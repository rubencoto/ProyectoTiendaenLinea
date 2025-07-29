<?php
session_start();

header('Content-Type: application/json');

$cantidad_total = 0;
if (isset($_SESSION['carrito'])) {
    $cantidad_total = array_sum($_SESSION['carrito']);
}

echo json_encode(['count' => $cantidad_total]);
?>
