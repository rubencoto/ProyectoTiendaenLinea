<?php
session_start();
require_once '../modelo/conexion.php';

if (empty($_SESSION['cliente_id'])) {
    echo "No hay sesión de cliente activa.";
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
echo "<h2>Debug Database Cart</h2>";
echo "<p>Cliente ID: $cliente_id</p>";

$conn = DatabaseConnection::getInstance()->getConnection();

// Check if items exist in carrito_persistente
try {
    echo "<h3>1. Items in carrito_persistente table:</h3>";
    $stmt = $conn->prepare("SELECT * FROM carrito_persistente WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "<p style='color: red;'>❌ NO ITEMS FOUND in carrito_persistente for cliente_id: $cliente_id</p>";
        
        // Check if there are ANY items in the table
        $stmt_all = $conn->prepare("SELECT COUNT(*) as total FROM carrito_persistente");
        $stmt_all->execute();
        $total = $stmt_all->fetch();
        echo "<p>Total items in carrito_persistente table: " . $total['total'] . "</p>";
        
        // Show all client IDs that have items
        $stmt_clients = $conn->prepare("SELECT DISTINCT cliente_id FROM carrito_persistente");
        $stmt_clients->execute();
        $client_ids = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Client IDs with cart items: " . implode(', ', array_column($client_ids, 'cliente_id')) . "</p>";
        
    } else {
        echo "<p style='color: green;'>✅ Found " . count($items) . " items</p>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Cliente ID</th><th>Producto ID</th><th>Cantidad</th><th>Fecha Agregado</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . $item['cliente_id'] . "</td>";
            echo "<td>" . $item['producto_id'] . "</td>";
            echo "<td>" . $item['cantidad'] . "</td>";
            echo "<td>" . $item['fecha_agregado'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test manual insert
echo "<h3>2. Test Manual Insert:</h3>";
try {
    // Try to insert a test item
    $stmt_insert = $conn->prepare("
        INSERT INTO carrito_persistente (cliente_id, producto_id, cantidad, fecha_agregado, fecha_actualizado) 
        VALUES (?, 1, 1, NOW(), NOW())
    ");
    $result = $stmt_insert->execute([$cliente_id]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Manual insert successful</p>";
        
        // Check if it appears now
        $stmt_check = $conn->prepare("SELECT * FROM carrito_persistente WHERE cliente_id = ?");
        $stmt_check->execute([$cliente_id]);
        $new_items = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Items after manual insert: " . count($new_items) . "</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Manual insert failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Manual insert error: " . $e->getMessage() . "</p>";
}

echo "<br><br><a href='index.php'>Go to catalog</a> | <a href='carrito.php'>Go to cart</a>";
?>
