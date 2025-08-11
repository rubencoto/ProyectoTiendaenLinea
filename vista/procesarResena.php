<?php
// Turn off error reporting to prevent HTML error output
error_reporting(0);
ini_set('display_errors', 0);

// Ensure we output JSON even if there are PHP notices/warnings
ob_start();

require_once '../modelo/conexion.php';

// Set JSON response header
header('Content-Type: application/json');

// Start session to get client ID
session_start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['cliente_id'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Debes estar logueado para enviar una reseña.']);
        exit;
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    // Get form data
    $cliente_id = $_SESSION['cliente_id'];
    $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
    $orden_id = filter_input(INPUT_POST, 'orden_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comentario = trim(filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_STRING));

    // Validate input
    if (!$producto_id || !$orden_id || !$rating || empty($comentario)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas.']);
        exit;
    }

    if (strlen($comentario) < 10) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 10 caracteres.']);
        exit;
    }

    // Get database connection
    $db = DatabaseConnection::getInstance();
    
    if (!$db || !$db->isConnected()) {
        throw new Exception("No se pudo establecer conexión con la base de datos");
    }
    
    $conexion = $db->getConnection();
    
    // Verify that the customer actually purchased this product in this order
    // Fixed: Use 'pedidos' table instead of 'ordenes'
    $verificarCompra = $conexion->prepare("
        SELECT COUNT(*) as compro 
        FROM pedidos o 
        INNER JOIN detalle_pedidos dp ON o.id = dp.orden_id 
        WHERE o.cliente_id = ? AND dp.producto_id = ? AND o.id = ?
    ");
    $verificarCompra->execute([$cliente_id, $producto_id, $orden_id]);
    $compro = $verificarCompra->fetch();

    if ($compro['compro'] == 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'No puedes reseñar un producto que no has comprado.']);
        exit;
    }

    // Check if the customer has already reviewed this product
    $verificarReseña = $conexion->prepare("
        SELECT COUNT(*) as ya_reseño 
        FROM reseñas 
        WHERE cliente_id = ? AND producto_id = ?
    ");
    $verificarReseña->execute([$cliente_id, $producto_id]);
    $yaReseño = $verificarReseña->fetch();

    if ($yaReseño['ya_reseño'] > 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Ya has reseñado este producto anteriormente.']);
        exit;
    }

    // Insert the review
    $insertarReseña = $conexion->prepare("
        INSERT INTO reseñas (cliente_id, producto_id, estrellas, comentario, fecha) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $insertarReseña->execute([$cliente_id, $producto_id, $rating, $comentario]);
    
    if ($result) {
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Reseña enviada exitosamente.']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Error al guardar la reseña. Inténtalo de nuevo.']);
    }

} catch (Exception $e) {
    error_log("Error en procesarResena.php: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>
