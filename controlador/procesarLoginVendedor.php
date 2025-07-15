<?php
// controlador/procesarLoginVendedor.php
session_start();
require_once '../modelo/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM Vendedores WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $vendedor = $resultado->fetch_assoc();

        if (password_verify($contrasena, $vendedor['contrasena_hash'])) {
            if ($vendedor['verificado']) {
                $_SESSION['vendedor_id'] = $vendedor['id'];
                $_SESSION['nombre_empresa'] = $vendedor['nombre_empresa'];
                header("Location: ../vista/inicioVendedor.php");
                exit;
            } else {
                echo "<p>Cuenta no verificada. Por favor verifica tu cuenta.</p>";
            }
        } else {
            echo "<p>Contraseña incorrecta.</p>";
        }
    } else {
        echo "<p>No existe una cuenta con ese correo.</p>";
    }

    $stmt->close();
}
?>
