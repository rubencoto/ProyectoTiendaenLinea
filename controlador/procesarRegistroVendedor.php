<?php
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';

// Recolectar datos del formulario
$nombre_empresa = $_POST['nombre'] ?? ''; // Map to nombre_empresa
$correo = $_POST['correo'] ?? '';
$contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
$telefono = $_POST['telefono'] ?? '';
$direccion = $_POST['direccion1'] ?? ''; // Use direccion for the main address
$categoria = $_POST['categoria'] ?? '';
$cedula_juridica = $_POST['cedula_juridica'] ?? '';

// Validar campos requeridos
if (!$nombre || !$correo || !$contrasena) {
    die('Faltan datos requeridos.');
}

// Manejo de logo
$logo_binario = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $logo_binario = file_get_contents($_FILES['logo']['tmp_name']);
}

// Generar código de verificación (6 dígitos)
$codigo_verificacion = substr(bin2hex(random_bytes(4)), 0, 6);

// Insertar vendedor (usando nombre_empresa)
$sql = "INSERT INTO vendedores (
    nombre_empresa, correo, contrasena, telefono,
    direccion, categoria, codigo_verificacion, verificado
) VALUES (?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'sssssss', // 7 's' correspond to 7 placeholders
    $nombre_empresa,
    $correo,
    $contrasena,
    $telefono,
    $direccion,
    $categoria,
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
header("Location: ../vista/mensajeRegistro.php?correo=" . urlencode($correo));
exit;
?>
