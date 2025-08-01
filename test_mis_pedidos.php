<?php
// Test the updated misPedidos logic
session_start();

// Mock a client session
$_SESSION['cliente_id'] = 1;

// Include the database connection
require_once 'modelo/conexion.php';

echo "<h1>MisPedidos Test</h1>";

$cliente_id = $_SESSION['cliente_id'];

// Test pagination variables
$limite = 10;
$pagina = 1;
$offset = ($pagina - 1) * $limite;

echo "<p>Testing misPedidos queries with cliente_id: $cliente_id</p>";

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<p><strong>Connection Type:</strong> " . $db->getConnectionType() . "</p>";

try {
    // Test client info query
    echo "<h3>1. Client Info Query</h3>";
    $stmt = $conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente_data = $stmt->fetch();
    echo "<p>✓ Client query executed successfully</p>";
    
    // Test count query
    echo "<h3>2. Count Orders Query</h3>";
    $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM ordenes WHERE cliente_id = ?");
    $stmt_count->execute([$cliente_id]);
    $count_result = $stmt_count->fetch();
    echo "<p>✓ Count query executed successfully</p>";
    
    // Test orders query with LIMIT/OFFSET
    echo "<h3>3. Orders Query with LIMIT/OFFSET</h3>";
    $stmt_ordenes = $conn->prepare("
        SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
               o.estado, o.fecha_orden
        FROM ordenes o 
        WHERE o.cliente_id = ? 
        ORDER BY o.fecha_orden DESC 
        LIMIT $limite OFFSET $offset
    ");
    $stmt_ordenes->execute([$cliente_id]);
    echo "<p>✓ Orders query executed successfully</p>";
    
    // Test products query
    echo "<h3>4. Product Details Query</h3>";
    $stmt_detalle = $conn->prepare("
        SELECT dp.cantidad, dp.precio_unitario, dp.subtotal,
               p.nombre as producto_nombre, p.imagen_principal
        FROM detalle_pedidos dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.orden_id = ?
    ");
    $stmt_detalle->execute([1]); // Test with order ID 1
    echo "<p>✓ Product details query executed successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<p style='color: green;'>✓ All tests completed without fatal errors!</p>";
?>
