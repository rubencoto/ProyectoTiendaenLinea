<?php
session_start();
require_once '../modelo/conexion.php';
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
    <form id="registroForm" action="../controlador/procesarRegistroVendedor.php" method="POST" enctype="multipart/form-data">

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

        <button type="submit" class="btn btn-primary" id="btnSubmit">Registrarse</button>
    </form>
    
    <!-- 🔔 Área para mensajes -->
    <div id="mensajeResultado" class="mt-3"></div>
</div>

<script>
    // 🚀 AJAX para registro sin recarga de página
    document.getElementById('registroForm').addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // Validar contraseñas
        const pass = document.getElementById('contrasena').value;
        const confirm = document.getElementById('confirmar_contrasena').value;
        const errorMsg = document.getElementById('errorContrasena');

        if (pass !== confirm) {
            errorMsg.textContent = "Las contraseñas no coinciden.";
            return;
        } else {
            errorMsg.textContent = "";
        }
        
        // 🔄 Mostrar estado de carga
        const btnSubmit = document.getElementById('btnSubmit');
        const originalText = btnSubmit.textContent;
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Registrando...';
        
        try {
            const formData = new FormData(this);
            
            const response = await fetch('../controlador/procesarRegistroVendedor.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // 🎯 Indica que es AJAX
                }
            });
            
            const result = await response.json();
            
            const mensajeDiv = document.getElementById('mensajeResultado');
            
            if (result.success) {
                // ✅ Éxito
                mensajeDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5>🎉 ${result.message}</h5>
                        <p>Se ha enviado el código de verificación a: <strong>${result.correo}</strong></p>
                        <a href="verificarCuenta.php?correo=${encodeURIComponent(result.correo)}" 
                           class="btn btn-primary">Verificar Cuenta Ahora</a>
                    </div>
                `;
                
                // Limpiar formulario
                this.reset();
                
                // Scroll al mensaje
                mensajeDiv.scrollIntoView({ behavior: 'smooth' });
                
            } else {
                // ❌ Error
                mensajeDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error:</strong> ${result.error}
                    </div>
                `;
            }
            
        } catch (error) {
            // 🚨 Error de conexión
            document.getElementById('mensajeResultado').innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> No se pudo conectar con el servidor. Inténtalo de nuevo.
                </div>
            `;
            console.error('Error:', error);
        } finally {
            // 🔄 Restaurar botón
            btnSubmit.disabled = false;
            btnSubmit.textContent = originalText;
        }
    });
    
    // 📧 Validación de email en tiempo real
    document.querySelector('input[name="correo"]').addEventListener('blur', async function() {
        const email = this.value;
        
        if (email && this.checkValidity()) {
            try {
                const response = await fetch('../controlador/procesarRegistroVendedor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `verificar_email=1&correo=${encodeURIComponent(email)}`
                });
                
                const result = await response.json();
                
                if (!result.success && result.error.includes('ya está registrado')) {
                    this.setCustomValidity('Este correo ya está registrado');
                    this.reportValidity();
                } else {
                    this.setCustomValidity('');
                }
            } catch (error) {
                console.warn('Error al verificar email:', error);
            }
        }
    });
</script>
</body>
</html>
