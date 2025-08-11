<?php
session_start();
header('Content-Type: application/json');

require_once '../modelo/conexion.php';

try {
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    $response = ['success' => false, 'message' => '', 'type' => 'error', 'step' => ''];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido.';
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'verify_code':
            $correo = $_POST['correo'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            
            if (empty($correo) || empty($codigo)) {
                $response['message'] = 'Correo y código son requeridos.';
                break;
            }
            
            // Clean the entered code
            $codigo_limpio = preg_replace('/[^0-9]/', '', trim($codigo));
            
            // Verify the code
            $stmt = $conn->prepare("SELECT id, codigo_verificacion FROM clientes WHERE correo = ?");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows === 1) {
                $row = $resultado->fetch_assoc();
                $codigo_db = preg_replace('/[^0-9]/', '', trim($row['codigo_verificacion']));
                
                // Compare clean codes
                if ($codigo_limpio === $codigo_db && !empty($codigo_limpio)) {
                    $response['success'] = true;
                    $response['message'] = 'Código verificado correctamente. Ahora puedes establecer tu nueva contraseña.';
                    $response['type'] = 'success';
                    $response['step'] = 'password_reset';
                    
                    // Save code in session for next step
                    $_SESSION['codigo_verificado'] = $codigo_limpio;
                    $_SESSION['correo_reset'] = $correo;
                } else {
                    $response['message'] = 'Código de verificación inválido. Revisa el código en tu correo e inténtalo de nuevo.';
                }
            } else {
                $response['message'] = 'No se encontró una cuenta con este correo electrónico.';
            }
            break;

        case 'reset_password':
            $correo = $_POST['correo'] ?? '';
            $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
            $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
            
            // Verify session data
            if (!isset($_SESSION['codigo_verificado']) || !isset($_SESSION['correo_reset'])) {
                $response['message'] = 'Sesión expirada. Por favor, verifica el código nuevamente.';
                $response['step'] = 'verify_code';
                break;
            }
            
            if ($_SESSION['correo_reset'] !== $correo) {
                $response['message'] = 'Error en la verificación del correo.';
                $response['step'] = 'verify_code';
                break;
            }
            
            if (empty($nueva_contrasena) || empty($confirmar_contrasena)) {
                $response['message'] = 'Por favor, completa todos los campos de contraseña.';
                $response['step'] = 'password_reset';
                break;
            }
            
            if ($nueva_contrasena !== $confirmar_contrasena) {
                $response['message'] = 'Las contraseñas no coinciden.';
                $response['step'] = 'password_reset';
                break;
            }
            
            if (strlen($nueva_contrasena) < 6) {
                $response['message'] = 'La contraseña debe tener al menos 6 caracteres.';
                $response['step'] = 'password_reset';
                break;
            }
            
            // Verify code again for security
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE correo = ? AND codigo_verificacion = ?");
            $stmt->bind_param("s", $correo, $_SESSION['codigo_verificado']);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows === 1) {
                $row = $resultado->fetch_assoc();
                $cliente_id = $row['id'];
                
                // Update password and clear verification code
                $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE clientes SET contrasena = ?, codigo_verificacion = NULL WHERE id = ?");
                $stmt_update->bind_param("si", $nueva_contrasena_hash, $cliente_id);
                
                if ($stmt_update->execute()) {
                    // Clear session data
                    unset($_SESSION['codigo_verificado']);
                    unset($_SESSION['correo_reset']);
                    
                    $response['success'] = true;
                    $response['message'] = '¡Contraseña restablecida exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.';
                    $response['type'] = 'success';
                    $response['step'] = 'complete';
                    $response['redirect'] = 'loginCliente.php';
                } else {
                    $response['message'] = 'Error al actualizar la contraseña. Inténtalo de nuevo.';
                    $response['step'] = 'password_reset';
                }
            } else {
                $response['message'] = 'Código de verificación inválido o expirado.';
                $response['step'] = 'verify_code';
                
                // Clear invalid session data
                unset($_SESSION['codigo_verificado']);
                unset($_SESSION['correo_reset']);
            }
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in verificarCodigoResetController.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo de nuevo.',
        'type' => 'error',
        'step' => ''
    ]);
}
?>
