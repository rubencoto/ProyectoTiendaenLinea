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
    $codigo_ingresado = $_POST['codigo'] ?? '';

    if (empty($token) || empty($codigo_ingresado)) {
        $response['message'] = 'Token y código son requeridos.';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("SELECT codigo_verificacion, codigo_expira FROM clientes WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($codigo_bd, $codigo_expira);
    
    if ($stmt->fetch()) {
        $stmt->close();
        $ahora = date("Y-m-d H:i:s");

        if ($codigo_bd !== $codigo_ingresado) {
            $response['message'] = "Código incorrecto.";
        } elseif ($ahora > $codigo_expira) {
            $response['message'] = "El código ha expirado.";
        } else {
            $response['success'] = true;
            $response['message'] = "Código verificado correctamente.";
            $response['redirect'] = "restablecerContrasena.php?token=" . urlencode($token);
        }
    } else {
        $stmt->close();
        $response['message'] = "Token inválido.";
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in verificarCodigoController.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo de nuevo.',
        'redirect' => ''
    ]);
}
?>
