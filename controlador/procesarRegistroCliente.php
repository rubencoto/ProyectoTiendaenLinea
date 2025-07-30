<?php
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';
require_once '../modelo/config.php';

// Recolectar datos del formulario
$nombre = $_POST['nombre'] ?? '';
$apellidos = $_POST['apellidos'] ?? ''; // Use apellidos since table has this column
$correo = $_POST['correo'] ?? '';
$contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
$telefono = $_POST['telefono'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$genero = $_POST['genero'] ?? null;
switch ($genero) {
    case 'Masculino':
        $genero = 'M';
        break;
    case 'Femenino':
        $genero = 'F';
        break;
    case 'Otro':
        $genero = 'O';
        break;
    default:
        $genero = null;  // or '' if you prefer empty string
    }
$newsletter = isset($_POST['newsletter']) ? 1 : 0;

// Validar campos requeridos
if (!$nombre || !$apellidos || !$correo || !$contrasena || !$telefono) {
    die('Error: Faltan datos requeridos. Por favor, completa todos los campos obligatorios.');
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die('Error: El formato del correo electrónico no es válido.');
}

// Verificar si el correo ya existe
$stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    die('Error: Ya existe una cuenta con este correo electrónico.');
}
$stmt->close();

// Generar código de verificación (6 dígitos)
$codigo_verificacion = substr(bin2hex(random_bytes(4)), 0, 6);

try {
    // Preparar e insertar nuevo cliente - solo campos esenciales para registro
    $stmt = $conn->prepare("
        INSERT INTO clientes (
            nombre, apellidos, correo, contrasena, telefono,
            fecha_nacimiento, genero, newsletter, 
            codigo_verificacion, verificado, fecha_registro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $stmt->bind_param(
        "sssssssis", 
        $nombre, $apellidos, $correo, $contrasena, $telefono,
        $fecha_nacimiento, $genero, $newsletter, $codigo_verificacion
    );


    if ($stmt->execute()) {
        $cliente_id = $conn->insert_id;
        
        // Create dynamic URL that works for both localhost and Heroku
        $verification_url = AppConfig::emailUrl('verificarCuentaCliente.php', [
            'codigo' => $codigo_verificacion,
            'correo' => $correo
        ]);

        // Enviar correo de verificación
        $asunto = "Verificación de cuenta - Tienda en Línea";
        $mensaje = "
        <h2>¡Bienvenido/a $nombre $apellidos!</h2>
        <p>Gracias por registrarte en nuestra tienda en línea.</p>
        <p>Para activar tu cuenta, usa el siguiente código de verificación:</p>
        <h3 style='background-color: #f0f0f0; padding: 10px; text-align: center; border-radius: 5px;'>$codigo_verificacion</h3>
        <p>Haz clic en el siguiente enlace para verificar tu cuenta:</p>
        <p><a href='$verification_url' 
           style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
           Verificar Cuenta
        </a></p>
        <p>Si no puedes hacer clic en el enlace, copia y pega la siguiente URL en tu navegador:</p>
        <p>$verification_url</p>
        <p><small>Este código expira en 24 horas.</small></p>
        ";

        if (enviarCorreo($correo, $asunto, $mensaje)) {
            // Redirigir a página de éxito
            header("Location: ../vista/mensajeRegistroCliente.php?exito=1");
            exit;
        } else {
            // Error al enviar correo, pero usuario registrado
            header("Location: ../vista/mensajeRegistroCliente.php?exito=1&correo_error=1");
            exit;
        }
    } else {
        throw new Exception("Error al registrar el cliente: " . $stmt->error);
    }

}  catch (Exception $e) {
    error_log("Error en registro de cliente: " . $e->getMessage());
    die('Error: Hubo un problema al procesar tu registro. Por favor, inténtalo de nuevo más tarde.<br><br>Detalle del error: ' . $e->getMessage());


} finally {
    $stmt->close();
    // Connection managed by singleton, no need to close explicitly
}
?>


