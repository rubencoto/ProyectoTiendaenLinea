<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../modelo/conexion.php';
require_once '../modelo/config.php';

// Set charset for this script to prevent collation issues
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function obtenerContenidoImagen($campo) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES[$campo]['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            error_log("Invalid file type: " . $file_type);
            return null;
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) {
            error_log("File too large: " . $_FILES[$campo]['size']);
            return null;
        }
        
        // Read file content as binary data
        $binary_data = file_get_contents($_FILES[$campo]['tmp_name']);
        
        // Log file info for debugging
        error_log("Processing image: " . $_FILES[$campo]['name'] . ", Size: " . $_FILES[$campo]['size'] . ", Type: " . $file_type);
        
        return $binary_data;
    }
    return null;
}

// Collect form data
$nombre = $_POST["nombre"];
$descripcion = $_POST["descripcion"];
$precio = $_POST["precio"];
$categoria = $_POST["categoria"];
$tallas = $_POST["tallas"];
$color = $_POST["color"];
$unidades = $_POST["unidades"];
$garantia = $_POST["garantia"];
$dimensiones = $_POST["dimensiones"];
$peso = $_POST["peso"];
$tamano_empaque = $_POST["tamano_empaque"];

$imagenPrincipal = obtenerContenidoImagen("imagen_principal");
$imagenSecundaria1 = obtenerContenidoImagen("imagen_secundaria1");
$imagenSecundaria2 = obtenerContenidoImagen("imagen_secundaria2");

// Debug image data
error_log("Image principal size: " . ($imagenPrincipal ? strlen($imagenPrincipal) : 'NULL'));
error_log("Image secundaria1 size: " . ($imagenSecundaria1 ? strlen($imagenSecundaria1) : 'NULL'));
error_log("Image secundaria2 size: " . ($imagenSecundaria2 ? strlen($imagenSecundaria2) : 'NULL'));

// Ensure we have at least the principal image
if (!$imagenPrincipal) {
    die("Error: Imagen principal es requerida.");
}

// Get vendor ID from session
$id_vendedor = $_SESSION['id'];

// Debug: Log the values being inserted
error_log("Processing product insert with values:");
error_log("nombre: " . $nombre);
error_log("descripcion: " . $descripcion);
error_log("categoria: " . $categoria);

// Prepare the SQL statement
$sql = "INSERT INTO productos 
    (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Use a simpler approach: bind all parameters including images directly
$stmt->bind_param(
    "ssdsbbssissdsi",
    $nombre,           // 1: s - string
    $descripcion,      // 2: s - string
    $precio,           // 3: d - double
    $categoria,        // 4: s - string
    $imagenPrincipal,  // 5: b - binary
    $imagenSecundaria1, // 6: b - binary
    $imagenSecundaria2, // 7: b - binary
    $tallas,           // 8: s - string
    $color,            // 9: s - string
    $unidades,         // 10: i - integer
    $garantia,         // 11: s - string
    $dimensiones,      // 12: s - string
    $peso,             // 13: d - double
    $tamano_empaque,   // 14: s - string
    $id_vendedor       // 15: i - integer
);

if ($stmt->execute()) {
    // Generate URLs for JavaScript
    $agregarProductoUrl = AppConfig::vistaUrl('agregarproducto.php');
    $inicioVendedorUrl = AppConfig::vistaUrl('inicioVendedor.php');
    
    // Success response
    echo '
    <!DOCTYPE html>
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
    // Enhanced error handling
    $error = $stmt->error;
    $errno = $stmt->errno;
    
    error_log("Product insert failed: " . $error . " (Error #" . $errno . ")");
    echo "Error al guardar producto: " . $error . " (Error #" . $errno . ")";
}

$stmt->close();
$conn->close();
?>
