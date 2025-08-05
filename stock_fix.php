<?php
// Simple stock fix script
require_once 'modelo/conexion.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Stock Fix</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h2>🔧 Stock Fix Tool</h2>";

try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    // Show current status
    echo "<h3>Current Products:</h3>";
    $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos");
    $stmt->execute();
    
    $products = [];
    while ($row = $stmt->fetch()) {
        $products[] = $row;
    }
    
    if (empty($products)) {
        echo "<p class='error'>No products found.</p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Stock</th><th>Unidades</th><th>Status</th></tr>";
        
        foreach ($products as $product) {
            $status_class = $product['stock'] < 0 ? 'error' : 'success';
            $status_text = $product['stock'] < 0 ? 'NEGATIVE' : 'OK';
            
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['nombre']}</td>";
            echo "<td class='$status_class'>{$product['stock']}</td>";
            echo "<td>{$product['unidades']}</td>";
            echo "<td class='$status_class'>$status_text</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Auto-fix if requested
        if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
            echo "<h3 class='info'>🔄 Fixing stock issues...</h3>";
            
            foreach ($products as $product) {
                if ($product['stock'] < 0) {
                    $new_stock = max(0, intval($product['unidades'] ?? 0));
                    
                    $update_stmt = $conn->prepare("UPDATE productos SET stock = ?, unidades = ? WHERE id = ?");
                    $update_stmt->execute([$new_stock, $new_stock, $product['id']]);
                    
                    echo "<p class='success'>✅ Fixed {$product['nombre']}: {$product['stock']} → {$new_stock}</p>";
                } else {
                    $update_stmt = $conn->prepare("UPDATE productos SET unidades = ? WHERE id = ?");
                    $update_stmt->execute([$product['stock'], $product['id']]);
                    
                    echo "<p class='info'>🔄 Synced {$product['nombre']}: unidades = {$product['stock']}</p>";
                }
            }
            
            echo "<p class='success'><strong>✅ Stock fix completed!</strong></p>";
            echo "<p><a href='?'>View Results</a></p>";
        } else {
            // Show fix button if there are negative stocks
            $has_negative = false;
            foreach ($products as $product) {
                if ($product['stock'] < 0) {
                    $has_negative = true;
                    break;
                }
            }
            
            if ($has_negative) {
                echo "<p class='error'>⚠️ Found products with negative stock!</p>";
                echo "<p><a href='?fix=true' style='background:#007185;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔧 Fix Stock Issues</a></p>";
            } else {
                echo "<p class='success'>✅ All products have valid stock values!</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='vista/index.php'>← Back to Store</a></p>";
echo "</body></html>";
?>
