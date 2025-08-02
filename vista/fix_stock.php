<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Stock - Admin Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background-color: #007185;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background-color: #005d6b;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .alert {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .stock-negative {
            color: #dc3545;
            font-weight: bold;
        }
        .stock-positive {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Stock Synchronization Tool</h1>
        
        <div class="alert alert-info">
            <strong>ℹ️ About this tool:</strong><br>
            This tool fixes negative stock values and synchronizes the <code>stock</code> and <code>unidades</code> columns in your products database.
        </div>

        <?php
        require_once __DIR__ . '/../modelo/conexion.php';

        $action = $_GET['action'] ?? '';
        $showStatus = $_GET['status'] ?? false;

        if ($action === 'fix') {
            try {
                $db = DatabaseConnection::getInstance();
                $conn = $db->getConnection();
                
                echo "<div class='alert alert-success'>";
                echo "<h3>🔄 Processing Stock Fix...</h3>";
                
                // Get all products first
                $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos");
                $stmt->execute();
                
                $products = [];
                while ($row = $stmt->fetch()) {
                    $products[] = $row;
                }
                
                if (empty($products)) {
                    echo "<p>❌ No products found in database.</p>";
                } else {
                    echo "<p>✅ Found " . count($products) . " products to process.</p>";
                    
                    $fixed = 0;
                    $synced = 0;
                    
                    foreach ($products as $product) {
                        if ($product['stock'] < 0) {
                            // Fix negative stock - set to 0 or use unidades value if positive
                            $new_stock = max(0, intval($product['unidades'] ?? 0));
                            
                            $update_stmt = $conn->prepare("UPDATE productos SET stock = ?, unidades = ? WHERE id = ?");
                            $update_stmt->execute([$new_stock, $new_stock, $product['id']]);
                            $fixed++;
                            
                            echo "<p style='color: orange;'>🔧 Fixed product '{$product['nombre']}': Stock {$product['stock']} → {$new_stock}</p>";
                        } else {
                            // Sync unidades with stock
                            $update_stmt = $conn->prepare("UPDATE productos SET unidades = ? WHERE id = ?");
                            $update_stmt->execute([$product['stock'], $product['id']]);
                            $synced++;
                            
                            echo "<p style='color: blue;'>🔄 Synced product '{$product['nombre']}': Unidades updated to {$product['stock']}</p>";
                        }
                    }
                    
                    echo "<h4>📊 Summary:</h4>";
                    echo "<ul>";
                    echo "<li>🔧 Products with negative stock fixed: $fixed</li>";
                    echo "<li>🔄 Products synchronized: $synced</li>";
                    echo "<li>✅ Total products processed: " . count($products) . "</li>";
                    echo "</ul>";
                }
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>";
                echo "<h3>❌ Error:</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        }

        // Always show current status
        try {
            $db = DatabaseConnection::getInstance();
            $conn = $db->getConnection();
            
            echo "<h3>📋 Current Products Status</h3>";
            
            $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos ORDER BY id");
            $stmt->execute();
            
            $products = [];
            while ($row = $stmt->fetch()) {
                $products[] = $row;
            }
            
            if (empty($products)) {
                echo "<p>No products found in database.</p>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Product Name</th><th>Stock</th><th>Unidades</th><th>Status</th></tr>";
                
                $has_negative = false;
                $has_mismatch = false;
                
                foreach ($products as $product) {
                    $stock_class = $product['stock'] < 0 ? 'stock-negative' : 'stock-positive';
                    $status = '✅ OK';
                    
                    if ($product['stock'] < 0) {
                        $status = '❌ Negative Stock';
                        $has_negative = true;
                    } elseif ($product['stock'] != $product['unidades']) {
                        $status = '⚠️ Needs Sync';
                        $has_mismatch = true;
                    }
                    
                    echo "<tr>";
                    echo "<td>{$product['id']}</td>";
                    echo "<td>" . htmlspecialchars($product['nombre']) . "</td>";
                    echo "<td class='$stock_class'>{$product['stock']}</td>";
                    echo "<td>{$product['unidades']}</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                if ($has_negative || $has_mismatch) {
                    echo "<div class='alert alert-info'>";
                    echo "<h4>🔧 Action Required:</h4>";
                    if ($has_negative) {
                        echo "<p>❌ Some products have negative stock values that need to be fixed.</p>";
                    }
                    if ($has_mismatch) {
                        echo "<p>⚠️ Some products have mismatched stock and unidades values that need synchronization.</p>";
                    }
                    echo "<button class='btn btn-danger' onclick=\"window.location.href='?action=fix'\">Fix Stock Issues</button>";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-success'>";
                    echo "<h4>✅ All Good!</h4>";
                    echo "<p>All products have valid stock values and are properly synchronized.</p>";
                    echo "</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>";
            echo "<h3>❌ Database Error:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <hr>
        <h3>🛠️ Manual Actions</h3>
        <p>You can also run these SQL commands manually if needed:</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;">
            -- Fix negative stock values<br>
            UPDATE productos SET stock = GREATEST(0, stock) WHERE stock < 0;<br><br>
            -- Sync unidades with stock<br>
            UPDATE productos SET unidades = stock;<br><br>
            -- Check results<br>
            SELECT id, nombre, stock, unidades FROM productos ORDER BY id;
        </div>
        
        <p><a href="index.php" class="btn">← Back to Store</a></p>
    </div>
</body>
</html>
