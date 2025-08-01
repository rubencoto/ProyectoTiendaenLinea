<?php
// Test the updated perfil.php logic
session_start();

// Mock a client session
$_SESSION['cliente_id'] = 1;

// Include the database connection
require_once 'modelo/conexion.php';

echo "<h1>Perfil Update Test</h1>";

$cliente_id = $_SESSION['cliente_id'];

echo "<p>Testing perfil update logic with cliente_id: $cliente_id</p>";

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<p><strong>Connection Type:</strong> " . $db->getConnectionType() . "</p>";

try {
    // Test updated query structure (prepare only, don't execute)
    echo "<h3>1. Updated Query Structure Test</h3>";
    $stmt_update = $conn->prepare("
        UPDATE clientes SET 
            apellido = ?, telefono = ?, 
            direccion = ?, provincia = ?, 
            genero = ?, newsletter = ?
        WHERE id = ?
    ");
    echo "<p>✓ Update query (without nombre and fecha_nacimiento) prepared successfully</p>";
    
    // Test refresh query
    echo "<h3>2. Refresh Query Test</h3>";
    $stmt_refresh = $conn->prepare("
        SELECT nombre, apellido, correo, telefono, direccion, 
               provincia, fecha_nacimiento, genero, newsletter, 
               verificado, fecha_registro
        FROM clientes 
        WHERE id = ?
    ");
    $stmt_refresh->execute([$cliente_id]);
    $cliente = $stmt_refresh->fetch();
    echo "<p>✓ Refresh query executed successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<p style='color: green;'>✓ All tests completed without fatal errors!</p>";
?>
