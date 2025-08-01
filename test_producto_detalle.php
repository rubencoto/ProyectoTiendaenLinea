<?php
// Test the updated productoDetalleCliente logic
session_start();

// Mock a client session
$_SESSION['cliente_id'] = 1;

// Include the database connection
require_once 'modelo/conexion.php';

echo "<h1>ProductoDetalleCliente Test</h1>";

// Mock a product ID
$id = 1;

echo "<p>Testing product detail query with ID: $id</p>";

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<p><strong>Connection Type:</strong> " . $db->getConnectionType() . "</p>";
echo "<p><strong>Is Connected:</strong> " . ($db->isConnected() ? 'Yes' : 'No') . "</p>";

try {
    // Test the product detail query (simulating productoDetalleCliente.php logic)
    $stmt = $conn->prepare("
        SELECT p.*, v.nombre_empresa as vendedor_nombre, v.telefono as vendedor_telefono, v.correo as vendedor_correo 
        FROM productos p 
        JOIN vendedores v ON p.id_vendedor = v.id 
        WHERE p.id = ?
    ");
    
    if ($stmt) {
        echo "<p style='color: green;'>✓ Query prepared successfully</p>";
        
        $executed = $stmt->execute([$id]);
        echo "<p>Execute result: " . ($executed ? '✓ Success' : '✗ Failed') . "</p>";
        
        $producto = $stmt->fetch();
        
        if ($producto && is_array($producto)) {
            echo "<p style='color: green;'>✓ Product found</p>";
            echo "<p>Product data available: " . count($producto) . " fields</p>";
        } else {
            if (!$db->isConnected()) {
                echo "<p style='color: orange;'>⚠️ No product found (expected with mock connection)</p>";
            } else {
                echo "<p style='color: red;'>✗ Product not found</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ Failed to prepare query</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<p style='color: green;'>✓ Test completed without fatal errors!</p>";
?>
