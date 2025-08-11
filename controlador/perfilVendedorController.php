<?php
/**
 * Controlador para gestión de perfil de vendedores
 * Maneja actualización de datos del vendedor
 */

session_start();

// Verificar autenticación del vendedor
if (!isset($_SESSION['vendedor_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Sesión expirada. Por favor inicia sesión nuevamente.',
        'redirect' => 'loginVendedor.php'
    ]);
    exit;
}

require_once '../modelo/conexion.php';

$vendedor_id = $_SESSION['vendedor_id'];

// Solo permitir método POST para actualizaciones
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Obtener datos del formulario
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion1 = trim($_POST['direccion1'] ?? '');
    $direccion2 = trim($_POST['direccion2'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $cedula_juridica = trim($_POST['cedula_juridica'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $redes = trim($_POST['redes'] ?? '');
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre_empresa)) {
        $errores[] = "El nombre de la empresa es obligatorio";
    } elseif (strlen($nombre_empresa) < 2) {
        $errores[] = "El nombre de empresa debe tener al menos 2 caracteres";
    } elseif (strlen($nombre_empresa) > 100) {
        $errores[] = "El nombre de empresa no puede tener más de 100 caracteres";
    }
    
    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio";
    } elseif (!preg_match('/^[0-9]{8}$/', $telefono)) {
        $errores[] = "El teléfono debe tener exactamente 8 dígitos";
    }
    
    if (empty($categoria)) {
        $errores[] = "La categoría es obligatoria";
    }
    
    if (empty($cedula_juridica)) {
        $errores[] = "La cédula jurídica es obligatoria";
    } elseif (!preg_match('/^[0-9\-]{9,12}$/', $cedula_juridica)) {
        $errores[] = "La cédula jurídica no tiene un formato válido";
    }
    
    if (empty($biografia)) {
        $errores[] = "La biografía es obligatoria";
    } elseif (strlen($biografia) < 10) {
        $errores[] = "La biografía debe tener al menos 10 caracteres";
    } elseif (strlen($biografia) > 1000) {
        $errores[] = "La biografía no puede tener más de 1000 caracteres";
    }
    
    // Validar dirección1 si se proporciona
    if (!empty($direccion1) && strlen($direccion1) > 255) {
        $errores[] = "La dirección 1 no puede tener más de 255 caracteres";
    }
    
    // Validar dirección2 si se proporciona
    if (!empty($direccion2) && strlen($direccion2) > 255) {
        $errores[] = "La dirección 2 no puede tener más de 255 caracteres";
    }
    
    // Validar redes sociales si se proporcionan
    if (!empty($redes) && strlen($redes) > 500) {
        $errores[] = "El campo de redes sociales no puede tener más de 500 caracteres";
    }
    
    // Verificar si hay errores de validación
    if (!empty($errores)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => implode('<br>', $errores),
            'errors' => $errores
        ]);
        exit;
    }
    
    // Verificar que el vendedor existe
    $stmt_check = $conn->prepare("SELECT id FROM vendedores WHERE id = ?");
    $stmt_check->execute([$vendedor_id]);
    
    if (!$stmt_check->fetch()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Vendedor no encontrado',
            'redirect' => 'loginVendedor.php'
        ]);
        exit;
    }
    
    // Actualizar información del vendedor
    $stmt_update = $conn->prepare("
        UPDATE vendedores SET 
            nombre_empresa = ?, telefono = ?, direccion1 = ?, direccion2 = ?, 
            categoria = ?, cedula_juridica = ?, biografia = ?, redes = ?
        WHERE id = ?
    ");
    
    $executed = $stmt_update->execute([
        $nombre_empresa, $telefono, $direccion1, $direccion2,
        $categoria, $cedula_juridica, $biografia, $redes, $vendedor_id
    ]);
    
    if ($executed) {
        // Actualizar la sesión si se cambió el nombre de la empresa
        $_SESSION['nombre_empresa'] = $nombre_empresa;
        
        // Obtener datos actualizados para la respuesta
        $stmt_vendedor = $conn->prepare("
            SELECT nombre_empresa, correo, telefono, direccion1, direccion2, categoria, 
                   cedula_juridica, biografia, redes, logo, fecha_registro
            FROM vendedores 
            WHERE id = ?
        ");
        $stmt_vendedor->execute([$vendedor_id]);
        $vendedor_actualizado = $stmt_vendedor->fetch();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'data' => [
                'vendedor' => $vendedor_actualizado
            ]
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el perfil. Inténtalo de nuevo.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en perfilVendedorController: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Inténtalo más tarde.',
        'error' => $e->getMessage()
    ]);
}
?>
