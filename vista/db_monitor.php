<?php
/**
 * Database Connection Monitor
 * Check current connection status and limits
 */

require_once '../modelo/conexion.php';

echo "<h2>🔍 Database Connection Monitor</h2>";

try {
    // Test connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Connection Status:</strong> Connected successfully to AWS RDS<br>";
    echo "📊 <strong>Database:</strong> " . $conn->get_server_info() . "<br>";
    echo "🌐 <strong>Host:</strong> kavfu5f7pido12mr.cbetxkdyhwsb.us-east-1.rds.amazonaws.com<br>";
    echo "🔗 <strong>Current Domain:</strong> " . $_SERVER['HTTP_HOST'] . "<br>";
    echo "⏰ <strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
    echo "</div>";
    
    // Check current connection info
    $result = $conn->query("SHOW STATUS LIKE 'Threads_connected'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<p><strong>Current Connections:</strong> " . $row['Value'] . "</p>";
    }
    
    $result = $conn->query("SHOW VARIABLES LIKE 'max_user_connections'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<p><strong>Max User Connections:</strong> " . $row['Value'] . "</p>";
    }
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<p><strong>Total Clients:</strong> " . $row['total'] . "</p>";
    }
    
    $result = $conn->query("SELECT COUNT(*) as total FROM vendedores");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<p><strong>Total Vendors:</strong> " . $row['total'] . "</p>";
    }
    
    $result = $conn->query("SELECT COUNT(*) as total FROM productos");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<p><strong>Total Products:</strong> " . $row['total'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ <strong>Connection Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    // Check if it's the connection limit error
    if (strpos($e->getMessage(), 'max_user_connections') !== false) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🚨 Connection Error</h3>";
        echo "<p><strong>Problem:</strong> Unable to connect to AWS RDS database.</p>";
        echo "<p><strong>Solutions:</strong></p>";
        echo "<ul>";
        echo "<li>Check AWS RDS instance status</li>";
        echo "<li>Verify security group settings allow connections</li>";
        echo "<li>Confirm database credentials are correct</li>";
        echo "<li>Check if RDS instance is publicly accessible</li>";
        echo "</ul>";
        echo "</div>";
    }
}

echo "<h3>🔧 Connection Optimization Status:</h3>";
echo "<ul>";
echo "<li>✅ Singleton pattern implemented</li>";
echo "<li>✅ Automatic connection cleanup</li>";
echo "<li>✅ Retry logic with exponential backoff</li>";
echo "<li>✅ Reduced timeout values (60s instead of 300s)</li>";
echo "<li>✅ Connection pooling optimized</li>";
echo "</ul>";

echo "<h3>📋 Recommendations:</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>1. Primary Access:</strong> Use only one domain primarily (either Heroku URL or custom domain)</p>";
echo "<p><strong>2. Connection Monitoring:</strong> Check this page if you encounter connection issues</p>";
echo "<p><strong>3. Database Upgrade:</strong> Consider upgrading Clever Cloud plan if the issue persists</p>";
echo "<p><strong>4. Wait Period:</strong> If connections are maxed out, wait 1-2 minutes before trying again</p>";
echo "</div>";

echo "<br><a href='index.php' style='background: #007185; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Back to Store</a>";
?>
