<?php
session_start();
require_once '../modelo/conexion.php';

// Simulate being logged in as leomoya55@yahoo.com
$stmt_customer = $conn->prepare("SELECT id FROM clientes WHERE correo = ?");
$stmt_customer->execute(['leomoya55@yahoo.com']);
$customer = $stmt_customer->fetch();

if (!$customer) {
    die("Customer not found");
}

$cliente_id = $customer['id'];

echo "<h2>Debugging Order Display for leomoya55@yahoo.com</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.debug { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; }
.order { border: 2px solid #007185; margin: 10px 0; padding: 15px; background: white; }
.duplicate { border-color: red; background: #ffe6e6; }
</style>";

// Get database connection
$db = DatabaseConnection::getInstance();
$pdo_conn = $db->getConnection();

// Execute the exact same query as misPedidos.php
$stmt_ordenes = $pdo_conn->prepare("
    SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
           o.estado, o.fecha_orden
    FROM pedidos o 
    WHERE o.cliente_id = ? 
    ORDER BY o.fecha_orden DESC
");
$stmt_ordenes->execute([$cliente_id]);

$ordenes = [];
while ($row = $stmt_ordenes->fetch()) {
    $ordenes[] = $row;
}

echo "<div class='debug'>";
echo "<strong>Query returned " . count($ordenes) . " orders</strong><br>";
echo "Customer ID: $cliente_id<br>";
echo "Raw query results:";
echo "</div>";

// Show the raw data structure
echo "<pre>";
print_r($ordenes);
echo "</pre>";

// Count specific order
$target_order_count = 0;
$target_orders = [];
foreach ($ordenes as $orden) {
    if ($orden['numero_orden'] == 'ORD-2025-945427') {
        $target_order_count++;
        $target_orders[] = $orden;
    }
}

echo "<div class='debug'>";
echo "<strong>ORD-2025-945427 appears $target_order_count times in the query result</strong>";
echo "</div>";

// Simulate the display loop exactly like misPedidos.php
echo "<h3>Simulated Display (like misPedidos.php):</h3>";

foreach ($ordenes as $index => $orden) {
    $isTarget = ($orden['numero_orden'] == 'ORD-2025-945427');
    $class = $isTarget ? 'order duplicate' : 'order';
    
    echo "<div class='$class'>";
    echo "<strong>Array Index:</strong> $index<br>";
    echo "<strong>Order ID:</strong> {$orden['id']}<br>";
    echo "<strong>Order Number:</strong> {$orden['numero_orden']}<br>";
    echo "<strong>Total:</strong> ₡" . number_format($orden['total'], 2) . "<br>";
    echo "<strong>Date:</strong> {$orden['fecha_orden']}<br>";
    echo "<strong>Status:</strong> {$orden['estado']}<br>";
    
    if ($isTarget) {
        echo "<div style='color: red; font-weight: bold;'>⚠️ THIS IS THE DUPLICATE ORDER</div>";
    }
    echo "</div>";
}

// Additional check: Look for any data processing that might duplicate entries
echo "<h3>Checking for Data Processing Issues:</h3>";

// Check if there are any duplicate IDs in the result set
$order_ids = array_column($ordenes, 'id');
$duplicate_ids = array_diff_assoc($order_ids, array_unique($order_ids));

if (!empty($duplicate_ids)) {
    echo "<div style='color: red; font-weight: bold;'>❌ FOUND DUPLICATE ORDER IDs IN RESULT SET:</div>";
    print_r($duplicate_ids);
} else {
    echo "<div style='color: green; font-weight: bold;'>✅ No duplicate order IDs found in result set</div>";
}

// Check if the order number appears multiple times
$order_numbers = array_column($ordenes, 'numero_orden');
$number_counts = array_count_values($order_numbers);

echo "<h4>Order Number Frequency:</h4>";
foreach ($number_counts as $number => $count) {
    if ($count > 1) {
        echo "<div style='color: red;'>$number appears $count times</div>";
    } else if ($number == 'ORD-2025-945427') {
        echo "<div style='color: blue; font-weight: bold;'>$number appears $count time (this is correct)</div>";
    }
}

echo "<h3>🔍 Conclusion:</h3>";
if ($target_order_count > 1) {
    echo "<div style='color: red; padding: 10px; background: #ffe6e6; border: 1px solid red;'>";
    echo "<strong>PROBLEM CONFIRMED:</strong> The SQL query is returning ORD-2025-945427 multiple times ($target_order_count times).<br>";
    echo "This suggests a database-level issue or a problem with the SQL query logic.";
    echo "</div>";
} else if ($target_order_count == 1) {
    echo "<div style='color: orange; padding: 10px; background: #fff3cd; border: 1px solid orange;'>";
    echo "<strong>SQL QUERY IS CORRECT:</strong> ORD-2025-945427 appears only once in the query result.<br>";
    echo "The duplication issue is likely in the frontend display, JavaScript, or browser rendering.";
    echo "</div>";
} else {
    echo "<div style='color: blue; padding: 10px; background: #e6f3ff; border: 1px solid blue;'>";
    echo "<strong>ORDER NOT FOUND:</strong> ORD-2025-945427 was not found in the query results.<br>";
    echo "The order might not belong to this customer or might not exist.";
    echo "</div>";
}
?>
