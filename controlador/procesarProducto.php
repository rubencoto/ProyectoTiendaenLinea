<?php
session_start();

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';

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
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    // Get form data
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria = $_POST['categoria'] ?? '';
    $tallas = $_POST['tallas'] ?? '';
    $color = $_POST['color'] ?? '';
    $stock = intval($_POST['stock'] ?? 0);
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

    
    // PDO SQL insert
    $sql = "INSERT INTO productos (
        nombre, descripcion, precio, categoria, 
        imagen_principal, imagen_secundaria1, imagen_secundaria2, 
        tallas, color, stock, garantia, dimensiones, peso, tamano_empaque, id_vendedor
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Error preparando consulta";
        exit;
    }
    
    // Execute with PDO parameters array
    $execute_result = $stmt->execute([
        $nombre,            // 1
        $descripcion,       // 2
        $precio,            // 3
        $categoria,         // 4
        $imagen_principal,  // 5
        $imagen_secundaria1,// 6
        $imagen_secundaria2,// 7
        $tallas,            // 8
        $color,             // 9
        $stock,             // 10
        $garantia,          // 11
        $dimensiones,       // 12
        $peso,              // 13
        $tamano_empaque,    // 14
        $id_vendedor        // 15
    ]);
    
    if ($execute_result) {
        echo "Producto agregado con éxito";
    } else {
        echo "Error al guardar producto";
    }
    
} catch (Exception $e) {
    echo "Error del sistema: " . $e->getMessage();
}
?>
