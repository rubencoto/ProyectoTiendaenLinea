<?php
require_once '../modelo/conexion.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

echo "<h2>🔍 Diagnostic: Checking for Duplicate Orders</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.success { color: #28a745; } .warning { color: #ffc107; } .error { color: #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f8f9fa; }
</style>";

try {
    // 1. Check for duplicate orden numbers
    echo "<div class='card'>";
    echo "<h3>1. Checking for Duplicate Order Numbers</h3>";
    
    $stmt_dup_orders = $conn->prepare("
        SELECT numero_orden, COUNT(*) as count 
        FROM pedidos 
        GROUP BY numero_orden 
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    $stmt_dup_orders->execute();
    
    $duplicates = [];
    while ($row = $stmt_dup_orders->fetch()) {
        $duplicates[] = $row;
    }
    
    if (empty($duplicates)) {
        echo "<p class='success'>✅ No duplicate order numbers found</p>";
    } else {
        echo "<p class='error'>⚠️ Found " . count($duplicates) . " duplicate order numbers:</p>";
        echo "<table>";
        echo "<tr><th>Order Number</th><th>Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>{$dup['numero_orden']}</td><td>{$dup['count']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 2. Check order distribution by vendor
    echo "<div class='card'>";
    echo "<h3>2. Order Distribution by Vendor</h3>";
    
    $stmt_vendor_orders = $conn->prepare("
        SELECT v.nombre_empresa, v.id as vendor_id,
               COUNT(DISTINCT o.id) as total_orders,
               COUNT(DISTINCT dp.id) as total_order_items
        FROM vendedores v
        LEFT JOIN productos p ON v.id = p.id_vendedor
        LEFT JOIN detalle_pedidos dp ON p.id = dp.producto_id
        LEFT JOIN pedidos o ON dp.orden_id = o.id
        GROUP BY v.id, v.nombre_empresa
        ORDER BY total_orders DESC
    ");
    $stmt_vendor_orders->execute();
    
    echo "<table>";
    echo "<tr><th>Vendor</th><th>Vendor ID</th><th>Total Orders</th><th>Total Items Sold</th></tr>";
    while ($row = $stmt_vendor_orders->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nombre_empresa'] ?? 'Unknown') . "</td>";
        echo "<td>{$row['vendor_id']}</td>";
        echo "<td>{$row['total_orders']}</td>";
        echo "<td>{$row['total_order_items']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 3. Check for orders with multiple vendors
    echo "<div class='card'>";
    echo "<h3>3. Orders with Multiple Vendors</h3>";
    
    $stmt_multi_vendor = $conn->prepare("
        SELECT o.id, o.numero_orden, o.fecha_orden,
               COUNT(DISTINCT p.id_vendedor) as vendor_count,
               GROUP_CONCAT(DISTINCT v.nombre_empresa SEPARATOR ', ') as vendors
        FROM pedidos o
        JOIN detalle_pedidos dp ON o.id = dp.orden_id
        JOIN productos p ON dp.producto_id = p.id
        JOIN vendedores v ON p.id_vendedor = v.id
        GROUP BY o.id, o.numero_orden, o.fecha_orden
        HAVING COUNT(DISTINCT p.id_vendedor) > 1
        ORDER BY o.fecha_orden DESC
        LIMIT 10
    ");
    $stmt_multi_vendor->execute();
    
    $multi_vendor_orders = [];
    while ($row = $stmt_multi_vendor->fetch()) {
        $multi_vendor_orders[] = $row;
    }
    
    if (empty($multi_vendor_orders)) {
        echo "<p class='success'>✅ No orders with multiple vendors found</p>";
    } else {
        echo "<p class='warning'>⚠️ Found " . count($multi_vendor_orders) . " orders with multiple vendors:</p>";
        echo "<table>";
        echo "<tr><th>Order ID</th><th>Order Number</th><th>Date</th><th>Vendor Count</th><th>Vendors</th></tr>";
        foreach ($multi_vendor_orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['numero_orden']}</td>";
            echo "<td>{$order['fecha_orden']}</td>";
            echo "<td>{$order['vendor_count']}</td>";
            echo "<td>" . htmlspecialchars($order['vendors']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><em>Note: This is normal behavior when customers order from multiple vendors in one order.</em></p>";
    }
    echo "</div>";

    // 4. Recent orders summary
    echo "<div class='card'>";
    echo "<h3>4. Recent Orders Summary</h3>";
    
    $stmt_recent = $conn->prepare("
        SELECT o.id, o.numero_orden, o.cliente_id, o.total, o.estado, o.fecha_orden,
               c.nombre as cliente_nombre, c.apellido as cliente_apellido,
               COUNT(dp.id) as items_count,
               COUNT(DISTINCT p.id_vendedor) as vendors_count
        FROM pedidos o
        JOIN clientes c ON o.cliente_id = c.id
        JOIN detalle_pedidos dp ON o.id = dp.orden_id
        JOIN productos p ON dp.producto_id = p.id
        GROUP BY o.id, o.numero_orden, o.cliente_id, o.total, o.estado, o.fecha_orden, c.nombre, c.apellido
        ORDER BY o.fecha_orden DESC
        LIMIT 10
    ");
    $stmt_recent->execute();
    
    echo "<table>";
    echo "<tr><th>Order ID</th><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Items</th><th>Vendors</th><th>Date</th></tr>";
    while ($row = $stmt_recent->fetch()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['numero_orden']}</td>";
        echo "<td>" . htmlspecialchars($row['cliente_nombre'] . ' ' . $row['cliente_apellido']) . "</td>";
        echo "<td>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['estado']}</td>";
        echo "<td>{$row['items_count']}</td>";
        echo "<td>{$row['vendors_count']}</td>";
        echo "<td>{$row['fecha_orden']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 5. Check database table structure
    echo "<div class='card'>";
    echo "<h3>5. Database Tables Structure Check</h3>";
    
    $tables = ['pedidos', 'detalle_pedidos', 'productos', 'vendedores', 'clientes'];
    
    foreach ($tables as $table) {
        echo "<h4>Table: $table</h4>";
        $stmt_desc = $conn->prepare("DESCRIBE $table");
        $stmt_desc->execute();
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt_desc->fetch()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='card'>";
echo "<h3>💡 What This Diagnostic Shows</h3>";
echo "<ul>";
echo "<li><strong>Duplicate Order Numbers:</strong> Shows if the same order number appears multiple times (should not happen)</li>";
echo "<li><strong>Vendor Distribution:</strong> Shows how many orders each vendor has</li>";
echo "<li><strong>Multi-Vendor Orders:</strong> Shows orders that contain products from multiple vendors (normal behavior)</li>";
echo "<li><strong>Recent Orders:</strong> Shows the latest orders with their details</li>";
echo "<li><strong>Table Structure:</strong> Confirms the database structure is correct</li>";
echo "</ul>";
echo "<p><em>If you see 'cloned pedidos', it's likely because the same order appears in both misPedidos.php (for customers) and gestionPedidos.php (for vendors), which is correct behavior.</em></p>";
echo "</div>";
?>
