<?php
require_once '../modelo/conexion.php';

// Incluir PHPMailer (ajusta ruta)
if (file_exists('C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/PHPMailer.php')) {
    require_once 'C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/Exception.php';
    require_once 'C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/PHPMailer.php';
    require_once 'C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/SMTP.php';
} else {
    die("PHPMailer no encontrado. Ajusta las rutas.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function obtenerUrlNgrok() {
    $apiUrl = 'http://127.0.0.1:4040/api/tunnels';
    $json = @file_get_contents($apiUrl);
    if ($json === false) return false;
    $data = json_decode($json, true);
    if (!isset($data['tunnels'][0]['public_url'])) return false;
    return $data['tunnels'][0]['public_url'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];

    $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $token = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $stmt->bind_result($id);
        $stmt->fetch();

        $codigo_verificacion = random_int(100000, 999999);
        $codigo_expira = date("Y-m-d H:i:s", strtotime('+10 minutes'));

        $update = $conn->prepare("UPDATE clientes SET reset_token = ?, token_expira = ?, codigo_verificacion = ?, codigo_expira = ? WHERE correo = ?");
        $update->bind_param("sssss", $token, $expira, $codigo_verificacion, $codigo_expira, $correo);
        $update->execute();

        $baseUrl = obtenerUrlNgrok() ?: "http://localhost";
        $enlace = $baseUrl . "/ProyectoTiendaenLinea-main/vista/verificarCodigo.php?token=$token";

        // Enviar correo con PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ianncalcha@gmail.com';  // tu correo
            $mail->Password = 'acrgqbqviyisuqol';     // contraseña de app
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('ianncalcha@gmail.com', 'Tu Tienda Online');
            $mail->addAddress($correo);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña';
            $mail->Body = "
                <p>Hola,</p>
                <p>Has solicitado restablecer tu contraseña. Haz clic en el enlace siguiente para continuar:</p>
                <p><a href='$enlace'>$enlace</a></p>
                <p>Tu código de verificación es: <b>$codigo_verificacion</b></p>
                <p>Este enlace y código expirarán pronto.</p>
                <p>Si no solicitaste este cambio, ignora este correo.</p>
            ";

            $mail->send();

            echo "<p style='color:green; text-align:center;'>Correo enviado correctamente. Revisa tu bandeja.</p>";
            echo "<p style='text-align:center;'><a href='loginCliente.php'>Volver al login</a></p>";

        } catch (Exception $e) {
            echo "<p style='color:red; text-align:center;'> Error al enviar el correo: {$mail->ErrorInfo}</p>";
            echo "<p style='text-align:center;'><a href='recuperarContrasena.php'>Intentar de nuevo</a></p>";
        }

    } else {
        echo "<p style='color:red; text-align:center;'>El correo no está registrado. <a href='recuperarContrasena.php'>Intentar de nuevo</a></p>";
    }

    $stmt->close();
    $conn->close();
}
?>













