<?php
require_once 'modelo/conexion.php';

echo "<h2>Sincronización de Stock</h2>\n";

try {
    // Get database connection
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    // First, let's see the current state
    echo "<h3>Estado actual de productos:</h3>\n";
    $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos");
    $stmt->execute();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>Nombre</th><th>Stock Actual</th><th>Unidades Actual</th></tr>\n";
    
    $productos = [];
    while ($row = $stmt->fetch()) {
        $productos[] = $row;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td style='color: " . ($row['stock'] < 0 ? 'red' : 'green') . "'>{$row['stock']}</td>";
        echo "<td>{$row['unidades']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Fix negative stock and sync with unidades
    echo "<h3>Aplicando correcciones:</h3>\n";
    
    foreach ($productos as $producto) {
        $nuevo_stock = max(0, $producto['unidades']); // Use unidades as the source of truth, minimum 0
        
        if ($producto['stock'] != $nuevo_stock) {
            $stmt_update = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
            $stmt_update->execute([$nuevo_stock, $producto['id']]);
            
            echo "<p>✅ Producto '{$producto['nombre']}' (ID: {$producto['id']}): ";
            echo "Stock actualizado de {$producto['stock']} a {$nuevo_stock}</p>\n";
        } else {
            echo "<p>✔️ Producto '{$producto['nombre']}' (ID: {$producto['id']}): Stock ya está correcto ({$nuevo_stock})</p>\n";
        }
    }
    
    // Show final state
    echo "<h3>Estado final de productos:</h3>\n";
    $stmt = $conn->prepare("SELECT id, nombre, stock, unidades FROM productos");
    $stmt->execute();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>Nombre</th><th>Stock Final</th><th>Unidades</th><th>Estado</th></tr>\n";
    
    while ($row = $stmt->fetch()) {
        $estado = $row['stock'] > 0 ? "✅ Disponible" : "❌ Agotado";
        $color = $row['stock'] > 0 ? "green" : "red";
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td style='color: {$color}'>{$row['stock']}</td>";
        echo "<td>{$row['unidades']}</td>";
        echo "<td style='color: {$color}'>{$estado}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<p><strong>✅ Sincronización completada exitosamente!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error durante la sincronización: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
