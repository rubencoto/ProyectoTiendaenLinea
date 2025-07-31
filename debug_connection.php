<?php
// Check connection debug
require_once 'modelo/conexion.php';

echo "<h1>Database Connection Debug</h1>";

echo "<p>PDO loaded: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</p>";
echo "<p>MySQLi loaded: " . (extension_loaded('mysqli') ? 'Yes' : 'No') . "</p>";

if (extension_loaded('pdo')) {
    echo "<p>Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
}

echo "<p>All loaded extensions: " . implode(', ', get_loaded_extensions()) . "</p>";

// Test if connection exists
global $conn;
if ($conn) {
    echo "<p style='color: green;'>✓ Connection established</p>";
    echo "<p>Connection type: " . get_class($conn) . "</p>";
} else {
    echo "<p style='color: red;'>✗ No connection established</p>";
}

?>
