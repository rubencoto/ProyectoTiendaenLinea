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
    // First, check what columns exist in vendedores table
    $vendedores_result = $conn->query("DESCRIBE vendedores");
    $vendedores_columns = [];
    if ($vendedores_result) {
        while ($row = $vendedores_result->fetch_assoc()) {
            $vendedores_columns[] = $row['Field'];
        }
    }

    $output[] = "Current vendedores columns: " . implode(', ', $vendedores_columns);

    // Define columns to add if they don't exist
    $columns_to_add = [
        'direccion1' => "ALTER TABLE vendedores ADD COLUMN direccion1 TEXT",
        'direccion2' => "ALTER TABLE vendedores ADD COLUMN direccion2 TEXT", 
        'categoria' => "ALTER TABLE vendedores ADD COLUMN categoria VARCHAR(100)",
        'cedula_juridica' => "ALTER TABLE vendedores ADD COLUMN cedula_juridica VARCHAR(20)"
    ];

    $output[] = "\nAdding missing columns to vendedores...";

    foreach ($columns_to_add as $column_name => $sql) {
        if (!in_array($column_name, $vendedores_columns)) {
            $output[] = "Adding column: $column_name";
            
            if ($conn->query($sql)) {
                $output[] = "✅ Added column: $column_name";
            } else {
                $output[] = "❌ Error adding $column_name: " . $conn->error;
            }
        } else {
            $output[] = "ℹ️ Column $column_name already exists";
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
