<?php
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];
    $codigo = $_POST['codigo'];

    $stmt = $conn->prepare("SELECT id FROM vendedores WHERE correo = ? AND codigo_verificacion = ? AND verificado = 0");
    $stmt->bind_param("ss", $correo, $codigo);
    $stmt->execute();
    $stmt->store_result();

    $htmlHeader = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Verificación de cuenta</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                background-color: #f2f2f2;
                min-height: 100vh;
            }
            .header {
                background-color: #232f3e;
                color: white;
                padding: 15px;
                text-align: center;
                font-size: 1.5rem;
                font-weight: 600;
                letter-spacing: 1px;
            }
            .container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 80vh;
            }
            .card {
                background: white;
                padding: 28px 24px 20px 24px;
                border-radius: 10px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 370px;
                width: 100%;
                margin: 0 auto;
            }
            .card h3 {
                margin: 10px 0 18px 0;
                font-size: 1.2rem;
            }
            .card a {
                text-decoration: none;
                color: #007185;
                font-weight: bold;
                display: inline-block;
                margin-top: 10px;
            }
            .card a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="header">Verificación de cuenta</div>
        <div class="container">
            <div class="card">
    ';
    $htmlFooter = '
            </div>
        </div>
    </body>
    </html>
    ';

    if ($stmt->num_rows === 1) {
        $stmt->close();
        $update = $conn->prepare("UPDATE vendedores SET verificado = 1 WHERE correo = ?");
        $update->bind_param("s", $correo);
        $update->execute();

        echo $htmlHeader . "<h3>Cuenta verificada correctamente.</h3><a href='" . AppConfig::vistaUrl('loginVendedor.php') . "'>Iniciar sesión</a>" . $htmlFooter;
    } else {
        echo $htmlHeader . "<h3>Código incorrecto o ya verificado.</h3><a href='../vista/verificarCuenta.php'>Reintentar</a>" . $htmlFooter;
    }
    // Connection managed by singleton, no need to close explicitly
} else {
    header("Location: ../vista/verificarCuenta.php");
    exit();
}
?>