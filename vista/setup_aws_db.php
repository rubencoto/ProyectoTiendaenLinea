<?php
/**
 * Database Setup Script for AWS RDS
 * Creates all necessary tables for the online store
 */

require_once '../modelo/conexion.php';

echo "<h2>🗄️ Database Setup for AWS RDS</h2>";

try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Connected to AWS RDS:</strong> " . $conn->get_server_info() . "<br>";
    echo "🌐 <strong>Host:</strong> kavfu5f7pido12mr.cbetxkdyhwsb.us-east-1.rds.amazonaws.com<br>";
    echo "📊 <strong>Database:</strong> lsj1q7iol6uhg5wu<br>";
    echo "</div>";

    // Create tables
    $tables = [
        'clientes' => "
            CREATE TABLE IF NOT EXISTS clientes (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
        'vendedores' => "
            CREATE TABLE IF NOT EXISTS vendedores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                apellido VARCHAR(100) NOT NULL,
                correo VARCHAR(150) UNIQUE NOT NULL,
                contrasena VARCHAR(255) NOT NULL,
                telefono VARCHAR(20),
                direccion TEXT,
                nombre_tienda VARCHAR(200),
                descripcion_tienda TEXT,
                fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                verificado BOOLEAN DEFAULT FALSE,
                codigo_verificacion VARCHAR(6)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
        'productos' => "
            CREATE TABLE IF NOT EXISTS productos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vendedor_id INT NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                descripcion TEXT,
                precio DECIMAL(10,2) NOT NULL,
                categoria VARCHAR(100),
                stock INT DEFAULT 0,
                imagen_principal LONGTEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                activo BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (vendedor_id) REFERENCES vendedores(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
        'pedidos' => "
            CREATE TABLE IF NOT EXISTS pedidos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
                fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                direccion_envio TEXT NOT NULL,
                telefono_contacto VARCHAR(20),
                FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
        'detalle_pedidos' => "
            CREATE TABLE IF NOT EXISTS detalle_pedidos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                producto_id INT NOT NULL,
                cantidad INT NOT NULL,
                precio_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
                FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
        'codigos_recuperacion' => "
            CREATE TABLE IF NOT EXISTS codigos_recuperacion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                correo VARCHAR(150) NOT NULL,
                codigo VARCHAR(6) NOT NULL,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                usado BOOLEAN DEFAULT FALSE,
                INDEX idx_correo_codigo (correo, codigo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    $success_count = 0;
    $total_tables = count($tables);

    foreach ($tables as $table_name => $sql) {
        try {
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✅ Table '<strong>$table_name</strong>' created/verified successfully</p>";
                $success_count++;
            } else {
                echo "<p style='color: red;'>❌ Error creating table '$table_name': " . $conn->error . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Exception creating table '$table_name': " . $e->getMessage() . "</p>";
        }
    }

    echo "<hr>";
    echo "<h3>📊 Setup Summary:</h3>";
    echo "<div style='background: " . ($success_count === $total_tables ? "#d4edda" : "#fff3cd") . "; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Tables Created:</strong> $success_count / $total_tables</p>";
    
    if ($success_count === $total_tables) {
        echo "<p style='color: green;'><strong>🎉 Database setup completed successfully!</strong></p>";
        echo "<p>Your online store is now connected to AWS RDS with all necessary tables.</p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ Some tables may need attention.</strong></p>";
    }
    echo "</div>";

    // Show current table status
    echo "<h3>📋 Current Tables:</h3>";
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            $table = $row[0];
            $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 'Unknown';
            echo "<li><strong>$table:</strong> $count records</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ Connection Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database credentials and try again.</p>";
    echo "</div>";
}

echo "<br><br>";
echo "<h3>🚀 Next Steps:</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li>Deploy changes to Heroku: <code>git add . && git commit -m \"Switch to AWS RDS database\" && git push heroku main</code></li>";
echo "<li>Test your application: <a href='index.php' target='_blank'>Go to Store</a></li>";
echo "<li>Create test accounts and verify everything works</li>";
echo "<li>The AWS RDS database should handle much higher connection limits</li>";
echo "</ol>";
echo "</div>";

echo "<br><a href='index.php' style='background: #007185; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Store</a>";
echo " ";
echo "<a href='db_monitor.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Monitor Database</a>";
?>
