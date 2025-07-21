<?php
<<<<<<< HEAD
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cambia la ruta a la ubicación real de PHPMailer:
require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/Exception.php';
require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/PHPMailer.php';
require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/SMTP.php';

function enviarCorreoVerificacion($correoDestino, $codigo) {
    $mail = new PHPMailer(true);
=======
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
        die('PHPMailer no encontrado. Por favor instala PHPMailer usando Composer o verifica la ruta manual.');
    }
}

// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function enviarCorreoVerificacion($correoDestino, $codigo) {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new PHPMailer(true);
    } else {
        $mail = new PHPMailer(true);
    }
>>>>>>> e608ed9 (Updated project files with latest changes)

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
