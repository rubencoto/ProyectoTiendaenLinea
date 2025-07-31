<?php
// Simple test to verify the DatabaseConnection class works
session_start();

// Include the database connection
require_once 'modelo/conexion.php';

echo "<h1>Database Connection Test</h1>";

// Test if the class exists and can be instantiated
if (class_exists('DatabaseConnection')) {
    echo "<p style='color: green;'>✓ DatabaseConnection class found</p>";
    
    try {
        $db = DatabaseConnection::getInstance();
        echo "<p style='color: green;'>✓ DatabaseConnection instance created</p>";
        
        $conn = $db->getConnection();
        echo "<p style='color: green;'>✓ Connection object retrieved</p>";
        
        echo "<p><strong>Connection Type:</strong> " . $db->getConnectionType() . "</p>";
        echo "<p><strong>Is Connected:</strong> " . ($db->isConnected() ? 'Yes' : 'No') . "</p>";
        
        if (!$db->isConnected()) {
            echo "<p style='color: orange;'>⚠️ Using mock connection - Error: " . $db->getLastError() . "</p>";
        }
        
        // Test a simple query
        echo "<h2>Query Test</h2>";
        $stmt = $conn->prepare("SELECT 1 as test");
        if ($stmt) {
            $stmt->execute();
            echo "<p style='color: green;'>✓ Prepare statement works</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Prepare statement returned false (expected with mock)</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ DatabaseConnection class not found</p>";
}

echo "<p>Test completed - no fatal errors!</p>";
?>
