<?php
session_start();
require_once '../modelo/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    $stmt = $conn->prepare("SELECT id, contrasena, verificado FROM vendedores WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hash, $verificado);
        $stmt->fetch();

        if (!$verificado) {
            $error = "La cuenta aún no ha sido verificada.";
        } elseif (password_verify($contrasena, $hash)) {
<<<<<<< HEAD
            $_SESSION['vendedor_id'] = $id;
            header("Location: panelVendedor.php");
=======
            $_SESSION['id'] = $id;
            header("Location: inicioVendedor.php");
>>>>>>> e608ed9 (Updated project files with latest changes)
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
    <title>Login Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 400px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="mb-4 text-center">Iniciar Sesión</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Correo electrónico</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Contraseña</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>
</body>
</html>
