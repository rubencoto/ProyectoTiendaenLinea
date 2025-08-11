<?php
require_once '../modelo/conexion.php';

// Set JSON response header
header('Content-Type: application/json');

// Start session to get client ID
session_start();

try {
    // Add logging for debugging
    error_log("procesarReseña.php: Starting review processing");
    
    // Check if user is logged in
    if (!isset($_SESSION['cliente_id'])) {
        error_log("procesarReseña.php: User not logged in");
        echo json_encode(['success' => false, 'message' => 'Debes estar logueado para enviar una reseña.']);
        exit;
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("procesarReseña.php: Not a POST request");
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    // Get form data
    $cliente_id = $_SESSION['cliente_id'];
    $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
    $orden_id = filter_input(INPUT_POST, 'orden_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comentario = trim(filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_STRING));

    error_log("procesarReseña.php: cliente_id=$cliente_id, producto_id=$producto_id, orden_id=$orden_id, rating=$rating, comentario=$comentario");

    // Validate input
    if (!$producto_id || !$orden_id || !$rating || empty($comentario)) {
        error_log("procesarReseña.php: Missing required fields");
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        error_log("procesarReseña.php: Invalid rating: $rating");
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas.']);
        exit;
    }

    if (strlen($comentario) < 10) {
        error_log("procesarReseña.php: Comment too short: " . strlen($comentario));
        echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 10 caracteres.']);
        exit;
    }

    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conexion = $db->getConnection();
    
    if (!$conexion) {
        error_log("procesarReseña.php: Failed to get database connection");
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
        exit;
    }
    
    error_log("procesarReseña.php: Database connection established");
    
    // Verify that the customer actually purchased this product in this order
    $verificarCompra = $conexion->prepare("
        SELECT COUNT(*) as compro 
        FROM pedidos o 
        INNER JOIN detalle_pedidos dp ON o.id = dp.orden_id 
        WHERE o.cliente_id = ? AND dp.producto_id = ? AND o.id = ?
    ");
    $verificarCompra->execute([$cliente_id, $producto_id, $orden_id]);
    $compro = $verificarCompra->fetch();

    error_log("procesarReseña.php: Purchase verification result: " . ($compro['compro'] ?? 'null'));

    if ($compro['compro'] == 0) {
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

    error_log("procesarReseña.php: Existing review check: " . ($yaReseño['ya_reseño'] ?? 'null'));

    if ($yaReseño['ya_reseño'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya has reseñado este producto anteriormente.']);
        exit;
    }

    // Insert the review
    $insertarReseña = $conexion->prepare("
        INSERT INTO reseñas (cliente_id, producto_id, estrellas, comentario, fecha) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    error_log("procesarReseña.php: Attempting to insert review");
    $result = $insertarReseña->execute([$cliente_id, $producto_id, $rating, $comentario]);
    error_log("procesarReseña.php: Insert result: " . ($result ? 'success' : 'failed'));
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Reseña enviada exitosamente.']);
    } else {
        $errorInfo = $insertarReseña->errorInfo();
        error_log("procesarReseña.php: Insert error: " . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'Error al guardar la reseña. Inténtalo de nuevo.']);
    }

} catch (Exception $e) {
    error_log("Error en procesarReseña.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>
