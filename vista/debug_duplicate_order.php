<?php
require_once '../modelo/conexion.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

$email = 'leomoya55@yahoo.com';
$order_number = 'ORD-2025-945427';

echo "<h2>🔍 Investigating Duplicate Order: $order_number for $email</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.success { color: #28a745; } .warning { color: #ffc107; } .error { color: #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f8f9fa; }
.highlight { background: #fff3cd; }
</style>";

try {
    // 1. Get client info
    echo "<div class='card'>";
    echo "<h3>1. Client Information</h3>";
    
    $stmt_client = $conn->prepare("SELECT * FROM clientes WHERE correo = ?");
    $stmt_client->execute([$email]);
    $client = $stmt_client->fetch();
    
    if ($client) {
        echo "<p><strong>Client ID:</strong> {$client['id']}</p>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($client['correo']) . "</p>";
        $cliente_id = $client['id'];
    } else {
        echo "<p class='error'>❌ Client not found with email: $email</p>";
        exit;
    }
    echo "</div>";

    // 2. Check for duplicate order numbers in pedidos table
    echo "<div class='card'>";
    echo "<h3>2. Orders with Number: $order_number</h3>";
    
    $stmt_orders = $conn->prepare("
        SELECT id, numero_orden, cliente_id, subtotal, envio, total, estado, fecha_orden
        FROM pedidos 
        WHERE numero_orden = ?
        ORDER BY id
    ");
    $stmt_orders->execute([$order_number]);
    
    $orders = [];
    while ($row = $stmt_orders->fetch()) {
        $orders[] = $row;
    }
    
    echo "<p><strong>Found " . count($orders) . " orders with this number:</strong></p>";
    echo "<table>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Client ID</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    foreach ($orders as $order) {
        $class = ($order['cliente_id'] == $cliente_id) ? 'class="highlight"' : '';
        echo "<tr $class>";
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

    // 3. Check orders for this specific client
    echo "<div class='card'>";
    echo "<h3>3. All Orders for Client ID: $cliente_id</h3>";
    
    $stmt_client_orders = $conn->prepare("
        SELECT id, numero_orden, subtotal, envio, total, estado, fecha_orden
        FROM pedidos 
        WHERE cliente_id = ?
        ORDER BY fecha_orden DESC
    ");
    $stmt_client_orders->execute([$cliente_id]);
    
    echo "<table>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    while ($row = $stmt_client_orders->fetch()) {
        $class = ($row['numero_orden'] == $order_number) ? 'class="highlight"' : '';
        echo "<tr $class>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['numero_orden']}</td>";
        echo "<td>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['estado']}</td>";
        echo "<td>{$row['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 4. Check order details for the specific order number
    foreach ($orders as $order) {
        if ($order['cliente_id'] == $cliente_id) {
            echo "<div class='card'>";
            echo "<h3>4. Order Details for Order ID: {$order['id']} ({$order_number})</h3>";
            
            $stmt_details = $conn->prepare("
                SELECT dp.id as detalle_id, dp.producto_id, dp.cantidad, dp.precio_unitario, dp.subtotal,
                       p.nombre as producto_nombre, p.id_vendedor,
                       v.nombre_empresa
                FROM detalle_pedidos dp
                JOIN productos p ON dp.producto_id = p.id
                JOIN vendedores v ON p.id_vendedor = v.id
                WHERE dp.orden_id = ?
                ORDER BY dp.id
            ");
            $stmt_details->execute([$order['id']]);
            
            echo "<table>";
            echo "<tr><th>Detail ID</th><th>Product ID</th><th>Product Name</th><th>Vendor</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr>";
            while ($detail = $stmt_details->fetch()) {
                echo "<tr>";
                echo "<td>{$detail['detalle_id']}</td>";
                echo "<td>{$detail['producto_id']}</td>";
                echo "<td>" . htmlspecialchars($detail['producto_nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($detail['nombre_empresa']) . "</td>";
                echo "<td>{$detail['cantidad']}</td>";
                echo "<td>$" . number_format($detail['precio_unitario'], 2) . "</td>";
                echo "<td>$" . number_format($detail['subtotal'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        }
    }

    // 5. Simulate the query from misPedidos.php
    echo "<div class='card'>";
    echo "<h3>5. Simulating misPedidos.php Query</h3>";
    
    $stmt_mis_pedidos = $conn->prepare("
        SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, o.estado, o.fecha_orden,
               COUNT(dp.id) as total_productos
        FROM pedidos o
        LEFT JOIN detalle_pedidos dp ON o.id = dp.orden_id
        WHERE o.cliente_id = ?
        GROUP BY o.id, o.numero_orden, o.subtotal, o.envio, o.total, o.estado, o.fecha_orden
        ORDER BY o.fecha_orden DESC
    ");
    $stmt_mis_pedidos->execute([$cliente_id]);
    
    echo "<p><strong>Results from misPedidos.php-style query:</strong></p>";
    echo "<table>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Total</th><th>Status</th><th>Products Count</th><th>Date</th></tr>";
    while ($row = $stmt_mis_pedidos->fetch()) {
        $class = ($row['numero_orden'] == $order_number) ? 'class="highlight"' : '';
        echo "<tr $class>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['numero_orden']}</td>";
        echo "<td>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['estado']}</td>";
        echo "<td>{$row['total_productos']}</td>";
        echo "<td>{$row['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 6. Check for potential issues with joins
    echo "<div class='card'>";
    echo "<h3>6. Checking for Join Issues</h3>";
    
    // Check if there are any orphaned detalle_pedidos records
    $stmt_orphans = $conn->prepare("
        SELECT dp.id, dp.orden_id, dp.producto_id
        FROM detalle_pedidos dp
        LEFT JOIN pedidos o ON dp.orden_id = o.id
        WHERE o.id IS NULL
    ");
    $stmt_orphans->execute();
    
    $orphans = [];
    while ($row = $stmt_orphans->fetch()) {
        $orphans[] = $row;
    }
    
    if (empty($orphans)) {
        echo "<p class='success'>✅ No orphaned order details found</p>";
    } else {
        echo "<p class='error'>⚠️ Found " . count($orphans) . " orphaned order details:</p>";
        foreach ($orphans as $orphan) {
            echo "<p>Detail ID: {$orphan['id']}, Order ID: {$orphan['orden_id']}, Product ID: {$orphan['producto_id']}</p>";
        }
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='card'>";
echo "<h3>💡 Analysis</h3>";
echo "<ul>";
echo "<li>If you see multiple rows with the same order number, there are duplicate orders in the database</li>";
echo "<li>If the misPedidos.php query shows the order twice, there might be an issue with the GROUP BY clause</li>";
echo "<li>Check if there are any JOIN issues or orphaned records</li>";
echo "<li>The highlighted rows show the specific order in question</li>";
echo "</ul>";
echo "</div>";
?>
