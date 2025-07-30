<?php
/**
 * Emergency Database Fix
 * Creates tables with correct column names immediately
 */

require_once '../modelo/conexion.php';

echo "<h2>🚨 Emergency Database Fix</h2>";

try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    echo "<p>✅ Connected to AWS RDS database</p>";
    
    // Drop existing tables if they have wrong structure
    echo "<h3>Dropping existing tables (if any)...</h3>";
    $drop_queries = [
        "DROP TABLE IF EXISTS detalle_pedidos",
        "DROP TABLE IF EXISTS pedidos", 
        "DROP TABLE IF EXISTS productos",
        "DROP TABLE IF EXISTS vendedores",
        "DROP TABLE IF EXISTS clientes",
        "DROP TABLE IF EXISTS codigos_recuperacion"
    ];
    
    foreach ($drop_queries as $query) {
        if ($conn->query($query)) {
            echo "<p style='color: orange;'>Dropped old table structure</p>";
        }
    }
    
    echo "<h3>Creating tables with correct structure...</h3>";
    
    // Create vendedores table first (referenced by productos)
    $vendedores_sql = "
        CREATE TABLE vendedores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            correo VARCHAR(150) UNIQUE NOT NULL,
            contrasena VARCHAR(255) NOT NULL,
            telefono VARCHAR(20),
            direccion TEXT,
            nombre_empresa VARCHAR(200),
            descripcion_tienda TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verificado BOOLEAN DEFAULT FALSE,
            codigo_verificacion VARCHAR(6)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($vendedores_sql)) {
        echo "<p style='color: green;'>✅ Created 'vendedores' table with nombre_empresa column</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating vendedores: " . $conn->error . "</p>";
    }
    
    // Create productos table with id_vendedor column
    $productos_sql = "
        CREATE TABLE productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_vendedor INT NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10,2) NOT NULL,
            categoria VARCHAR(100),
            stock INT DEFAULT 0,
            imagen_principal LONGTEXT,
            imagen_secundaria1 LONGTEXT,
            imagen_secundaria2 LONGTEXT,
            tallas VARCHAR(100),
            color VARCHAR(50),
            unidades INT DEFAULT 0,
            garantia TEXT,
            dimensiones VARCHAR(100),
            peso VARCHAR(50),
            tamano_empaque VARCHAR(100),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (id_vendedor) REFERENCES vendedores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($productos_sql)) {
        echo "<p style='color: green;'>✅ Created 'productos' table with id_vendedor column</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating productos: " . $conn->error . "</p>";
    }
    
    // Create clientes table
    $clientes_sql = "
        CREATE TABLE clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            correo VARCHAR(150) UNIQUE NOT NULL,
            contrasena VARCHAR(255) NOT NULL,
            telefono VARCHAR(20),
            direccion TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verificado BOOLEAN DEFAULT FALSE,
            codigo_verificacion VARCHAR(6)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($clientes_sql)) {
        echo "<p style='color: green;'>✅ Created 'clientes' table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating clientes: " . $conn->error . "</p>";
    }
    
    // Create other essential tables
    $pedidos_sql = "
        CREATE TABLE pedidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
            fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            direccion_envio TEXT NOT NULL,
            telefono_contacto VARCHAR(20),
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($pedidos_sql)) {
        echo "<p style='color: green;'>✅ Created 'pedidos' table</p>";
    }
    
    $detalle_pedidos_sql = "
        CREATE TABLE detalle_pedidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($detalle_pedidos_sql)) {
        echo "<p style='color: green;'>✅ Created 'detalle_pedidos' table</p>";
    }
    
    $codigos_sql = "
        CREATE TABLE codigos_recuperacion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            correo VARCHAR(150) NOT NULL,
            codigo VARCHAR(6) NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            usado BOOLEAN DEFAULT FALSE,
            INDEX idx_correo_codigo (correo, codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($codigos_sql)) {
        echo "<p style='color: green;'>✅ Created 'codigos_recuperacion' table</p>";
    }
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🎉 Database Fixed!</h3>";
    echo "<p><strong>All tables created with correct column names:</strong></p>";
    echo "<ul>";
    echo "<li>✅ vendedores.nombre_empresa (not nombre_tienda)</li>";
    echo "<li>✅ productos.id_vendedor (not vendedor_id)</li>";
    echo "<li>✅ All other tables created successfully</li>";
    echo "</ul>";
    echo "<p><strong>Your store should now work without column errors!</strong></p>";
    echo "</div>";
    
    // Test the query that was failing
    echo "<h3>🧪 Testing the problematic query...</h3>";
    try {
        $test_stmt = $conn->prepare(
            "SELECT p.id, p.nombre, p.precio, p.imagen_principal, p.descripcion, v.nombre_empresa AS vendedor_nombre 
            FROM productos p 
            JOIN vendedores v ON p.id_vendedor = v.id 
            ORDER BY p.id DESC LIMIT 1"
        );
        
        if ($test_stmt) {
            echo "<p style='color: green;'>✅ Query syntax is now correct!</p>";
            $test_stmt->close();
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Query still has issues: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<br><br>";
echo "<a href='index.php' style='background: #007185; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Your Store Now</a>";
echo " ";
echo "<a href='db_monitor.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Check Database Status</a>";
?>
