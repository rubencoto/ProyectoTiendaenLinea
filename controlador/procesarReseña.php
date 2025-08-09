<?php
require_once '../modelo/conexion.php';

// Set JSON response header
header('Content-Type: application/json');

// Start session to get client ID
session_start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['cliente_id'])) {
        echo json_encode(['success' => false, 'message' => 'Debes estar logueado para enviar una reseña.']);
        exit;
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas.']);
        exit;
    }

    if (strlen($comentario) < 10) {
        echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 10 caracteres.']);
        exit;
    }

    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conexion = $db->getConnection();

    // Verify that the customer actually purchased this product in this order
    $verificarCompra = $conexion->prepare("
        SELECT COUNT(*) as compro 
        FROM ordenes o 
        INNER JOIN detalle_pedidos dp ON o.id = dp.orden_id 
        WHERE o.cliente_id = ? AND dp.producto_id = ? AND o.id = ?
    ");
    $verificarCompra->execute([$cliente_id, $producto_id, $orden_id]);
    $compro = $verificarCompra->fetch(PDO::FETCH_ASSOC);

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
    $yaReseño = $verificarReseña->fetch(PDO::FETCH_ASSOC);

    if ($yaReseño['ya_reseño'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya has reseñado este producto anteriormente.']);
        exit;
    }

    // Insert the review
    $insertarReseña = $conexion->prepare("
        INSERT INTO reseñas (cliente_id, producto_id, estrellas, comentario, fecha) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    if ($insertarReseña->execute([$cliente_id, $producto_id, $rating, $comentario])) {
        echo json_encode(['success' => true, 'message' => 'Reseña enviada exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la reseña. Inténtalo de nuevo.']);
    }

} catch (Exception $e) {
    error_log("Error en procesarReseña.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>
