<?php
/**
 * Quick Database Table Creation
 * Creates the essential tables to fix the immediate error
 */

require_once '../modelo/conexion.php';

echo "<h2>🔧 Quick Database Fix</h2>";

try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    echo "<p>✅ Connected to AWS RDS database</p>";
    
    // Create productos table first (causing the immediate error)
    $productos_sql = "
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
            activo BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($productos_sql)) {
        echo "<p style='color: green;'>✅ Created 'productos' table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating productos table: " . $conn->error . "</p>";
    }
    
    // Create other essential tables
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($tables as $table_name => $sql) {
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Created '$table_name' table</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating $table_name: " . $conn->error . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🎉 Quick Fix Applied!</h3>";
    echo "<p>The immediate error should now be resolved. Your store should load without the 'productos doesn't exist' error.</p>";
    echo "<p><strong>Next:</strong> Run the full setup script to create all remaining tables.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<br>";
echo "<a href='index.php' style='background: #007185; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Store Now</a>";
echo " ";
echo "<a href='setup_aws_db.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Full Database Setup</a>";
?>
