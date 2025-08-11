<?php
// Turn off error reporting to prevent HTML error output
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Ensure we output JSON even if there are PHP notices/warnings
ob_start();

header('Content-Type: application/json');

try {
    // Simple test endpoint
    if (isset($_GET['test'])) {
        ob_clean();
        echo json_encode(['test' => 'success', 'message' => 'Cliente controller is accessible']);
        exit;
    }
    
    require_once '../modelo/conexion.php';
    require_once '../modelo/config.php';

    // Get database connection
    $db = DatabaseConnection::getInstance();
    
    if (!$db || !$db->isConnected()) {
        throw new Exception("No se pudo establecer conexión con la base de datos");
    }
    
    $conn = $db->getConnection();

    $response = ['success' => false, 'message' => '', 'redirect' => ''];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido.';
        ob_clean();
        echo json_encode($response);
        exit;
    }

    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($correo) || empty($contrasena)) {
        $response['message'] = 'Por favor, completa todos los campos.';
        ob_clean();
        echo json_encode($response);
        exit;
    }

    // Validate email format
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Formato de correo electrónico inválido.';
        ob_clean();
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, contrasena, verificado FROM clientes WHERE correo = ?");
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta");
    }

    $stmt->execute([$correo]);
    $result = $stmt->fetch();

    if ($result) {
        $id = $result['id'];
        $hash = $result['contrasena'];
        $verificado = $result['verificado'];

        if (!$verificado) {
            $response['message'] = 'La cuenta aún no ha sido verificada. Por favor, verifica tu correo electrónico.';
            $response['verification_needed'] = true;
            $response['redirect'] = 'verificarCuentaCliente.php?correo=' . urlencode($correo);
        } elseif (password_verify($contrasena, $hash)) {
            $_SESSION['cliente_id'] = $id;
            $response['success'] = true;
            $response['message'] = '¡Login exitoso! Redirigiendo...';
            $response['redirect'] = AppConfig::link('index.php');
        } else {
            $response['message'] = 'Contraseña incorrecta.';
        }
    } else {
        $response['message'] = 'Correo no registrado.';
        $response['register_suggestion'] = true;
        $response['register_url'] = 'registroCliente.php';
    }

    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in procesarLoginCliente.php: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo de nuevo.',
        'redirect' => ''
    ]);
}
?>
