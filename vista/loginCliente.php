<?php
session_start();
require_once '../modelo/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    $stmt = $conn->prepare("SELECT id, contrasena, verificado FROM clientes WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hash, $verificado);
        $stmt->fetch();

        if (!$verificado) {
            $error = "La cuenta aún no ha sido verificada.";
        } elseif (password_verify($contrasena, $hash)) {
            $_SESSION['cliente_id'] = $id;
            header("Location: inicioCliente.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Correo no registrado.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 400px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="mb-4 text-center">Iniciar Sesión - Cliente</h3>
        
        <form method="POST">
            <div class="mb-3">
                <label>Correo electrónico</label>
                <input type="email" class="form-control" name="correo" required>
            </div>
            <div class="mb-3">
                <label>Contraseña</label>
                <input type="password" class="form-control" name="contrasena" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-3">
            <p class="small">¿No tienes cuenta? <a href="registroCliente.php">Regístrate aquí</a></p>
            <p class="small"><a href="loginVendedor.php">¿Eres vendedor? Ingresa aquí</a></p>
        </div>
    </div>
</body>
</html>
