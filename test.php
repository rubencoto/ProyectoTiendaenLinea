<?php
// Simple test file to verify PHP is working on Heroku
echo "<h1>PHP is working on Heroku!</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test database connection
echo "<h2>Database Connection Test:</h2>";
try {
    require_once 'modelo/conexion.php';
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test a simple query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        echo "<p style='color: green;'>✓ Database query test successful!</p>";
    }
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Available Files:</h2>";
echo "<ul>";
echo "<li><a href='vista/loginCliente.php'>Client Login</a></li>";
echo "<li><a href='vista/loginVendedor.php'>Vendor Login</a></li>";
echo "<li><a href='vista/registroCliente.php'>Client Registration</a></li>";
echo "</ul>";
?>
