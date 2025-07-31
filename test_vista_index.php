<?php
// Test the updated vista index logic
session_start();

// Include the database connection
require_once 'modelo/conexion.php';

echo "<h1>Vista Index Test</h1>";

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<p><strong>Connection Type:</strong> " . $db->getConnectionType() . "</p>";
echo "<p><strong>Is Connected:</strong> " . ($db->isConnected() ? 'Yes' : 'No') . "</p>";

if (!$db->isConnected()) {
    echo "<p style='color: orange;'>⚠️ Using mock connection</p>";
}

// Test the products query (simulating vista/index.php logic)
echo "<h2>Products Query Test</h2>";

try {
    $stmt = $conn->prepare(
        "SELECT p.id, p.nombre, p.precio, p.imagen_principal, p.descripcion, v.nombre_empresa AS vendedor_nombre 
        FROM productos p 
        JOIN vendedores v ON p.id_vendedor = v.id 
        ORDER BY p.id DESC"
    );
    
    if ($stmt) {
        echo "<p style='color: green;'>✓ Query prepared successfully</p>";
        
        $executed = $stmt->execute();
        echo "<p>Execute result: " . ($executed ? '✓ Success' : '✗ Failed') . "</p>";
        
        $productos = [];
        while ($row = $stmt->fetch()) {
            if ($row && is_array($row)) {
                if ($row['imagen_principal']) {
                    $row['imagen_principal'] = base64_encode($row['imagen_principal']);
                }
                $productos[] = $row;
            } else {
                break; // No more rows or mock connection
            }
        }
        
        echo "<p>Products found: " . count($productos) . "</p>";
        
        if (count($productos) === 0 && !$db->isConnected()) {
            echo "<p style='color: orange;'>⚠️ No products (expected with mock connection)</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Failed to prepare query</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<p style='color: green;'>✓ Test completed without fatal errors!</p>";
?>
