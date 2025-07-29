<?php
// Script de verificación de tablas para el sistema de órdenes
require_once '../modelo/conexion.php';

echo "<h2>Verificación de Tablas del Sistema de Órdenes</h2>";

// Verificar tabla ordenes
$result = $conn->query("SHOW TABLES LIKE 'ordenes'");
if ($result->num_rows > 0) {
    echo "✅ Tabla 'ordenes' existe<br>";
    
    // Mostrar estructura de la tabla
    $structure = $conn->query("DESCRIBE ordenes");
    echo "<h3>Estructura de la tabla 'ordenes':</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Tabla 'ordenes' NO existe<br>";
    echo "<p>Ejecuta este SQL para crear la tabla:</p>";
    echo "<pre>
CREATE TABLE IF NOT EXISTS ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_orden VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    envio DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    fecha_orden DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE ON UPDATE CASCADE
);
    </pre>";
}

echo "<br>";

// Verificar tabla detalle_pedidos
$result = $conn->query("SHOW TABLES LIKE 'detalle_pedidos'");
if ($result->num_rows > 0) {
    echo "✅ Tabla 'detalle_pedidos' existe<br>";
    
    // Mostrar estructura de la tabla
    $structure = $conn->query("DESCRIBE detalle_pedidos");
    echo "<h3>Estructura de la tabla 'detalle_pedidos':</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Tabla 'detalle_pedidos' NO existe<br>";
    echo "<p>Ejecuta este SQL para crear la tabla:</p>";
    echo "<pre>
CREATE TABLE IF NOT EXISTS detalle_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_orden_id (orden_id),
    INDEX idx_producto_id (producto_id)
);
    </pre>";
}

echo "<br>";

// Verificar tabla clientes
$result = $conn->query("SHOW TABLES LIKE 'clientes'");
if ($result->num_rows > 0) {
    echo "✅ Tabla 'clientes' existe<br>";
} else {
    echo "❌ Tabla 'clientes' NO existe<br>";
}

// Verificar tabla productos
$result = $conn->query("SHOW TABLES LIKE 'productos'");
if ($result->num_rows > 0) {
    echo "✅ Tabla 'productos' existe<br>";
} else {
    echo "❌ Tabla 'productos' NO existe<br>";
}

echo "<br><hr><br>";

// Contar registros en las tablas (si existen)
$tablas_check = ['ordenes', 'detalle_pedidos', 'clientes', 'productos'];
foreach ($tablas_check as $tabla) {
    $result = $conn->query("SHOW TABLES LIKE '$tabla'");
    if ($result->num_rows > 0) {
        $count_result = $conn->query("SELECT COUNT(*) as total FROM $tabla");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['total'];
            echo "📊 Tabla '$tabla': $count registros<br>";
        }
    }
}

$conn->close();
?>
