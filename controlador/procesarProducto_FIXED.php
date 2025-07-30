<?php
// Product Processing Script - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../modelo/conexion.php';
require_once '../modelo/config.php';

// Set charset
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function obtenerContenidoImagen($campo) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES[$campo]['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            return null;
        }
        
        if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) {
            return null;
        }
        
        return file_get_contents($_FILES[$campo]['tmp_name']);
    }
    return null;
}

// Collect form data
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

$imagenPrincipal = obtenerContenidoImagen("imagen_principal");
$imagenSecundaria1 = obtenerContenidoImagen("imagen_secundaria1");
$imagenSecundaria2 = obtenerContenidoImagen("imagen_secundaria2");

// Get vendor ID from session
$id_vendedor = $_SESSION['id'] ?? 1;

// Prepare SQL - 15 columns total
$sql = "INSERT INTO productos 
    (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// CORRECTED: 15 parameters with correct types
// Count: s-s-d-s-b-b-b-s-s-i-s-s-d-s-i = 15 characters
$result = $stmt->bind_param(
    "ssdsbbssissdsi",  // Exactly 15 characters
    $nombre,           // 1: s
    $descripcion,      // 2: s  
    $precio,           // 3: d
    $categoria,        // 4: s
    $imagenPrincipal,  // 5: b
    $imagenSecundaria1,// 6: b
    $imagenSecundaria2,// 7: b
    $tallas,           // 8: s
    $color,            // 9: s
    $unidades,         // 10: i
    $garantia,         // 11: s
    $dimensiones,      // 12: s
    $peso,             // 13: d
    $tamano_empaque,   // 14: s
    $id_vendedor       // 15: i
);

if (!$result) {
    die("Bind failed: " . $stmt->error);
}

if ($stmt->execute()) {
    $agregarProductoUrl = AppConfig::vistaUrl('agregarproducto.php');
    $inicioVendedorUrl = AppConfig::vistaUrl('inicioVendedor.php');
    
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Producto agregado</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: "Producto agregado con éxito",
            text: "¿Deseas agregar otro artículo?",
            icon: "success",
            showCancelButton: true,
            confirmButtonText: "Sí, agregar otro",
            cancelButtonText: "No, volver al panel"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "' . $agregarProductoUrl . '";
            } else {
                window.location.href = "' . $inicioVendedorUrl . '";
            }
        });
    </script>
    </body>
    </html>';
} else {
    echo "Error al guardar producto: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
