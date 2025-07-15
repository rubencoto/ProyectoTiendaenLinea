<?php
session_start();
include '../modelo/conexion.php';

function obtenerContenidoImagen($campo) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        return file_get_contents($_FILES[$campo]['tmp_name']);
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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

    if ($stmt->execute()) {
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
                title: "✅ Producto agregado con éxito",
                text: "¿Deseas agregar otro artículo?",
                icon: "success",
                showCancelButton: true,
                confirmButtonText: "Sí, agregar otro",
                cancelButtonText: "No, volver al panel"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../vista/agregarProducto.php";
                } else {
                    window.location.href = "../vista/inicioVendedor.php";
                }
            });
        </script>
        </body>
        </html>';
    } else {
        echo "❌ Error al guardar producto: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
