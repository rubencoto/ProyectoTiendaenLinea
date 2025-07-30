<?php
// Simple test processor - no images for now
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../modelo/conexion.php';
require_once '../modelo/config.php';

// Set charset
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
}

// Collect basic form data (no images for now)
$nombre = $_POST["nombre"] ?? '';
$descripcion = $_POST["descripcion"] ?? '';
$precio = floatval($_POST["precio"] ?? 0);
$categoria = $_POST["categoria"] ?? '';
$tallas = $_POST["tallas"] ?? '';
$color = $_POST["color"] ?? '';
$unidades = intval($_POST["unidades"] ?? 0);
$garantia = $_POST["garantia"] ?? '';
$dimensiones = $_POST["dimensiones"] ?? '';
$peso = floatval($_POST["peso"] ?? 0);
$tamano_empaque = $_POST["tamano_empaque"] ?? '';
$id_vendedor = $_SESSION['id'] ?? 1;

// Simple insert without images first
$sql = "INSERT INTO productos 
    (nombre, descripcion, precio, categoria, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// 12 parameters - no images
$stmt->bind_param(
    "ssdsssississi",
    $nombre,           // s
    $descripcion,      // s
    $precio,           // d
    $categoria,        // s
    $tallas,           // s
    $color,            // s
    $unidades,         // i
    $garantia,         // s
    $dimensiones,      // s
    $peso,             // d
    $tamano_empaque,   // s
    $id_vendedor       // i
);

if ($stmt->execute()) {
    echo "SUCCESS: Product added with ID: " . $conn->insert_id;
} else {
    echo "ERROR: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
