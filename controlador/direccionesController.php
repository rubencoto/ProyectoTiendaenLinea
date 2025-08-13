<?php
session_start();

// Verificar autenticación del cliente
if (empty($_SESSION['cliente_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Sesión expirada. Por favor inicia sesión nuevamente.',
        'redirect' => '../vista/loginCliente.php'
    ]);
    exit;
}

require_once '../modelo/DireccionesManager.php';

$cliente_id = $_SESSION['cliente_id'];
$direccionesManager = new DireccionesManager();

// Solo permitir métodos POST para modificaciones
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'listar':
            $direcciones = $direccionesManager->obtenerDireccionesCliente($cliente_id);
            echo json_encode([
                'success' => true,
                'data' => $direcciones
            ]);
            break;
            
        case 'obtener':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de dirección inválido');
            }
            
            $direccion = $direccionesManager->obtenerDireccionPorId($id, $cliente_id);
            if (!$direccion) {
                throw new Exception('Dirección no encontrada');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $direccion
            ]);
            break;
            
        case 'crear':
            // Validar datos requeridos
            $datos = [
                'cliente_id' => $cliente_id,
                'etiqueta' => $_POST['etiqueta'] ?? '',
                'nombre' => $_POST['nombre'] ?? '',
                'apellidos' => $_POST['apellidos'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'codigo_postal' => $_POST['codigo_postal'] ?? '',
                'linea1' => $_POST['linea1'] ?? '',
                'linea2' => $_POST['linea2'] ?? '',
                'provincia' => $_POST['provincia'] ?? '',
                'canton' => $_POST['canton'] ?? '',
                'distrito' => $_POST['distrito'] ?? '',
                'referencia' => $_POST['referencia'] ?? '',
                'is_default' => isset($_POST['is_default']) ? 1 : 0
            ];
            
            // Validar datos
            $errores = $direccionesManager->validarDatosDireccion($datos);
            if (!empty($errores)) {
                throw new Exception(implode('. ', $errores));
            }
            
            // Si es la primera dirección, marcarla como principal automáticamente
            $count = $direccionesManager->contarDireccionesCliente($cliente_id);
            if ($count == 0) {
                $datos['is_default'] = 1;
            }
            
            $resultado = $direccionesManager->agregarDireccionCliente($datos);
            if (!$resultado) {
                throw new Exception('Error al crear la dirección');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Dirección creada exitosamente'
            ]);
            break;
            
        case 'actualizar':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de dirección inválido');
            }
            
            // Verificar que la dirección pertenece al cliente
            $direccionExistente = $direccionesManager->obtenerDireccionPorId($id, $cliente_id);
            if (!$direccionExistente) {
                throw new Exception('Dirección no encontrada');
            }
            
            $datos = [
                'cliente_id' => $cliente_id,
                'etiqueta' => $_POST['etiqueta'] ?? '',
                'nombre' => $_POST['nombre'] ?? '',
                'apellidos' => $_POST['apellidos'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'codigo_postal' => $_POST['codigo_postal'] ?? '',
                'linea1' => $_POST['linea1'] ?? '',
                'linea2' => $_POST['linea2'] ?? '',
                'provincia' => $_POST['provincia'] ?? '',
                'canton' => $_POST['canton'] ?? '',
                'distrito' => $_POST['distrito'] ?? '',
                'referencia' => $_POST['referencia'] ?? '',
                'is_default' => isset($_POST['is_default']) ? 1 : 0
            ];
            
            // Validar datos
            $errores = $direccionesManager->validarDatosDireccion($datos);
            if (!empty($errores)) {
                throw new Exception(implode('. ', $errores));
            }
            
            $resultado = $direccionesManager->actualizarDireccionCliente($id, $datos);
            if (!$resultado) {
                throw new Exception('Error al actualizar la dirección');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Dirección actualizada exitosamente'
            ]);
            break;
            
        case 'eliminar':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de dirección inválido');
            }
            
            // Verificar que la dirección pertenece al cliente
            $direccion = $direccionesManager->obtenerDireccionPorId($id, $cliente_id);
            if (!$direccion) {
                throw new Exception('Dirección no encontrada');
            }
            
            // No permitir eliminar si es la única dirección y es principal
            $count = $direccionesManager->contarDireccionesCliente($cliente_id);
            if ($count == 1 && $direccion['is_default']) {
                throw new Exception('No puedes eliminar tu única dirección principal. Agrega otra dirección primero.');
            }
            
            $resultado = $direccionesManager->eliminarDireccionCliente($id, $cliente_id);
            if (!$resultado) {
                throw new Exception('Error al eliminar la dirección');
            }
            
            // Si se eliminó la dirección principal, marcar otra como principal automáticamente
            if ($direccion['is_default']) {
                $direcciones = $direccionesManager->obtenerDireccionesCliente($cliente_id);
                if (!empty($direcciones)) {
                    $direccionesManager->establecerDireccionPrincipalCliente($direcciones[0]['id'], $cliente_id);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Dirección eliminada exitosamente'
            ]);
            break;
            
        case 'establecer_principal':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de dirección inválido');
            }
            
            // Verificar que la dirección pertenece al cliente
            $direccion = $direccionesManager->obtenerDireccionPorId($id, $cliente_id);
            if (!$direccion) {
                throw new Exception('Dirección no encontrada');
            }
            
            $resultado = $direccionesManager->establecerDireccionPrincipalCliente($id, $cliente_id);
            if (!$resultado) {
                throw new Exception('Error al establecer dirección principal');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Dirección principal actualizada'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en direccionesController: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
