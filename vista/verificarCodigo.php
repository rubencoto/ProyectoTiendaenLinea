<?php
require_once '../modelo/conexion.php';

$error = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $codigo_ingresado = $_POST['codigo'] ?? '';

    $stmt = $conn->prepare("SELECT codigo_verificacion, codigo_expira FROM clientes WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($codigo_bd, $codigo_expira);
    if ($stmt->fetch()) {
        $stmt->close();
        $ahora = date("Y-m-d H:i:s");

        if ($codigo_bd !== $codigo_ingresado) {
            $error = "Código incorrecto.";
        } elseif ($ahora > $codigo_expira) {
            $error = "El código ha expirado.";
        } else {
            header("Location: restablecerContrasena.php?token=$token");
            exit;
        }
    } else {
        $error = "Token inválido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Código</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.container { max-width: 400px; margin: 80px auto; }</style>
</head>
<body>
<div class="container">
    <h4 class="mb-4 text-center">Ingresa tu código de verificación</h4>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
            <label>Código de verificación</label>
            <input type="text" name="codigo" class="form-control" maxlength="6" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Verificar</button>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</div>
</body>
</html>
