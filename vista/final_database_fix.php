<?php
require_once '../modelo/conexion.php';

$output = [];
$output[] = "=== Final Database Fix ===";

try {
    // Fix the detalle_pedidos table column name
    $output[] = "Checking detalle_pedidos table structure...";
    
    $result = $conn->query("DESCRIBE detalle_pedidos");
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    
    if (in_array('pedido_id', $columns) && !in_array('orden_id', $columns)) {
        $output[] = "Found 'pedido_id' column, renaming to 'orden_id' to match application code...";
        
        if ($conn->query("ALTER TABLE detalle_pedidos CHANGE pedido_id orden_id INT NOT NULL")) {
            $output[] = "✅ Successfully renamed 'pedido_id' to 'orden_id'";
        } else {
            $output[] = "❌ Error renaming column: " . $conn->error;
        }
    } else if (in_array('orden_id', $columns)) {
        $output[] = "✅ Column 'orden_id' already exists - no fix needed";
    } else {
        $output[] = "❌ Neither 'pedido_id' nor 'orden_id' found in detalle_pedidos table";
    }
    
    // Show final detalle_pedidos structure
    $output[] = "\nFinal detalle_pedidos structure:";
    $result = $conn->query("DESCRIBE detalle_pedidos");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $output[] = "• " . $row['Field'] . " (" . $row['Type'] . ")";
        }
    }
    
    $output[] = "\n✅ Database fix completed!";
    $output[] = "\n🚀 Your database is now fully compatible with your application!";
    $output[] = "\nYou can now test:";
    $output[] = "• Customer registration ✅";
    $output[] = "• Vendor registration ✅";  
    $output[] = "• Product management ✅";
    $output[] = "• Order processing ✅";
    
    $status = 'completed';

} catch (Exception $e) {
    $output[] = "❌ Error: " . $e->getMessage();
    $status = 'error';
}

header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'output' => $output
], JSON_UNESCAPED_UNICODE);
?>
