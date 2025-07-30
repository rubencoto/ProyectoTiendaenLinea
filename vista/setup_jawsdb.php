<?php
require_once '../modelo/conexion.php';

$output = [];
$output[] = "=== Database Structure Fix for JAWSDB ===";

try {
    // First, let's see what we have
    $result = $conn->query("SHOW TABLES");
    $existing_tables = [];
    if ($result) {
        while ($row = $result->fetch_array()) {
            $existing_tables[] = $row[0];
        }
    }
    $output[] = "Existing tables: " . implode(', ', $existing_tables);

    // Define the complete schema needed by the application
    $schema = [
        'clientes' => "CREATE TABLE IF NOT EXISTS clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            correo VARCHAR(150) UNIQUE NOT NULL,
            contrasena VARCHAR(255) NOT NULL,
            telefono VARCHAR(20),
            direccion TEXT,
            verificado BOOLEAN DEFAULT FALSE,
            codigo_verificacion VARCHAR(6),
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reset_token VARCHAR(100),
            token_expira DATETIME,
            codigo_expira DATETIME
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'vendedores' => "CREATE TABLE IF NOT EXISTS vendedores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre_empresa VARCHAR(150) NOT NULL,
            correo VARCHAR(150) UNIQUE NOT NULL,
            contrasena VARCHAR(255) NOT NULL,
            telefono VARCHAR(20),
            direccion1 TEXT,
            direccion2 TEXT,
            categoria VARCHAR(100),
            cedula_juridica VARCHAR(20),
            verificado BOOLEAN DEFAULT FALSE,
            codigo_verificacion VARCHAR(6),
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'productos' => "CREATE TABLE IF NOT EXISTS productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_vendedor INT NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10,2) NOT NULL,
            unidades INT NOT NULL DEFAULT 0,
            categoria VARCHAR(100),
            imagen VARCHAR(255),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (id_vendedor) REFERENCES vendedores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

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

    $output[] = "\nCreating/updating tables...";

    foreach ($schema as $table_name => $sql) {
        $output[] = "Processing table: $table_name";
        
        if ($conn->query($sql)) {
            $output[] = "✅ Table $table_name created/updated successfully";
        } else {
            $output[] = "❌ Error with table $table_name: " . $conn->error;
        }
    }

    // Check final structure
    $output[] = "\nFinal database structure:";
    foreach (array_keys($schema) as $table) {
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            $output[] = "\n=== Table: $table ===";
            while ($row = $result->fetch_assoc()) {
                $output[] = "- " . $row['Field'] . " (" . $row['Type'] . ")";
            }
        }
    }

    $output[] = "\n✅ Database structure setup completed!";
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
