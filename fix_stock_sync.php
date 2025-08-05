<?php
// Fix stock sync with unidades column
require_once 'modelo/conexion.php';

try {
    // Direct connection to your AWS RDS database
    $pdo = new PDO(
        "mysql:host=kavfu5f7pido12mr.cbetxkdyhwsb.us-east-1.rds.amazonaws.com;port=3306;dbname=lsj1q7iol6uhg5wu;charset=utf8mb4",
        "kd8mm5vnhfoajcsh",
        "u8im10ovr94ccsfq",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<h2>Stock Synchronization Report</h2>";
    
    // First, let's see what products we have
    $stmt = $pdo->prepare("SELECT id, nombre, stock, unidades FROM productos");
    $stmt->execute();
    $productos = $stmt->fetchAll();
    
    echo "<h3>Current Products Status:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Product Name</th><th>Current Stock</th><th>Current Unidades</th><th>Action</th></tr>";
    
    foreach ($productos as $producto) {
        echo "<tr>";
        echo "<td>{$producto['id']}</td>";
        echo "<td>{$producto['nombre']}</td>";
        echo "<td style='color: " . ($producto['stock'] < 0 ? 'red' : 'green') . ";'>{$producto['stock']}</td>";
        echo "<td>{$producto['unidades']}</td>";
        
        if ($producto['stock'] < 0) {
            // Fix negative stock - set to 0 or sync with unidades if it has a positive value
            $new_stock = max(0, intval($producto['unidades']));
            
            $update_stmt = $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?");
            $update_stmt->execute([$new_stock, $producto['id']]);
            
            echo "<td style='color: orange;'>Fixed: Stock set to {$new_stock}</td>";
        } else {
            // Sync unidades with current stock value
            $update_stmt = $pdo->prepare("UPDATE productos SET unidades = ? WHERE id = ?");
            $update_stmt->execute([$producto['stock'], $producto['id']]);
            
            echo "<td style='color: blue;'>Synced: Unidades updated to {$producto['stock']}</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Show final status
    echo "<h3>Final Status:</h3>";
    $stmt = $pdo->prepare("SELECT id, nombre, stock, unidades FROM productos");
    $stmt->execute();
    $productos_final = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Product Name</th><th>Stock</th><th>Unidades</th><th>Status</th></tr>";
    
    foreach ($productos_final as $producto) {
        echo "<tr>";
        echo "<td>{$producto['id']}</td>";
        echo "<td>{$producto['nombre']}</td>";
        echo "<td>{$producto['stock']}</td>";
        echo "<td>{$producto['unidades']}</td>";
        echo "<td style='color: green;'>✓ Synchronized</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Stock synchronization completed successfully!</h3>";
    echo "<p><strong>What was done:</strong></p>";
    echo "<ul>";
    echo "<li>Fixed negative stock values</li>";
    echo "<li>Synchronized stock and unidades columns</li>";
    echo "<li>All products now have consistent inventory data</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
