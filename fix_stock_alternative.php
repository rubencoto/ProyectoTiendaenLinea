<?php
// Alternative stock fix using existing connection system
require_once 'modelo/conexion.php';

echo "<h2>Stock Synchronization Using Existing Connection</h2>";

try {
    // Get database connection through your existing system
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    echo "<p>Connection type: " . $db->getConnectionType() . "</p>";
    
    if ($db->getConnectionType() === 'mock') {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>⚠️ Notice:</strong> Using mock connection locally. ";
        echo "This script will work properly when deployed to Heroku with your AWS RDS database.";
        echo "</div>";
        
        echo "<p>To fix your stock manually in the database, run these SQL commands:</p>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "-- Fix negative stock (set to 0 if negative)\n";
        echo "UPDATE productos SET stock = GREATEST(0, stock) WHERE stock < 0;\n\n";
        echo "-- Sync unidades with stock\n";
        echo "UPDATE productos SET unidades = stock;\n\n";
        echo "-- Check results\n";
        echo "SELECT id, nombre, stock, unidades FROM productos;";
        echo "</pre>";
    } else {
        // If we have a real connection, proceed with the fix
        echo "<h3>Processing Products...</h3>";
        
        // Get all products
        $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos");
        $stmt->execute();
        
        $productos = [];
        while ($row = $stmt->fetch()) {
            $productos[] = $row;
        }
        
        if (empty($productos)) {
            echo "<p>No products found in database.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Product Name</th><th>Current Stock</th><th>Current Unidades</th><th>Action</th></tr>";
            
            foreach ($productos as $producto) {
                echo "<tr>";
                echo "<td>{$producto['id']}</td>";
                echo "<td>{$producto['nombre']}</td>";
                echo "<td style='color: " . ($producto['stock'] < 0 ? 'red' : 'green') . ";'>{$producto['stock']}</td>";
                echo "<td>{$producto['unidades']}</td>";
                
                if ($producto['stock'] < 0) {
                    // Fix negative stock
                    $new_stock = max(0, intval($producto['unidades'] ?? 0));
                    
                    $update_stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
                    $update_stmt->execute([$new_stock, $producto['id']]);
                    
                    echo "<td style='color: orange;'>Fixed: Stock set to {$new_stock}</td>";
                } else {
                    // Sync unidades with stock
                    $update_stmt = $conn->prepare("UPDATE productos SET unidades = ? WHERE id = ?");
                    $update_stmt->execute([$producto['stock'], $producto['id']]);
                    
                    echo "<td style='color: blue;'>Synced: Unidades updated to {$producto['stock']}</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            echo "<h3>✅ Stock synchronization completed!</h3>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>✅ Deploy this to Heroku to fix your live database</li>";
echo "<li>✅ Update cart system to prevent negative stock</li>";
echo "<li>✅ Update order processing with stock validation</li>";
echo "</ul>";
?>
