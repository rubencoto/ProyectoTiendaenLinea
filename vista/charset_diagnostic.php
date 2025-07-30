<?php
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

echo "<h2>Database Charset and Collation Diagnostic</h2>";

try {
    // Check database default charset and collation
    echo "<h3>Database Information:</h3>";
    $result = $conn->query("SELECT @@character_set_database, @@collation_database");
    if ($result && $row = $result->fetch_row()) {
        echo "<p><strong>Database Charset:</strong> " . $row[0] . "</p>";
        echo "<p><strong>Database Collation:</strong> " . $row[1] . "</p>";
    }
    
    // Check connection charset
    echo "<h3>Connection Information:</h3>";
    $result = $conn->query("SELECT @@character_set_connection, @@collation_connection");
    if ($result && $row = $result->fetch_row()) {
        echo "<p><strong>Connection Charset:</strong> " . $row[0] . "</p>";
        echo "<p><strong>Connection Collation:</strong> " . $row[1] . "</p>";
    }
    
    // Check productos table structure
    echo "<h3>Productos Table Structure:</h3>";
    $result = $conn->query("SHOW FULL COLUMNS FROM productos");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Collation</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $collation = $row['Collation'] ?? 'N/A';
            echo "<tr>";
            echo "<td><strong>" . $row['Field'] . "</strong></td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $collation . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . ($row['Key'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test charset conversion
    echo "<h3>Charset Test:</h3>";
    $test_string = "Test string with special chars: áéíóú ñ";
    echo "<p><strong>Test String:</strong> " . $test_string . "</p>";
    
    $stmt = $conn->prepare("SELECT ? as test_value");
    $stmt->bind_param("s", $test_string);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "<p><strong>Database Response:</strong> " . $row['test_value'] . "</p>";
    }
    
    echo "<h3>Fix Commands:</h3>";
    echo "<p>If you see charset/collation mismatches, try running these commands:</p>";
    echo "<code>";
    echo "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;<br>";
    echo "ALTER DATABASE " . $conn->get_server_info() . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;<br>";
    echo "</code>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='" . AppConfig::vistaUrl('agregarproducto.php') . "'> ← Back to Add Product</a></p>";
?>
