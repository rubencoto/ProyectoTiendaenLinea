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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 600px;
            margin: auto;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            border: none;
            padding: 12px 0;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Crear Cuenta de Cliente</h2>
        </div>
        <div class="card-body p-4">
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
                    <div class="col-md-12 mb-3">
                        <label>Número de Teléfono *</label>
                        <input type="tel" name="telefono" class="form-control" required pattern="[0-9]{8,15}" 
                               placeholder="Ejemplo: 88887777">
                        <div class="form-text">Ingrese su número de teléfono sin espacios ni guiones</div>
                    </div>
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

                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Podrá agregar su dirección de envío más tarde en su perfil o durante el proceso de compra.
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">Crear Cuenta</button>
                
                <div class="text-center mt-3">
                    <p>¿Ya tienes una cuenta? <a href="<?= AppConfig::link('loginCliente.php') ?>" class="text-decoration-none">Iniciar Sesión</a></p>
                </div>
            </form>
        </div>
    </div>
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
