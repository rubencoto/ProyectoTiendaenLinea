<?php
require_once '../modelo/conexion.php';

$error = '';
$exito = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token no proporcionado.");
}

// Verificamos token válido y no expirado
$stmt = $conn->prepare("SELECT id FROM clientes WHERE reset_token = ? AND token_expira > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    die("Enlace inválido o expirado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    if ($nueva_contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($nueva_contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE clientes SET contrasena = ?, reset_token = NULL, token_expira = NULL, codigo_verificacion = NULL, codigo_expira = NULL WHERE reset_token = ?");
        $update->bind_param("ss", $hash, $token);

        if ($update->execute()) {
            $exito = "Contraseña actualizada correctamente.";
        } else {
            $error = "Error al actualizar la contraseña.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.container { max-width: 400px; margin: 80px auto; }</style>
</head>
<body>
<div class="container">
    <h4 class="mb-4 text-center">Ingresa tu nueva contraseña</h4>

    <?php if ($exito): ?>
        <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
        <p class="text-center"><a href="index.php">Volver al login</a></p>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label>Nueva contraseña</label>
                <input type="password" name="nueva_contrasena" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label>Confirmar contraseña</label>
                <input type="password" name="confirmar_contrasena" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Actualizar contraseña</button>
        </form>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>