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
        echo json_encode(['test' => 'success', 'message' => 'Vista controller is accessible']);
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
        ob_clean(); // Clear any output buffer
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

    $stmt = $conn->prepare("SELECT id, contrasena, verificado FROM vendedores WHERE correo = ?");
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta");
    }

    $stmt->execute([$correo]);
    $vendor = $stmt->fetch();

    if ($vendor) {
        $id = $vendor['id'];
        $hash = $vendor['contrasena'];
        $verificado = $vendor['verificado'];

        if (!$verificado) {
            $response['message'] = 'La cuenta aún no ha sido verificada. Por favor, verifica tu correo electrónico.';
            $response['verification_needed'] = true;
        } elseif (password_verify($contrasena, $hash)) {
            $_SESSION['id'] = $id;
            $response['success'] = true;
            $response['message'] = '¡Login exitoso! Redirigiendo...';
            $response['redirect'] = 'inicioVendedor.php';
        } else {
            $response['message'] = 'Contraseña incorrecta.';
        }
    } else {
        $response['message'] = 'Correo no registrado.';
        $response['register_suggestion'] = true;
        $response['register_url'] = 'registroVendedor.php';
    }

    ob_clean(); // Clear any output buffer before sending JSON
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in procesarLoginVendedor.php: " . $e->getMessage());
    ob_clean(); // Clear any output buffer
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo de nuevo.',
        'redirect' => ''
    ]);
}
?>
