<?php
session_start();
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

// Debug: Output something immediately so we know this file is reached
echo "<!-- Debug: procesarProducto.php loaded at " . date('Y-m-d H:i:s') . " -->";
error_log("procesarProducto.php called with POST data: " . print_r($_POST, true));

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Error: Solo se permiten peticiones POST";
    exit;
}

// Check session
if (!isset($_SESSION['id'])) {
    echo "Error: Sesión no válida";
    exit;
}

$id_vendedor = $_SESSION['id'];

try {
    // Get connection from the included file
    if (!isset($conn) || !$conn) {
        echo "Error: No se pudo conectar a la base de datos";
        exit;
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Get form data
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria = $_POST['categoria'] ?? '';
    $tallas = $_POST['tallas'] ?? '';
    $color = $_POST['color'] ?? '';
    $unidades = intval($_POST['unidades'] ?? 0);
    $garantia = $_POST['garantia'] ?? '';
    $dimensiones = $_POST['dimensiones'] ?? '';
    $peso = floatval($_POST['peso'] ?? 0);
    $tamano_empaque = $_POST['tamano_empaque'] ?? '';
    
    // Validate required fields
    if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($categoria)) {
        echo "Error: Campos obligatorios faltantes";
        exit;
    }
    
    // Handle images
    $imagen_principal = null;
    $imagen_secundaria1 = null;
    $imagen_secundaria2 = null;
    
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
        $imagen_principal = file_get_contents($_FILES['imagen_principal']['tmp_name']);
    }
    if (isset($_FILES['imagen_secundaria1']) && $_FILES['imagen_secundaria1']['error'] === UPLOAD_ERR_OK) {
        $imagen_secundaria1 = file_get_contents($_FILES['imagen_secundaria1']['tmp_name']);
    }
    if (isset($_FILES['imagen_secundaria2']) && $_FILES['imagen_secundaria2']['error'] === UPLOAD_ERR_OK) {
        $imagen_secundaria2 = file_get_contents($_FILES['imagen_secundaria2']['tmp_name']);
    }

    
    // Simple SQL insert with string concatenation for testing
    $sql = "INSERT INTO productos (
        nombre, descripcion, precio, categoria, 
        imagen_principal, imagen_secundaria1, imagen_secundaria2, 
        tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Error preparando consulta: " . $conn->error;
        exit;
    }
    
    // Simple bind with exact count: 15 parameters need 15 characters
    // Let me count: s-s-d-s-b-b-b-s-s-i-s-s-d-s-i = 15 characters
    $bind_result = $stmt->bind_param(
        "ssdsbbssissdsi",  // 15 characters for 15 parameters 
        $nombre,            // 1: s
        $descripcion,       // 2: s
        $precio,            // 3: d
        $categoria,         // 4: s
        $imagen_principal,  // 5: b
        $imagen_secundaria1,// 6: b
        $imagen_secundaria2,// 7: b
        $tallas,            // 8: s
        $color,             // 9: s
        $unidades,          // 10: i
        $garantia,          // 11: s
        $dimensiones,       // 12: s
        $peso,              // 13: d
        $tamano_empaque,    // 14: s
        $id_vendedor        // 15: i
    );
    
    if (!$bind_result) {
        echo "Error vinculando parámetros: " . $stmt->error;
        exit;
    }
    
    // Execute
    $execute_result = $stmt->execute();
    
    if ($execute_result) {
        echo "Producto agregado con éxito";
    } else {
        echo "Error al guardar producto: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "Error del sistema: " . $e->getMessage();
}
?>
