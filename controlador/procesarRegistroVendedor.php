<?php
require_once '../modelo/conexion.php';
require_once '../modelo/enviarCorreo.php';

// 🔍 Detectar si es petición AJAX
$esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// 📧 Verificación de email (petición AJAX separada)
if (isset($_POST['verificar_email'])) {
    header('Content-Type: application/json');
    $correo = $_POST['correo'] ?? '';
    
    if (!$correo) {
        echo json_encode(['success' => false, 'error' => 'Email requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM vendedores WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Este correo ya está registrado.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Email disponible']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

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
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos.']);
        exit;
    }
    die('Faltan datos requeridos.');
}

// Verificar si el correo ya existe
$stmt = $conn->prepare("SELECT id FROM vendedores WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Este correo ya está registrado.']);
        exit;
    }
    die('Este correo ya está registrado.');
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
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error al registrar vendedor: ' . $stmt->error]);
        exit;
    }
    die('Error al registrar vendedor: ' . $stmt->error);
}

// Enviar correo con OTP
$correoEnviado = enviarCorreoVerificacion($correo, $codigo_verificacion);
if (!$correoEnviado) {
    error_log('No se pudo enviar el OTP al correo ' . $correo);
}

// 🚀 Respuesta según el tipo de petición
if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '¡Registro exitoso! Se ha enviado un código de verificación a tu correo.',
        'correo' => $correo,
        'correo_enviado' => $correoEnviado
    ]);
} else {
    // Redirigir a mensaje de éxito (comportamiento original)
    header("Location: ../vista/mensajeRegistro.php?correo=" . urlencode($correo));
}
exit;
?>
