<?php
require_once '../modelo/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $nueva = $_POST['nueva_contrasena'] ?? '';

    if (empty($token) || empty($codigo) || empty($nueva)) {
        die(" Todos los campos son obligatorios.");
    }

    $stmt = $conn->prepare("SELECT id FROM clientes 
        WHERE reset_token = ? 
        AND token_expira > NOW() 
        AND codigo_verificacion = ? 
        AND codigo_expira > NOW()");
    $stmt->bind_param("ss", $token, $codigo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id);
        $stmt->fetch();

        $hash = password_hash($nueva, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE clientes SET contrasena = ?, reset_token = NULL, token_expira = NULL, codigo_verificacion = NULL, codigo_expira = NULL WHERE id = ?");
        $update->bind_param("si", $hash, $id);

        if ($update->execute()) {
            echo "<p style='text-align:center; color:green;'> Contraseña actualizada correctamente. <a href='index.php'>Iniciar sesión</a></p>";
        } else {
            echo "<p style='text-align:center; color:red;'> Error al actualizar la contraseña.</p>";
        }

        $update->close();
    } else {
        echo "<p style='text-align:center; color:red;'> Código inválido, expirado o token incorrecto.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>