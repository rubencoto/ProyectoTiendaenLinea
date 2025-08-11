<?php
header("Content-Type: application/json");

require_once '../modelo/conexion.php';

try {
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    // Verify POST request and ID parameter
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["status" => "error", "message" => "Método no permitido."]);
        exit;
    }

    if (!isset($_POST["id"]) || empty($_POST["id"])) {
        echo json_encode(["status" => "error", "message" => "ID del producto no especificado."]);
        exit;
    }

    $id = intval($_POST["id"]);

    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID del producto inválido."]);
        exit;
    }

    // Prepare and execute deletion
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta");
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                "status" => "success", 
                "message" => "Producto eliminado correctamente."
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "No se encontró el producto a eliminar."
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Error al eliminar el producto."
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in eliminarProductoController.php: " . $e->getMessage());
    echo json_encode([
        "status" => "error", 
        "message" => "Error interno del servidor."
    ]);
}
?>
