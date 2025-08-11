<?php
// Turn off error reporting to prevent HTML error output
error_reporting(0);
ini_set('display_errors', 0);

// Ensure we output JSON even if there are PHP notices/warnings
ob_start();

header("Content-Type: application/json");

try {
    require_once '../modelo/conexion.php';

    // Get database connection
    $db = DatabaseConnection::getInstance();
    
    if (!$db || !$db->isConnected()) {
        throw new Exception("No se pudo establecer conexión con la base de datos");
    }
    
    $conn = $db->getConnection();

    // Verify POST request and ID parameter
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Método no permitido."]);
        exit;
    }

    if (!isset($_POST["id"]) || empty($_POST["id"])) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "ID del producto no especificado."]);
        exit;
    }

    $id = intval($_POST["id"]);

    if ($id <= 0) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "ID del producto inválido."]);
        exit;
    }

    // Use PDO-style prepare for better compatibility
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta");
    }

    if ($stmt->execute([$id])) {
        // For PDO, use rowCount() to check affected rows
        $affectedRows = $stmt->rowCount();
        
        if ($affectedRows > 0) {
            ob_clean();
            echo json_encode([
                "status" => "success", 
                "message" => "Producto eliminado correctamente."
            ]);
        } else {
            ob_clean();
            echo json_encode([
                "status" => "error", 
                "message" => "No se encontró el producto a eliminar."
            ]);
        }
    } else {
        ob_clean();
        echo json_encode([
            "status" => "error", 
            "message" => "Error al eliminar el producto."
        ]);
    }

} catch (Exception $e) {
    error_log("Error in eliminarProductoController.php: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        "status" => "error", 
        "message" => "Error interno del servidor: " . $e->getMessage()
    ]);
}
?>
