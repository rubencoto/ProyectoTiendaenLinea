<?php
// Test MySQLi compatibility with the new unified connection
require_once 'modelo/conexion.php';

echo "<h1>MySQLi Compatibility Test</h1>";

try {
    // Test MySQLi-style prepare and bind_param
    $stmt = $conn->prepare("SELECT ? as test_value");
    echo "<p style='color: green;'>✓ Prepare successful</p>";
    
    $testValue = "hello";
    $result = $stmt->bind_param("s", $testValue);
    echo "<p style='color: green;'>✓ bind_param successful: " . ($result ? 'true' : 'false') . "</p>";
    
    $result = $stmt->execute();
    echo "<p style='color: green;'>✓ execute successful: " . ($result ? 'true' : 'false') . "</p>";
    
    $result = $stmt->get_result();
    echo "<p style='color: green;'>✓ get_result successful</p>";
    
    $row = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ fetch_assoc successful: " . ($row ? 'got data' : 'no data') . "</p>";
    
    $stmt->close();
    echo "<p style='color: green;'>✓ close successful</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<p>MySQLi compatibility test completed!</p>";
?>
