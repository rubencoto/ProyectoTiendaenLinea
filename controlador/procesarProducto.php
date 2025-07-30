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
        return file_get_contents($_FILES[$campo]['tmp_name']);
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if vendor session exists
    if (!isset($_SESSION['id'])) {
        echo "Error: Sesión de vendedor no válida. Por favor inicia sesión nuevamente.";
        exit;
    }
    
    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $precio = $_POST["precio"];
    $categoria = $_POST["categoria"];
    $tallas = $_POST["tallas"];
    $color = $_POST["color"];
    $unidades = $_POST["unidades"]; // This maps to 'unidades' column in your database
    $garantia = $_POST["garantia"];
    $dimensiones = $_POST["dimensiones"];
    $peso = $_POST["peso"];
    $tamano_empaque = $_POST["tamano_empaque"];

    $imagenPrincipal = obtenerContenidoImagen("imagen_principal");
    $imagenSecundaria1 = obtenerContenidoImagen("imagen_secundaria1");
    $imagenSecundaria2 = obtenerContenidoImagen("imagen_secundaria2");

    // Obtener el ID del vendedor de la sesión
    $id_vendedor = $_SESSION['id'];

    // Debug: Log the values being inserted to help diagnose charset issues
    error_log("Processing product insert with values:");
    error_log("nombre: " . $nombre);
    error_log("descripcion: " . $descripcion);
    error_log("categoria: " . $categoria);

    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssdssssssissdsi",
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
        $tamano_empaque,
        $id_vendedor
    );

    if ($stmt->execute()) {
        // Generate URLs for JavaScript
        $agregarProductoUrl = AppConfig::vistaUrl('agregarproducto.php');
        $inicioVendedorUrl = AppConfig::vistaUrl('inicioVendedor.php');
        
        // Usamos SweetAlert2 aquí
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
        // Enhanced error handling for charset/collation issues
        $error = $stmt->error;
        $errno = $stmt->errno;
        
        // Check for charset/collation specific errors
        if (strpos($error, 'collation') !== false || strpos($error, 'charset') !== false) {
            // Try to fix charset issue and retry
            $conn->set_charset("utf8mb4");
            $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            if ($stmt->execute()) {
                // Success after charset fix
                $agregarProductoUrl = AppConfig::vistaUrl('agregarproducto.php');
                $inicioVendedorUrl = AppConfig::vistaUrl('inicioVendedor.php');
                
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
                // Still failed after charset fix
                echo "Error al guardar producto (charset issue): " . $stmt->error;
            }
        } else {
            // Other type of error
            echo "Error al guardar producto: " . $error . " (Error #" . $errno . ")";
        }
    }

    $stmt->close();
    $conn->close();
}
?>
