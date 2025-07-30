<?php
session_start();

echo "<h2>Test Logout Functionality</h2>";

echo "<h3>Current Session Status:</h3>";
echo "<ul>";
echo "<li><strong>Cliente ID:</strong> " . ($_SESSION['cliente_id'] ?? 'Not set') . "</li>";
echo "<li><strong>Vendedor ID (vendedor_id):</strong> " . ($_SESSION['vendedor_id'] ?? 'Not set') . "</li>";
echo "<li><strong>Vendedor ID (id):</strong> " . ($_SESSION['id'] ?? 'Not set') . "</li>";
echo "</ul>";

// Test what logout.php would do
$is_cliente = isset($_SESSION['cliente_id']);
$is_vendedor = isset($_SESSION['vendedor_id']) || isset($_SESSION['id']);

echo "<h3>Logout Behavior Test:</h3>";
if ($is_cliente) {
    echo "<p>✅ <strong>Cliente detected</strong> - Would redirect to loginCliente.php</p>";
} elseif ($is_vendedor) {
    echo "<p>✅ <strong>Vendedor detected</strong> - Would redirect to loginVendedor.php</p>";
} else {
    echo "<p>ℹ️ <strong>No user session</strong> - Would redirect to index.php</p>";
}

echo "<h3>Test Actions:</h3>";
echo "<ul>";
echo "<li><a href='loginCliente.php'>Simulate Client Login</a></li>";
echo "<li><a href='loginVendedor.php'>Simulate Vendor Login</a></li>";
echo "<li><a href='logout.php' onclick='return confirm(\"Are you sure you want to test logout?\")'>Test Logout</a></li>";
echo "</ul>";

echo "<p><a href='index.php'>← Back to main page</a></p>";
?>
