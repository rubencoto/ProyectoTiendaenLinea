<?php
header("Content-Type: application/json"); // Devuelve respuesta como JSON

include '../modelo/conexion.php';

// Verifica que la solicitud sea POST y que venga con un 'id'
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);

    // Prepara y ejecuta la eliminación
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "ok", "message" => "Producto eliminado correctamente."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al eliminar el producto."]);
    }

    $stmt->close();
    $conn->close();
} else {
    // Si no es POST o falta el ID
    echo json_encode(["status" => "error", "message" => "ID no especificado o método incorrecto."]);
}
?>
