<?php
// Connection test script for debugging database connection issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test the singleton connection
    $db1 = DatabaseConnection::getInstance();
    $conn1 = $db1->getConnection();
    
    $db2 = DatabaseConnection::getInstance();
    $conn2 = $db2->getConnection();
    
    // Check if both instances are the same (singleton working)
    echo "<p><strong>Singleton Test:</strong> ";
    if ($db1 === $db2) {
        echo "<span style='color: green;'>✓ PASSED - Both instances are the same</span></p>";
    } else {
        echo "<span style='color: red;'>✗ FAILED - Multiple instances created</span></p>";
    }
    
    // Test database connection
    echo "<p><strong>Connection Test:</strong> ";
    if ($conn1 && !$conn1->connect_error) {
        echo "<span style='color: green;'>✓ PASSED - Database connected successfully</span></p>";
    } else {
        echo "<span style='color: red;'>✗ FAILED - " . ($conn1->connect_error ?? 'Unknown error') . "</span></p>";
    }
    
    // Test a simple query
    $result = $conn1->query("SELECT 1 as test");
    echo "<p><strong>Query Test:</strong> ";
    if ($result && $result->num_rows > 0) {
        echo "<span style='color: green;'>✓ PASSED - Query executed successfully</span></p>";
        $result->free();
    } else {
        echo "<span style='color: red;'>✗ FAILED - Query failed</span></p>";
    }
    
    // Show connection info
    echo "<p><strong>Connection Info:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($conn1->host_info) . "</li>";
    echo "<li>Protocol Version: " . $conn1->protocol_version . "</li>";
    echo "<li>Server Version: " . htmlspecialchars($conn1->server_info) . "</li>";
    echo "</ul>";
    
    // Test multiple includes (simulating multiple page loads)
    echo "<p><strong>Multiple Include Test:</strong></p>";
    for ($i = 1; $i <= 3; $i++) {
        $tempDb = DatabaseConnection::getInstance();
        $tempConn = $tempDb->getConnection();
        echo "<p>Include $i: ";
        if ($tempDb === $db1) {
            echo "<span style='color: green;'>✓ Same instance</span></p>";
        } else {
            echo "<span style='color: red;'>✗ Different instance</span></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><span style='color: red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span></p>";
}

echo "<hr>";
echo "<p><em>If all tests pass, your connection management should be working properly and should resolve the 'max_user_connections' error.</em></p>";
?>
