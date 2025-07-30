<?php
session_start();
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 700px;
            margin: auto;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Registro de Emprendedor</h2>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> 
            <?php if ($_GET['error'] === 'email_exists_verified'): ?>
                <strong>Error:</strong> Este correo electrónico ya está registrado y verificado. 
                <a href="<?= AppConfig::vistaUrl('loginVendedor.php') ?>" class="alert-link">Inicia sesión aquí</a>
            <?php elseif ($_GET['error'] === 'invalid_email'): ?>
                <strong>Error:</strong> El formato del correo electrónico no es válido. Por favor, verifica e intenta nuevamente.
            <?php else: ?>
                <strong>Error:</strong> Este correo electrónico ya está registrado. 
                <a href="<?= AppConfig::vistaUrl('loginVendedor.php') ?>" class="alert-link">¿Ya tienes cuenta? Inicia sesión aquí</a>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <form id="registroForm" action="<?= AppConfig::controladorUrl('procesarRegistroVendedor.php') ?>" method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label>Nombre de la Empresa *</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Correo Electrónico *</label>
            <input type="email" name="correo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Contraseña *</label>
            <input type="password" name="contrasena" id="contrasena" class="form-control" required minlength="6">
        </div>

        <div class="mb-3">
            <label>Confirmar Contraseña *</label>
            <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" class="form-control" required minlength="6">
            <div id="errorContrasena" class="text-danger small"></div>
        </div>

        <div class="mb-3">
            <label>Número de Teléfono *</label>
            <input type="tel" name="telefono" class="form-control" required pattern="[0-9]{8,15}">
        </div>

        <div class="mb-3">
            <label>Dirección Línea 1 *</label>
            <input type="text" name="direccion1" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Dirección Línea 2</label>
            <input type="text" name="direccion2" class="form-control">
        </div>

        <div class="mb-3">
            <label>Categoría de Negocio *</label>
            <input type="text" name="categoria" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Cédula Jurídica *</label>
            <input type="text" name="cedula_juridica" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Logo de la Empresa *</label>
            <input type="file" name="logo" class="form-control" accept="image/*" required>
        </div>

        <div class="mb-3">
            <label>Descripción breve o Biografía *</label>
            <textarea name="biografia" class="form-control" required rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label>Redes Sociales</label>
            <input type="text" name="redes" class="form-control">
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="terminos" id="terminos" required>
            <label class="form-check-label" for="terminos">
                Acepto los <a href="#">términos y condiciones</a>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>
</div>

<script>
    // Validación JS: Contraseñas coincidan
    document.getElementById('registroForm').addEventListener('submit', function(event) {
        const pass = document.getElementById('contrasena').value;
        const confirm = document.getElementById('confirmar_contrasena').value;
        const errorMsg = document.getElementById('errorContrasena');

        if (pass !== confirm) {
            errorMsg.textContent = "Las contraseñas no coinciden.";
            event.preventDefault();
        } else {
            errorMsg.textContent = "";
        }
    });
</script>
</body>
</html>
