<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Exitoso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 text-center">
    <?php if (isset($_GET['exito']) && $_GET['exito'] == 1): ?>
        <h3>Registro exitoso</h3>
        
        <?php if (isset($_GET['correo_error']) && $_GET['correo_error'] == 1): ?>
            <div class="alert alert-warning mt-3" role="alert">
                Hubo un problema al enviar el correo de verificación, pero tu cuenta fue registrada correctamente.
            </div>
        <?php else: ?>
            <p>Se ha enviado un código de verificación a tu correo electrónico.</p>
        <?php endif; ?>
        
        <a href="verificarCuentaCliente.php" class="btn btn-primary">Verificar cuenta</a>
        <br><br>
        <a href="loginCliente.php" class="btn btn-secondary">Ir al Login</a>
        
    <?php else: ?>
        <h3>Error en el registro</h3>
        <p>No se pudo completar el registro. Por favor, inténtalo de nuevo.</p>
        <a href="registroCliente.php" class="btn btn-primary">Volver al Registro</a>
    <?php endif; ?>
</div>
</body>
</html>
