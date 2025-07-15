<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cambia la ruta a la ubicación real de PHPMailer:
require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/Exception.php';
require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/PHPMailer.php';
require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/SMTP.php';

function enviarCorreoVerificacion($correoDestino, $codigo) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'serviciocontactoventaonline@gmail.com'; // Cambia por tu correo
        $mail->Password   = 'hbon bfqz wroe bmzm'; // Cambia por tu contraseña de app
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('serviciocontactoventaonline@gmail.com', 'Registro Emprendedores');
        $mail->addAddress($correoDestino);

        $mail->isHTML(true);
        $mail->Subject = 'Código de verificación';
        $mail->Body    = "<h3>Gracias por registrarte.</h3><p>Tu código de verificación es: <strong>$codigo</strong></p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
