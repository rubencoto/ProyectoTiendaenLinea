<?php
header('Content-Type: application/json');

require_once '../modelo/conexion.php';

try {
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    $response = ['success' => false, 'message' => '', 'redirect' => ''];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido.';
        echo json_encode($response);
        exit;
    }

    $token = $_POST['token'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    if (empty($token)) {
        $response['message'] = 'Token no proporcionado.';
        echo json_encode($response);
        exit;
    }

    // Verify valid and non-expired token
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE reset_token = ? AND token_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 1) {
        $response['message'] = 'Enlace inválido o expirado.';
        echo json_encode($response);
        exit;
    }

    if (empty($nueva_contrasena) || empty($confirmar_contrasena)) {
        $response['message'] = 'Por favor, completa todos los campos.';
        echo json_encode($response);
        exit;
    }

    if ($nueva_contrasena !== $confirmar_contrasena) {
        $response['message'] = 'Las contraseñas no coinciden.';
        echo json_encode($response);
        exit;
    }

    if (strlen($nueva_contrasena) < 6) {
        $response['message'] = 'La contraseña debe tener al menos 6 caracteres.';
        echo json_encode($response);
        exit;
    }

    $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE clientes SET contrasena = ?, reset_token = NULL, token_expira = NULL, codigo_verificacion = NULL, codigo_expira = NULL WHERE reset_token = ?");
    $update->bind_param("ss", $hash, $token);

    if ($update->execute()) {
        $response['success'] = true;
        $response['message'] = '¡Contraseña actualizada correctamente! Ya puedes iniciar sesión.';
        $response['redirect'] = 'loginCliente.php';
    } else {
        $response['message'] = 'Error al actualizar la contraseña. Inténtalo de nuevo.';
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in restablecerContrasenaController.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo de nuevo.',
        'redirect' => ''
    ]);
}
?>
