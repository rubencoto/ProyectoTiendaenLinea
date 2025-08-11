<?php require_once '../modelo/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Exitoso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center p-5">
                    <?php if (isset($_GET['pendiente']) && $_GET['pendiente'] === '1'): ?>
                        <i class="fas fa-clock text-warning fa-3x mb-3"></i>
                        <h3 class="text-warning">Verificación Pendiente</h3>
                        <p class="mb-4">Ya tienes una cuenta registrada con este correo, pero aún no está verificada. Te hemos reenviado el código de verificación.</p>
                    <?php else: ?>
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h3 class="text-success">¡Registro Exitoso!</h3>
                        <p class="mb-4">Se ha enviado un código de verificación a tu correo electrónico.</p>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['correo'])): ?>
                        <p class="text-muted"><strong>Correo:</strong> <?= htmlspecialchars($_GET['correo']) ?></p>
                    <?php endif; ?>
                    
                    <a href="<?= AppConfig::vistaUrl('verificarCuenta.php') ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-shield-alt"></i> Verificar Cuenta
                    </a>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            ¿No recibiste el código? Revisa tu carpeta de spam o
                            <a href="<?= AppConfig::vistaUrl('registroVendedor.php') ?>" class="text-decoration-none">intenta registrarte nuevamente</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
