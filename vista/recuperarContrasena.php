<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.container { max-width: 400px; margin: 80px auto; }</style>
</head>
<body>
    <div class="container">
        <h4 class="mb-4 text-center">Recuperar Contraseña</h4>
        <?php require_once '../modelo/config.php'; ?>
        <form action="<?= AppConfig::vistaUrl('enviarEnlace.php') ?>" method="POST">
            <div class="mb-3">
                <label>Correo registrado</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
        </form>
    </div>
</body>
</html>