<?php
require_once '../modelo/conexion.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<h2>🔍 Investigating Order ORD-2025-945427 for leomoya55@yahoo.com</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.success { color: #28a745; } .warning { color: #ffc107; } .error { color: #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f8f9fa; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

try {
    // 1. First, find the customer ID for leomoya55@yahoo.com
    echo "<div class='card'>";
    echo "<h3>1. Customer Information</h3>";
    
    $stmt_customer = $conn->prepare("SELECT id, nombre, apellido, correo FROM clientes WHERE correo = ?");
    $stmt_customer->execute(['leomoya55@yahoo.com']);
    $customer = $stmt_customer->fetch();
    
    if (!$customer) {
        echo "<p class='error'>❌ Customer leomoya55@yahoo.com not found!</p>";
        echo "</div>";
        exit;
    }
    
    echo "<p class='success'>✅ Found customer:</p>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$customer['id']}</li>";
    echo "<li><strong>Name:</strong> " . htmlspecialchars($customer['nombre'] . ' ' . $customer['apellido']) . "</li>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($customer['correo']) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    $cliente_id = $customer['id'];

    // 2. Check for the specific order ORD-2025-945427
    echo "<div class='card'>";
    echo "<h3>2. Direct Check: Order ORD-2025-945427</h3>";
    
    $stmt_specific = $conn->prepare("
        SELECT * FROM pedidos 
        WHERE numero_orden = 'ORD-2025-945427'
    ");
    $stmt_specific->execute();
    
    $specific_orders = [];
    while ($row = $stmt_specific->fetch()) {
        $specific_orders[] = $row;
    }
    
    echo "<p>Found <strong>" . count($specific_orders) . "</strong> records with order number ORD-2025-945427:</p>";
    
    if (count($specific_orders) > 1) {
        echo "<p class='error'>⚠️ DUPLICATE DETECTED! This order number exists multiple times in the database:</p>";
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Order Number</th><th>Customer ID</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    foreach ($specific_orders as $order) {
        $highlight = ($order['cliente_id'] == $cliente_id) ? "style='background-color: #fff3cd;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['numero_orden']}</td>";
        echo "<td>{$order['cliente_id']}</td>";
        echo "<td>$" . number_format($order['total'], 2) . "</td>";
        echo "<td>{$order['estado']}</td>";
        echo "<td>{$order['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 3. Check all orders for this customer
    echo "<div class='card'>";
    echo "<h3>3. All Orders for Customer {$customer['correo']}</h3>";
    
    $stmt_customer_orders = $conn->prepare("
        SELECT * FROM pedidos 
        WHERE cliente_id = ?
        ORDER BY fecha_orden DESC
    ");
    $stmt_customer_orders->execute([$cliente_id]);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Order Number</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    while ($row = $stmt_customer_orders->fetch()) {
        $highlight = ($row['numero_orden'] == 'ORD-2025-945427') ? "style='background-color: #fff3cd;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['numero_orden']}</td>";
        echo "<td>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['estado']}</td>";
        echo "<td>{$row['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 4. Check the exact query used in misPedidos.php
    echo "<div class='card'>";
    echo "<h3>4. Simulating misPedidos.php Query</h3>";
    
    $stmt_mis_pedidos = $conn->prepare("
        SELECT id, numero_orden, subtotal, envio, total, estado, fecha_orden
        FROM pedidos 
        WHERE cliente_id = ? 
        ORDER BY fecha_orden DESC
    ");
    $stmt_mis_pedidos->execute([$cliente_id]);
    
    $ordenes_from_query = [];
    while ($row = $stmt_mis_pedidos->fetch()) {
        $ordenes_from_query[] = $row;
    }
    
    echo "<p>Query returned <strong>" . count($ordenes_from_query) . "</strong> orders:</p>";
    
    // Count occurrences of ORD-2025-945427
    $count_target_order = 0;
    foreach ($ordenes_from_query as $order) {
        if ($order['numero_orden'] == 'ORD-2025-945427') {
            $count_target_order++;
        }
    }
    
    if ($count_target_order > 1) {
        echo "<p class='error'>⚠️ PROBLEM FOUND: Order ORD-2025-945427 appears $count_target_order times in this query result!</p>";
    } else {
        echo "<p class='success'>✅ Order ORD-2025-945427 appears $count_target_order time(s) in this query - this is correct.</p>";
    }
    
    echo "<table>";
    echo "<tr><th>Array Index</th><th>ID</th><th>Order Number</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    foreach ($ordenes_from_query as $index => $order) {
        $highlight = ($order['numero_orden'] == 'ORD-2025-945427') ? "style='background-color: #fff3cd;'" : "";
        echo "<tr $highlight>";
        echo "<td>$index</td>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['numero_orden']}</td>";
        echo "<td>$" . number_format($order['total'], 2) . "</td>";
        echo "<td>{$order['estado']}</td>";
        echo "<td>{$order['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 5. Check for any potential issues with order details
    echo "<div class='card'>";
    echo "<h3>5. Order Details for ORD-2025-945427</h3>";
    
    foreach ($specific_orders as $order) {
        echo "<h4>Order ID: {$order['id']} (Customer ID: {$order['cliente_id']})</h4>";
        
        $stmt_details = $conn->prepare("
            SELECT dp.*, p.nombre as producto_nombre 
            FROM detalle_pedidos dp
            JOIN productos p ON dp.producto_id = p.id
            WHERE dp.orden_id = ?
        ");
        $stmt_details->execute([$order['id']]);
        
        echo "<table>";
        echo "<tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr>";
        $has_details = false;
        while ($detail = $stmt_details->fetch()) {
            $has_details = true;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($detail['producto_nombre']) . "</td>";
            echo "<td>{$detail['cantidad']}</td>";
            echo "<td>$" . number_format($detail['precio_unitario'], 2) . "</td>";
            echo "<td>$" . number_format($detail['subtotal'], 2) . "</td>";
            echo "</tr>";
        }
        if (!$has_details) {
            echo "<tr><td colspan='4' style='text-align: center; color: #666;'>No order details found</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 6. Recommendations
    echo "<div class='card'>";
    echo "<h3>💡 Analysis & Recommendations</h3>";
    
    if (count($specific_orders) > 1) {
        echo "<div class='error'>";
        echo "<h4>🚨 DUPLICATE ORDER DETECTED</h4>";
        echo "<p>The order number ORD-2025-945427 appears <strong>" . count($specific_orders) . " times</strong> in the pedidos table. This should not happen!</p>";
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>Double-click on checkout button</li>";
        echo "<li>Network timeout causing retry</li>";
        echo "<li>Bug in order creation logic</li>";
        echo "<li>Database constraint missing on numero_orden</li>";
        echo "</ul>";
        echo "<p><strong>Recommended actions:</strong></p>";
        echo "<ul>";
        echo "<li>Add UNIQUE constraint on numero_orden field</li>";
        echo "<li>Check order creation logic for race conditions</li>";
        echo "<li>Implement order deduplication</li>";
        echo "<li>Consider removing duplicate entries manually</li>";
        echo "</ul>";
        echo "</div>";
    } else if (count($specific_orders) == 1) {
        echo "<div class='warning'>";
        echo "<h4>🤔 Order Exists Once in Database</h4>";
        echo "<p>The order ORD-2025-945427 appears only <strong>once</strong> in the database, but you're seeing it twice on the page.</p>";
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>JavaScript or CSS duplicating the display</li>";
        echo "<li>Multiple AJAX calls loading the same data</li>";
        echo "<li>Browser caching issues</li>";
        echo "<li>Loop logic error in misPedidos.php</li>";
        echo "</ul>";
        echo "<p><strong>Recommended actions:</strong></p>";
        echo "<ul>";
        echo "<li>Check browser developer tools for duplicate DOM elements</li>";
        echo "<li>Clear browser cache and reload</li>";
        echo "<li>Check misPedidos.php for any duplicate rendering logic</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h4>✅ Order Not Found</h4>";
        echo "<p>No order with number ORD-2025-945427 was found in the database.</p>";
        echo "</div>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
