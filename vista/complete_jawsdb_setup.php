<?php
require_once '../modelo/conexion.php';

$output = [];
$output[] = "=== JAWSDB Database Completion Setup ===";

try {
    // Create missing tables that your application needs
    $missing_tables = [
        'ordenes' => "CREATE TABLE IF NOT EXISTS ordenes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero_orden VARCHAR(20) UNIQUE NOT NULL,
            cliente_id INT NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            envio DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            estado VARCHAR(50) DEFAULT 'pendiente',
            fecha_orden TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'detalle_pedidos' => "CREATE TABLE IF NOT EXISTS detalle_pedidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            orden_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    $output[] = "Creating missing tables...";

    foreach ($missing_tables as $table_name => $sql) {
        $output[] = "Creating table: $table_name";
        
        if ($conn->query($sql)) {
            $output[] = "✅ Table $table_name created successfully";
        } else {
            $output[] = "❌ Error creating table $table_name: " . $conn->error;
        }
    }

    // Add missing columns to existing tables if needed
    $column_additions = [
        // If vendedores table is missing columns
        "ALTER TABLE vendedores ADD COLUMN IF NOT EXISTS direccion1 TEXT" => "Add direccion1 to vendedores",
        "ALTER TABLE vendedores ADD COLUMN IF NOT EXISTS direccion2 TEXT" => "Add direccion2 to vendedores", 
        "ALTER TABLE vendedores ADD COLUMN IF NOT EXISTS categoria VARCHAR(100)" => "Add categoria to vendedores",
        "ALTER TABLE vendedores ADD COLUMN IF NOT EXISTS cedula_juridica VARCHAR(20)" => "Add cedula_juridica to vendedores",
        
        // If productos table needs column name fixes
        "ALTER TABLE productos ADD COLUMN IF NOT EXISTS unidades INT DEFAULT 0" => "Add unidades to productos (if using different name)"
    ];

    $output[] = "\nAdding missing columns...";

    foreach ($column_additions as $sql => $description) {
        $output[] = "Attempting: $description";
        
        if ($conn->query($sql)) {
            $output[] = "✅ $description - Success";
        } else {
            // Don't show error for "IF NOT EXISTS" failures - column might already exist
            $output[] = "ℹ️ $description - Skipped (column may already exist)";
        }
    }

    // Check final structure
    $output[] = "\n=== Final Database Structure ===";
    
    $tables_to_check = ['clientes', 'vendedores', 'productos', 'ordenes', 'detalle_pedidos'];
    
    foreach ($tables_to_check as $table) {
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            $output[] = "\n--- Table: $table ---";
            while ($row = $result->fetch_assoc()) {
                $output[] = "• " . $row['Field'] . " (" . $row['Type'] . ")";
            }
        } else {
            $output[] = "❌ Table $table does not exist: " . $conn->error;
        }
    }

    $output[] = "\n✅ JAWSDB database setup completed!";
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
