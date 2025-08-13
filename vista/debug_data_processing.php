<?php
session_start();
require_once '../modelo/conexion.php';

if (!isset($_SESSION['cliente_id'])) {
    die("Please log in first");
}

$cliente_id = $_SESSION['cliente_id'];

// Get database connection
$db = DatabaseConnection::getInstance();
$pdo_conn = $db->getConnection();

echo "<h2>🔍 Debug: Data Processing in misPedidos.php</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.debug { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 8px; }
.step { border-left: 4px solid #007185; padding-left: 15px; margin: 10px 0; }
</style>";

// Step 1: Initial order query (exactly like misPedidos.php)
echo "<div class='debug'>";
echo "<h3>Step 1: Initial Order Query</h3>";

$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

$stmt_ordenes = $pdo_conn->prepare("
    SELECT o.id, o.numero_orden, o.subtotal, o.envio, o.total, 
           o.estado, o.fecha_orden
    FROM pedidos o 
    WHERE o.cliente_id = ? 
    ORDER BY o.fecha_orden DESC 
    LIMIT $limite OFFSET $offset
");
$stmt_ordenes->execute([$cliente_id]);

$ordenes = [];
while ($row = $stmt_ordenes->fetch()) {
    $ordenes[] = $row;
}

echo "<p>Orders found: " . count($ordenes) . "</p>";
$target_count_step1 = 0;
foreach ($ordenes as $orden) {
    if ($orden['numero_orden'] == 'ORD-2025-945427') {
        $target_count_step1++;
    }
}
echo "<p><strong>ORD-2025-945427 count after initial query: $target_count_step1</strong></p>";
echo "</div>";

// Step 2: Data processing loop (exactly like misPedidos.php)
echo "<div class='debug'>";
echo "<h3>Step 2: Data Processing Loop</h3>";

echo "<p>Starting data processing loop...</p>";

foreach ($ordenes as &$orden) {
    echo "<div class='step'>";
    echo "<p>Processing order ID: {$orden['id']} ({$orden['numero_orden']})</p>";
    
    $stmt_detalle = $pdo_conn->prepare("
        SELECT dp.cantidad, dp.precio_unitario, dp.subtotal, dp.producto_id,
               p.nombre as producto_nombre, p.imagen_principal
        FROM detalle_pedidos dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.orden_id = ? 
        AND EXISTS (SELECT 1 FROM pedidos o WHERE o.id = dp.orden_id)
    ");
    $stmt_detalle->execute([$orden['id']]);
    
    $productos = [];
    while ($row_detalle = $stmt_detalle->fetch()) {
        if ($row_detalle['imagen_principal']) {
            $row_detalle['imagen_principal'] = base64_encode($row_detalle['imagen_principal']);
        }
        
        // Check if customer has already reviewed this product
        $stmt_review = $pdo_conn->prepare("
            SELECT id, estrellas, comentario, fecha 
            FROM reseñas 
            WHERE cliente_id = ? AND producto_id = ?
        ");
        $stmt_review->execute([$cliente_id, $row_detalle['producto_id']]);
        $existing_review = $stmt_review->fetch();
        
        $row_detalle['existing_review'] = $existing_review;
        $productos[] = $row_detalle;
    }
    
    $orden['productos'] = $productos;
    echo "<p>Added " . count($productos) . " products to this order</p>";
    echo "</div>";
}

echo "<p>Data processing loop completed.</p>";

// Count again after processing
$target_count_step2 = 0;
foreach ($ordenes as $orden) {
    if ($orden['numero_orden'] == 'ORD-2025-945427') {
        $target_count_step2++;
    }
}
echo "<p><strong>ORD-2025-945427 count after processing: $target_count_step2</strong></p>";
echo "</div>";

// Step 3: Display simulation
echo "<div class='debug'>";
echo "<h3>Step 3: Display Simulation</h3>";

echo "<p>Simulating the display loop...</p>";
$display_count = 0;

foreach ($ordenes as $orden) {
    if ($orden['numero_orden'] == 'ORD-2025-945427') {
        $display_count++;
        echo "<div style='border: 2px solid red; padding: 10px; margin: 5px 0; background: #ffe6e6;'>";
        echo "<strong>DISPLAYING ORD-2025-945427 (Occurrence #$display_count)</strong><br>";
        echo "Order ID: {$orden['id']}<br>";
        echo "Total: ₡" . number_format($orden['total'], 2) . "<br>";
        echo "Products: " . count($orden['productos']) . "<br>";
        echo "</div>";
    } else {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
        echo "Order: {$orden['numero_orden']} (ID: {$orden['id']})<br>";
        echo "Products: " . count($orden['productos']) . "<br>";
        echo "</div>";
    }
}

echo "<p><strong>Total times ORD-2025-945427 was displayed: $display_count</strong></p>";
echo "</div>";

// Step 4: Analysis
echo "<div class='debug'>";
echo "<h3>Step 4: Analysis</h3>";

if ($target_count_step1 != $target_count_step2) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ PROBLEM DETECTED: Order count changed during processing!</p>";
    echo "<p>Before processing: $target_count_step1</p>";
    echo "<p>After processing: $target_count_step2</p>";
    echo "<p>This suggests the data processing loop is somehow duplicating orders.</p>";
} else if ($display_count > 1) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ DISPLAY DUPLICATION DETECTED!</p>";
    echo "<p>Order appears $display_count times in display, but only $target_count_step2 times in data.</p>";
    echo "<p>This suggests a frontend/JavaScript issue in the actual misPedidos.php file.</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ NO DUPLICATION DETECTED IN THIS TEST</p>";
    echo "<p>The order processing and display simulation work correctly.</p>";
    echo "<p>The issue might be specific to the actual misPedidos.php file's HTML/JavaScript.</p>";
}
echo "</div>";

// Step 5: Raw data dump
echo "<div class='debug'>";
echo "<h3>Step 5: Raw Data Structure</h3>";
echo "<pre>";
foreach ($ordenes as $index => $orden) {
    if ($orden['numero_orden'] == 'ORD-2025-945427') {
        echo "=== ORDER INDEX $index (ORD-2025-945427) ===\n";
        print_r($orden);
        echo "\n";
    }
}
echo "</pre>";
echo "</div>";
?>
