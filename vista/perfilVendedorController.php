<?php
/**
 * Controlador para gestión de perfil de vendedores
 * Maneja actualización de datos del vendedor
 */

session_start();

// Verificar autenticación del vendedor
if (!isset($_SESSION['id'])) {
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

$vendedor_id = $_SESSION['id'];

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
    $descripcion_tienda = trim($_POST['descripcion_tienda'] ?? '');
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre_empresa)) {
        $errores[] = "El nombre de la empresa es obligatorio";
    }
    
    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio";
    }
    
    if (empty($direccion1)) {
        $errores[] = "La dirección principal es obligatoria";
    }
    
    if (empty($categoria)) {
        $errores[] = "La categoría es obligatoria";
    }
    
    if (empty($cedula_juridica)) {
        $errores[] = "La cédula jurídica es obligatoria";
    }
    
    if (empty($descripcion_tienda)) {
        $errores[] = "La descripción de la tienda es obligatoria";
    }
    
    if (!empty($errores)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Por favor corrige los siguientes errores:',
            'errors' => $errores
        ]);
        exit;
    }
    
    // Get database connection
    $db = DatabaseConnection::getInstance();
    
    if ($db->isConnected() && $db->getConnectionType() === 'pdo') {
        $conn = $db->getConnection();
        $is_pdo = true;
    } else {
        $conn = $db->getConnection();
        $is_pdo = false;
    }
    
    // Actualizar información del vendedor
    if ($is_pdo) {
        $stmt = $conn->prepare("
            UPDATE vendedores 
            SET nombre_empresa = ?, telefono = ?, direccion1 = ?, direccion2 = ?, 
                categoria = ?, cedula_juridica = ?, descripcion_tienda = ?
            WHERE id = ?
        ");
        $resultado = $stmt->execute([
            $nombre_empresa, $telefono, $direccion1, $direccion2, 
            $categoria, $cedula_juridica, $descripcion_tienda, $vendedor_id
        ]);
    } else {
        $stmt = $conn->prepare("
            UPDATE vendedores 
            SET nombre_empresa = ?, telefono = ?, direccion1 = ?, direccion2 = ?, 
                categoria = ?, cedula_juridica = ?, descripcion_tienda = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssssssi", 
            $nombre_empresa, $telefono, $direccion1, $direccion2, 
            $categoria, $cedula_juridica, $descripcion_tienda, $vendedor_id
        );
        $resultado = $stmt->execute();
    }
    
    if ($resultado) {
        // Obtener información actualizada
        if ($is_pdo) {
            $stmt_info = $conn->prepare("
                SELECT nombre_empresa, telefono, direccion1, direccion2, categoria, 
                       cedula_juridica, descripcion_tienda
                FROM vendedores 
                WHERE id = ?
            ");
            $stmt_info->execute([$vendedor_id]);
            $vendedor_actualizado = $stmt_info->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt_info = $conn->prepare("
                SELECT nombre_empresa, telefono, direccion1, direccion2, categoria, 
                       cedula_juridica, descripcion_tienda
                FROM vendedores 
                WHERE id = ?
            ");
            $stmt_info->bind_param("i", $vendedor_id);
            $stmt_info->execute();
            $result = $stmt_info->get_result();
            $vendedor_actualizado = $result->fetch_assoc();
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'vendedor' => $vendedor_actualizado
        ]);
        
    } else {
        throw new Exception("Error al actualizar la base de datos");
    }
    
} catch (Exception $e) {
    error_log("Error en perfilVendedorController: " . $e->getMessage());
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor intenta nuevamente.'
    ]);
}
?>
