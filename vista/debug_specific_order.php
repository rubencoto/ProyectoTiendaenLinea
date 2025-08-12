<?php
require_once '../modelo/conexion.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

$email = 'leomoya55@yahoo.com';

echo "<h2>🔍 Detailed Analysis of misPedidos.php Query for $email</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.success { color: #28a745; } .warning { color: #ffc107; } .error { color: #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f8f9fa; }
.highlight { background: #fff3cd; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

try {
    // Get client info
    $stmt_client = $conn->prepare("SELECT * FROM clientes WHERE correo = ?");
    $stmt_client->execute([$email]);
    $client = $stmt_client->fetch();
    
    if (!$client) {
        echo "<p class='error'>Client not found</p>";
        exit;
    }
    
    $cliente_id = $client['id'];
    echo "<div class='card'>";
    echo "<h3>Client Info</h3>";
    echo "<p><strong>Client ID:</strong> $cliente_id</p>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) . "</p>";
    echo "</div>";

    // Execute the exact same query as misPedidos.php
    echo "<div class='card'>";
    echo "<h3>1. Exact misPedidos.php Query</h3>";
    
    $limite = 10;
    $offset = 0;
    
    echo "<pre>Query:
SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
       o.estado, o.fecha_orden
FROM pedidos o 
WHERE o.cliente_id = ? 
ORDER BY o.fecha_orden DESC 
LIMIT $limite OFFSET $offset</pre>";

    $stmt_ordenes = $conn->prepare("
        SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
               o.estado, o.fecha_orden
        FROM pedidos o 
        WHERE o.cliente_id = ? 
        ORDER BY o.fecha_orden DESC 
        LIMIT $limite OFFSET $offset
    ");
    $stmt_ordenes->execute([$cliente_id]);
    
    $ordenes = [];
    echo "<table>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    
    while ($row = $stmt_ordenes->fetch()) {
        $ordenes[] = $row;
        $class = ($row['numero_orden'] == 'ORD-2025-945427') ? 'class="highlight"' : '';
        echo "<tr $class>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['numero_orden']}</td>";
        echo "<td>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['estado']}</td>";
        echo "<td>{$row['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total orders returned:</strong> " . count($ordenes) . "</p>";
    echo "</div>";

    // Check for the specific order
    $order_945427_count = 0;
    foreach ($ordenes as $orden) {
        if ($orden['numero_orden'] == 'ORD-2025-945427') {
            $order_945427_count++;
        }
    }
    
    echo "<div class='card'>";
    echo "<h3>2. ORD-2025-945427 Analysis</h3>";
    echo "<p><strong>Count of ORD-2025-945427 in results:</strong> $order_945427_count</p>";
    
    if ($order_945427_count > 1) {
        echo "<p class='error'>❌ DUPLICATE FOUND! The order appears $order_945427_count times</p>";
        
        // Check if there are actually duplicate records in the database
        $stmt_dup_check = $conn->prepare("
            SELECT COUNT(*) as count
            FROM pedidos 
            WHERE numero_orden = 'ORD-2025-945427' AND cliente_id = ?
        ");
        $stmt_dup_check->execute([$cliente_id]);
        $dup_result = $stmt_dup_check->fetch();
        
        echo "<p><strong>Database records for ORD-2025-945427:</strong> {$dup_result['count']}</p>";
        
        if ($dup_result['count'] > 1) {
            echo "<p class='error'>🚨 PROBLEM: There are multiple database records with the same order number!</p>";
            
            // Show all records with this order number
            $stmt_all_dups = $conn->prepare("
                SELECT id, numero_orden, cliente_id, total, estado, fecha_orden
                FROM pedidos 
                WHERE numero_orden = 'ORD-2025-945427'
                ORDER BY id
            ");
            $stmt_all_dups->execute();
            
            echo "<h4>All database records for ORD-2025-945427:</h4>";
            echo "<table>";
            echo "<tr><th>Order ID</th><th>Order Number</th><th>Client ID</th><th>Total</th><th>Status</th><th>Date</th></tr>";
            while ($dup_row = $stmt_all_dups->fetch()) {
                $class = ($dup_row['cliente_id'] == $cliente_id) ? 'class="highlight"' : '';
                echo "<tr $class>";
                echo "<td>{$dup_row['id']}</td>";
                echo "<td>{$dup_row['numero_orden']}</td>";
                echo "<td>{$dup_row['cliente_id']}</td>";
                echo "<td>$" . number_format($dup_row['total'], 2) . "</td>";
                echo "<td>{$dup_row['estado']}</td>";
                echo "<td>{$dup_row['fecha_orden']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else if ($order_945427_count == 1) {
        echo "<p class='success'>✅ Order appears only once in the query results</p>";
    } else {
        echo "<p class='warning'>⚠️ Order not found in current page results</p>";
    }
    echo "</div>";

    // Check the order details for this specific order
    echo "<div class='card'>";
    echo "<h3>3. Order Details Processing</h3>";
    
    foreach ($ordenes as $orden) {
        if ($orden['numero_orden'] == 'ORD-2025-945427') {
            echo "<h4>Processing Order ID: {$orden['id']}</h4>";
            
            $stmt_detalle = $conn->prepare("
                SELECT dp.cantidad, dp.precio_unitario, dp.subtotal, dp.producto_id,
                       p.nombre as producto_nombre, p.imagen_principal
                FROM detalle_pedidos dp
                JOIN productos p ON dp.producto_id = p.id
                WHERE dp.orden_id = ? 
                AND EXISTS (SELECT 1 FROM pedidos o WHERE o.id = dp.orden_id)
            ");
            $stmt_detalle->execute([$orden['id']]);
            
            echo "<table>";
            echo "<tr><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr>";
            $product_count = 0;
            while ($detalle = $stmt_detalle->fetch()) {
                $product_count++;
                echo "<tr>";
                echo "<td>{$detalle['producto_id']}</td>";
                echo "<td>" . htmlspecialchars($detalle['producto_nombre']) . "</td>";
                echo "<td>{$detalle['cantidad']}</td>";
                echo "<td>$" . number_format($detalle['precio_unitario'], 2) . "</td>";
                echo "<td>$" . number_format($detalle['subtotal'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Products in this order:</strong> $product_count</p>";
        }
    }
    echo "</div>";

    // Check for recent similar orders that might be getting mixed up
    echo "<div class='card'>";
    echo "<h3>4. Recent Orders Pattern Analysis</h3>";
    
    $stmt_pattern = $conn->prepare("
        SELECT o.id, o.numero_orden, o.total, o.estado, o.fecha_orden,
               COUNT(dp.id) as item_count
        FROM pedidos o
        LEFT JOIN detalle_pedidos dp ON o.id = dp.orden_id
        WHERE o.cliente_id = ?
        GROUP BY o.id, o.numero_orden, o.total, o.estado, o.fecha_orden
        ORDER BY o.fecha_orden DESC
        LIMIT 15
    ");
    $stmt_pattern->execute([$cliente_id]);
    
    echo "<table>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Total</th><th>Status</th><th>Items</th><th>Date</th></tr>";
    while ($pattern_row = $stmt_pattern->fetch()) {
        $class = ($pattern_row['numero_orden'] == 'ORD-2025-945427') ? 'class="highlight"' : '';
        echo "<tr $class>";
        echo "<td>{$pattern_row['id']}</td>";
        echo "<td>{$pattern_row['numero_orden']}</td>";
        echo "<td>$" . number_format($pattern_row['total'], 2) . "</td>";
        echo "<td>{$pattern_row['estado']}</td>";
        echo "<td>{$pattern_row['item_count']}</td>";
        echo "<td>{$pattern_row['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='card'>";
echo "<h3>💡 Diagnosis Steps</h3>";
echo "<ol>";
echo "<li><strong>If order appears multiple times in query results:</strong> There are duplicate records in the pedidos table</li>";
echo "<li><strong>If database count > 1:</strong> The same order number was inserted multiple times</li>";
echo "<li><strong>If different Order IDs have same number:</strong> There's an issue with order number generation</li>";
echo "<li><strong>If same Order ID appears twice:</strong> There's an issue with the query or display logic</li>";
echo "</ol>";
echo "</div>";
?>
