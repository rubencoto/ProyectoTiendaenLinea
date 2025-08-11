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
    } elseif (file_exists('C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/PHPMailer.php')) {
        require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/Exception.php';
        require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/PHPMailer.php';
        require_once 'C:/Users/ruben/OneDrive/Desktop/correo/PHPMailer-master/src/SMTP.php';
    } elseif (file_exists('C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/PHPMailer.php')) {
        require_once 'C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/Exception.php';
        require_once 'C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/PHPMailer.php';
        require_once 'C:/Users/iannc/OneDrive/Projects/Escritorio/PHPMailer-master/src/SMTP.php';
    } else {
        error_log('PHPMailer no encontrado en ninguna ubicación conocida.');
        return false;
    }
}

// Asegurarse de que las clases de PHPMailer estén disponibles
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

        $mail->setFrom('serviciocontactoventaonline@gmail.com', 'Tienda en Linea');
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

function enviarNotificacionCambioEstado($correoDestino, $nombreCliente, $numeroOrden, $nuevoEstado, $nombreEmpresa) {
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
        $mail->addAddress($correoDestino, $nombreCliente);

        $mail->isHTML(true);
        $mail->Subject = "Actualización de tu pedido #$numeroOrden";
        
        $estadoTexto = '';
        switch($nuevoEstado) {
            case 'pendiente':
                $estadoTexto = 'Pendiente de procesamiento';
                break;
            case 'procesando':
                $estadoTexto = 'En procesamiento';
                break;
            case 'enviado':
                $estadoTexto = 'Enviado';
                break;
            case 'entregado':
                $estadoTexto = 'Entregado';
                break;
            case 'cancelado':
                $estadoTexto = 'Cancelado';
                break;
            default:
                $estadoTexto = $nuevoEstado;
        }
        
        $mail->Body = "
            <h3>Actualización de tu pedido</h3>
            <p>Estimado/a $nombreCliente,</p>
            <p>Te informamos que el estado de tu pedido <strong>#$numeroOrden</strong> ha sido actualizado.</p>
            <p><strong>Nuevo estado:</strong> $estadoTexto</p>
            <p><strong>Vendedor:</strong> $nombreEmpresa</p>
            <p>Gracias por tu compra.</p>
            <hr>
            <p><small>Este es un correo automático, por favor no responder.</small></p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar notificación de cambio de estado: " . $mail->ErrorInfo);
        return false;
    }
}
?>