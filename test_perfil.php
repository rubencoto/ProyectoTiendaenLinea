<?php
// Test the updated perfil.php logic
session_start();

// Mock a client session
$_SESSION['cliente_id'] = 1;

// Include the database connection
require_once 'modelo/conexion.php';

echo "<h1>Perfil Test</h1>";

$cliente_id = $_SESSION['cliente_id'];

echo "<p>Testing perfil queries with cliente_id: $cliente_id</p>";

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<p><strong>Connection Type:</strong> " . $db->getConnectionType() . "</p>";

try {
    // Test client info query
    echo "<h3>1. Client Info Query</h3>";
    $stmt = $conn->prepare("
        SELECT nombre, apellido, correo, telefono, direccion, 
               provincia, fecha_nacimiento, genero, newsletter, 
               verificado, fecha_registro
        FROM clientes 
        WHERE id = ?
    ");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    echo "<p>✓ Client query executed successfully</p>";
    
    // Test update query (prepare only, don't execute)
    echo "<h3>2. Update Query Test (prepare only)</h3>";
    $stmt_update = $conn->prepare("
        UPDATE clientes SET 
            nombre = ?, apellido = ?, telefono = ?, 
            direccion = ?, provincia = ?, fecha_nacimiento = ?, 
            genero = ?, newsletter = ?
        WHERE id = ?
    ");
    echo "<p>✓ Update query prepared successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<p style='color: green;'>✓ All tests completed without fatal errors!</p>";
?>
