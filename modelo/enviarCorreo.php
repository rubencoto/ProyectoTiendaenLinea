<?php
// Verificar si PHPMailer está disponible usando Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Si no hay Composer, intentar incluir PHPMailer manualmente
    if (file_exists('C:/Users/leomo/OneDrive/Desktop/PHPMailer-master/PHPMailer-master/src/PHPMailer.php')) {
        require_once 'C:/Users/leomo/OneDrive/Desktop/PHPMailer-master/PHPMailer-master/src/Exception.php';
        require_once 'C:/Users/leomo/OneDrive/Desktop/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
        require_once 'C:/Users/leomo/OneDrive/Desktop/PHPMailer-master/PHPMailer-master/src/SMTP.php';
    } else {
        // Fallback para otros usuarios
        if (file_exists('C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/PHPMailer.php')) {
            require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/Exception.php';
            require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/PHPMailer.php';
            require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/SMTP.php';
        } else {
            error_log('PHPMailer no encontrado en ninguna ubicación conocida.');
            return false;
        }
    }
}

// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function enviarCorreoVerificacion($correoDestino, $codigo) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'serviciocontactoventaonline@gmail.com';
        $mail->Password   = 'hbon bfqz wroe bmzm';
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

// Función general para enviar correos
function enviarCorreo($correoDestino, $asunto, $mensaje) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'serviciocontactoventaonline@gmail.com';
        $mail->Password   = 'hbon bfqz wroe bmzm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('serviciocontactoventaonline@gmail.com', 'Tienda en Línea');
        $mail->addAddress($correoDestino);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
?>
