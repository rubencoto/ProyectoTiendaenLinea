<?php
// Simple test page to debug verification issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Verification Page Debug Tool</h2>";

// Test database connection
try {
    require_once '../modelo/conexion.php';
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if verification page exists and is accessible
$verification_file = __DIR__ . '/verificarCuentaCliente.php';
echo "<p><strong>Verification file path:</strong> $verification_file</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($verification_file) ? "✅ YES" : "❌ NO") . "</p>";
echo "<p><strong>File readable:</strong> " . (is_readable($verification_file) ? "✅ YES" : "❌ NO") . "</p>";

// Test different URL patterns
echo "<h3>🔗 Test Links</h3>";
echo "<ul>";
echo "<li><a href='verificarCuentaCliente.php' target='_blank'>Normal verification page</a></li>";
echo "<li><a href='verificarCuentaCliente.php?registro=1&correo=test@example.com' target='_blank'>Registration success test</a></li>";
echo "<li><a href='verificarCuentaCliente.php?pendiente=1&correo=test@example.com' target='_blank'>Pending verification test</a></li>";
echo "<li><a href='verificarCuentaCliente.php?codigo=123456&correo=test@example.com' target='_blank'>URL verification test</a></li>";
echo "</ul>";

// Check for recent unverified accounts
try {
    $stmt = $conn->prepare("SELECT correo, codigo_verificacion, fecha_registro FROM clientes WHERE verificado = 0 ORDER BY fecha_registro DESC LIMIT 5");
    $stmt->execute();
    $results = $stmt->get_result();
    
    echo "<h3>📊 Recent Unverified Accounts (for testing)</h3>";
    if ($results->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Correo</th><th>Código</th><th>Fecha</th><th>Test Link</th></tr>";
        while ($row = $results->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['correo']) . "</td>";
            echo "<td>" . htmlspecialchars($row['codigo_verificacion']) . "</td>";
            echo "<td>" . $row['fecha_registro'] . "</td>";
            echo "<td><a href='verificarCuentaCliente.php?correo=" . urlencode($row['correo']) . "&codigo=" . urlencode($row['codigo_verificacion']) . "' target='_blank'>Test Verify</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No unverified accounts found.</p>";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking accounts: " . $e->getMessage() . "</p>";
}

// Server information
echo "<h3>🖥️ Server Info</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";

echo "<h3>🔄 Quick Actions</h3>";
echo "<p><a href='cleanup_database.php'>🗑️ Cleanup Database</a> | ";
echo "<a href='registroCliente.php'>➕ Test Registration</a> | ";
echo "<a href='loginCliente.php'>🔑 Login Page</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f0f0f0; }
</style>
