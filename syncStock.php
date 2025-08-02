<?php
/**
 * Script to sync stock column with unidades column in productos table
 * This will copy all unidades values to stock and ensure they're in sync
 */

require_once 'modelo/conexion.php';

echo "<h2>Stock Synchronization Script</h2>\n";

try {
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    // First, let's see the current state
    echo "<h3>Current State Before Sync:</h3>\n";
    $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos ORDER BY id");
    $stmt->execute();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>Nombre</th><th>Stock</th><th>Unidades</th><th>Diferencia</th></tr>\n";
    
    $products_to_sync = [];
    while ($row = $stmt->fetch()) {
        $difference = ($row['stock'] !== $row['unidades']) ? '❌ Diferente' : '✅ Igual';
        echo "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['nombre']) . "</td><td>{$row['stock']}</td><td>{$row['unidades']}</td><td>$difference</td></tr>\n";
        
        if ($row['stock'] !== $row['unidades']) {
            $products_to_sync[] = $row;
        }
    }
    echo "</table>\n";
    
    if (empty($products_to_sync)) {
        echo "<p>✅ <strong>All products are already in sync!</strong></p>\n";
    } else {
        echo "<p>🔄 Found " . count($products_to_sync) . " products that need synchronization.</p>\n";
        
        // Sync stock with unidades
        $stmt_update = $conn->prepare("UPDATE productos SET stock = unidades WHERE id = ?");
        $updated_count = 0;
        
        foreach ($products_to_sync as $product) {
            $stmt_update->execute([$product['id']]);
            if ($stmt_update->rowCount() > 0) {
                $updated_count++;
                echo "<p>✅ Updated product ID {$product['id']} ('{$product['nombre']}'): stock {$product['stock']} → {$product['unidades']}</p>\n";
            }
        }
        
        echo "<h3>Sync Results:</h3>\n";
        echo "<p><strong>✅ Successfully updated $updated_count products</strong></p>\n";
        
        // Show final state
        echo "<h3>Final State After Sync:</h3>\n";
        $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos ORDER BY id");
        $stmt->execute();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>ID</th><th>Nombre</th><th>Stock</th><th>Unidades</th><th>Status</th></tr>\n";
        
        while ($row = $stmt->fetch()) {
            $status = ($row['stock'] === $row['unidades']) ? '✅ Sincronizado' : '❌ Error';
            echo "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['nombre']) . "</td><td>{$row['stock']}</td><td>{$row['unidades']}</td><td>$status</td></tr>\n";
        }
        echo "</table>\n";
    }
    
    // Fix any negative stock values
    echo "<h3>Fixing Negative Stock Values:</h3>\n";
    $stmt_negative = $conn->prepare("SELECT id, nombre, stock FROM productos WHERE stock < 0");
    $stmt_negative->execute();
    
    $negative_products = [];
    while ($row = $stmt_negative->fetch()) {
        $negative_products[] = $row;
    }
    
    if (empty($negative_products)) {
        echo "<p>✅ No negative stock values found!</p>\n";
    } else {
        echo "<p>⚠️ Found " . count($negative_products) . " products with negative stock:</p>\n";
        
        $stmt_fix = $conn->prepare("UPDATE productos SET stock = 0 WHERE id = ?");
        foreach ($negative_products as $product) {
            echo "<p>🔧 Fixing product ID {$product['id']} ('{$product['nombre']}'): stock {$product['stock']} → 0</p>\n";
            $stmt_fix->execute([$product['id']]);
        }
        
        echo "<p><strong>✅ All negative stock values have been set to 0</strong></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("Stock sync error: " . $e->getMessage());
}

echo "<hr>\n";
echo "<p><strong>Stock synchronization completed!</strong></p>\n";
echo "<p><a href='vista/index.php'>← Back to Store</a> | <a href='vista/inicioVendedor.php'>Vendor Dashboard</a></p>\n";
?>
