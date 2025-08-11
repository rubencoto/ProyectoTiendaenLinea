<?php
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';
require_once '../modelo/config.php';

// Recolectar datos del formulario
$nombre_empresa = $_POST['nombre'] ?? ''; // Form sends 'nombre' for company name
$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$direccion1 = $_POST['direccion1'] ?? '';
$direccion2 = $_POST['direccion2'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$cedula_juridica = $_POST['cedula_juridica'] ?? '';

// Hash the password
if (!empty($contrasena)) {
    $contrasena = password_hash($contrasena, PASSWORD_BCRYPT);
} else {
    die('Error: Contraseña requerida.');
}

// Validar campos requeridos
if (empty($nombre_empresa) || empty($correo) || empty($contrasena)) {
    die('Error: Faltan datos requeridos. Nombre de empresa, correo y contraseña son obligatorios.');
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: " . AppConfig::vistaUrl('registroVendedor.php') . "?error=invalid_email");
    exit;
}

// Verificar si el correo ya está registrado
$check_email = "SELECT id, verificado FROM vendedores WHERE correo = ?";
$stmt_check = $conn->prepare($check_email);
$stmt_check->bind_param('s', $correo);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    $existing_vendor = $result->fetch_assoc();
    $stmt_check->close();
    
    if ($existing_vendor['verificado'] == 1) {
        // Vendor already verified - redirect to login
        header("Location: " . AppConfig::vistaUrl('registroVendedor.php') . "?error=email_exists_verified");
        exit;
    } else {
        // Vendor exists but not verified - redirect to verification
        header("Location: " . AppConfig::vistaUrl('mensajeRegistro.php') . "?correo=" . urlencode($correo) . "&pendiente=1");
        exit;
    }
}
$stmt_check->close();

// Manejo de logo
$logo_binario = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $logo_binario = file_get_contents($_FILES['logo']['tmp_name']);
}

// Generar código de verificación (6 dígitos)
$codigo_verificacion = substr(bin2hex(random_bytes(4)), 0, 6);

// Insertar nuevo vendedor - incluir campos nombre y apellido requeridos
$sql = "INSERT INTO vendedores (
    nombre, apellido, nombre_empresa, correo, contrasena, telefono, direccion1, direccion2, categoria, cedula_juridica,
    codigo_verificacion, verificado
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'sssssssssss', // 11 's' correspond to 11 placeholders
    $nombre_empresa, // Use company name as nombre
    $nombre_empresa, // Use company name as apellido
    $nombre_empresa,
    $correo,
    $contrasena,
    $telefono,
    $direccion1,
    $direccion2,
    $categoria,
    $cedula_juridica,
    $codigo_verificacion
);

if (!$stmt->execute()) {
    die('Error al registrar vendedor: ' . $stmt->error);
}

// Enviar correo con OTP
if (!enviarCorreoVerificacion($correo, $codigo_verificacion)) {
    error_log('No se pudo enviar el OTP al correo ' . $correo);
}

// Redirigir a mensaje de éxito
header("Location: " . AppConfig::vistaUrl('mensajeRegistro.php') . "?correo=" . urlencode($correo));
exit;
?>
