<?php
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';
require_once '../modelo/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];

    $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ?");
    $stmt->execute([$correo]);
    $result = $stmt->fetch();

    if ($result) {
        $id = $result['id'];

        $codigo_verificacion = random_int(100000, 999999);

        // Usar solo la columna codigo_verificacion existente para el reset
        $codigo_str = (string)$codigo_verificacion;
        $update = $conn->prepare("UPDATE clientes SET codigo_verificacion = ? WHERE correo = ?");
        $updateResult = $update->execute([$codigo_str, $correo]);
        
        // Debug: verificar que se guardó correctamente
        if ($result) {
            error_log("Código guardado correctamente: $codigo_str para correo: $correo");
        } else {
            error_log("Error al guardar código: " . $update->error);
        }

        // Use centralized configuration for URL generation  
        $enlace = AppConfig::emailUrl('verificarCodigoReset.php', ['correo' => $correo]);

        // Preparar el mensaje del correo
        $asunto = "Recuperacion de contrasena - Tu Tienda Online";
        $mensaje = "
            <h2 style='color: #007185;'>Recuperacion de Contrasena</h2>
            <p>Hola,</p>
            <p>Has solicitado restablecer tu contrasena. Haz clic en el enlace siguiente para continuar:</p>
            <p><a href='$enlace' style='background-color: #007185; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Restablecer Contrasena</a></p>
            <p><strong>Tu codigo de verificacion es: $codigo_verificacion</strong></p>
            <p><em>Este enlace y codigo expiraran en 10 minutos.</em></p>
            <p>Si no solicitaste este cambio, ignora este correo.</p>
            <hr>
            <p><small>Equipo de Tu Tienda Online</small></p>
        ";

        // Enviar correo usando la función existente
        if (enviarCorreo($correo, $asunto, $mensaje)) {
            echo "<div style='max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;'>";
            echo "<h2 style='color: #28a745;'>Correo Enviado</h2>";
            echo "<p>Hemos enviado un código de verificación a tu correo.</p>";
            echo "<p><strong>Haz clic en el botón de abajo para ingresar el código:</strong></p>";
            echo "<a href='$enlace' style='background-color: #007185; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; margin: 20px 0;'>Ingresar Código de Verificación</a>";
            echo "<p style='margin-top: 20px;'><a href='loginCliente.php'>Volver al login</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color:red; text-align:center;'>Error al enviar el correo. Intentalo de nuevo.</p>";
            echo "<p style='text-align:center;'><a href='recuperarContrasena.php'>Intentar de nuevo</a></p>";
        }

    } else {
        echo "<p style='color:red; text-align:center;'>El correo no está registrado. <a href='recuperarContrasena.php'>Intentar de nuevo</a></p>";
    }

    $stmt->close();
    // Connection managed by singleton, no need to close explicitly
}
?>