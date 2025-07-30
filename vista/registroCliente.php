<?php
session_start();
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente</title>
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
    <h2 class="mb-4">Registro de Cliente</h2>
    <form id="registroForm" action="<?= AppConfig::controladorUrl('procesarRegistroCliente.php') ?>" method="POST">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Apellidos *</label>
                        <input type="text" name="apellidos" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Correo Electrónico *</label>
                    <input type="email" name="correo" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Contraseña *</label>
                        <input type="password" name="contrasena" id="contrasena" class="form-control" required minlength="6">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Confirmar Contraseña *</label>
                        <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" class="form-control" required minlength="6">
                        <div id="errorContrasena" class="text-danger small"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Número de Teléfono *</label>
                        <input type="tel" name="telefono" class="form-control" required pattern="[0-9]{8,15}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Cédula *</label>
                        <input type="text" name="cedula" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Dirección *</label>
                    <input type="text" name="direccion" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Provincia *</label>
                    <select name="provincia" class="form-control" required>
                        <option value="">Seleccione una provincia</option>
                        <option value="San José">San José</option>
                        <option value="Alajuela">Alajuela</option>
                        <option value="Cartago">Cartago</option>
                        <option value="Heredia">Heredia</option>
                        <option value="Guanacaste">Guanacaste</option>
                        <option value="Puntarenas">Puntarenas</option>
                        <option value="Limón">Limón</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Género</label>
                        <select name="genero" class="form-control">
                            <option value="">Prefiero no especificar</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="terminos" id="terminos" required>
                    <label class="form-check-label" for="terminos">
                        Acepto los <a href="#">términos y condiciones</a>
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter">
                    <label class="form-check-label" for="newsletter">
                        Deseo recibir ofertas y novedades por correo electrónico
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
