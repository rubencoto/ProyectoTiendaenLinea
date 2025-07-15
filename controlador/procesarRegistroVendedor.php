<?php
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';

// Recolectar datos del formulario
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
$telefono = $_POST['telefono'] ?? '';
$direccion1 = $_POST['direccion1'] ?? '';
$direccion2 = $_POST['direccion2'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$cedula_juridica = $_POST['cedula_juridica'] ?? '';
$biografia = $_POST['biografia'] ?? '';
$redes = $_POST['redes'] ?? '';

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
    direccion1, direccion2, categoria, cedula_juridica,
    logo, biografia, redes, codigo_verificacion, verificado
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssssssssss', // 12 's' correspond to 12 placeholders
    $nombre,
    $correo,
    $contrasena,
    $telefono,
    $direccion1,
    $direccion2,
    $categoria,
    $cedula_juridica,
    $logo_binario,
    $biografia,
    $redes,
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
