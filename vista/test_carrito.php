<?php
session_start();
require_once '../modelo/conexion.php';
require_once '../modelo/carritoPersistente.php';

if (empty($_SESSION['cliente_id'])) {
    echo "No hay sesión de cliente activa.";
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
echo "<h2>Test de Carrito Persistente</h2>";
echo "<p>Cliente ID: $cliente_id</p>";

$carritoPersistente = new CarritoPersistente();

// Verificar conexión a la base de datos
$conn = DatabaseConnection::getInstance()->getConnection();
if ($conn) {
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
} else {
    echo "<p style='color: red;'>❌ Error de conexión a base de datos</p>";
}

// Verificar si la tabla existe
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM carrito_persistente WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $result = $stmt->fetch();
    echo "<p>📊 Items en carrito_persistente para cliente $cliente_id: " . $result['count'] . "</p>";
    
    // Mostrar todos los items
    $stmt = $conn->prepare("SELECT * FROM carrito_persistente WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Items en la tabla:</h3>";
    if (empty($items)) {
        echo "<p>No hay items en el carrito para este cliente.</p>";
    } else {
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
    
    // Test del método obtenerCarrito
    echo "<h3>Test del método obtenerCarrito():</h3>";
    $productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
    echo "<p>Productos obtenidos: " . count($productos_carrito) . "</p>";
    
    if (!empty($productos_carrito)) {
        echo "<table border='1'>";
        echo "<tr><th>Producto ID</th><th>Nombre</th><th>Precio</th><th>Cantidad</th><th>Vendedor</th></tr>";
        foreach ($productos_carrito as $producto) {
            echo "<tr>";
            echo "<td>" . $producto['producto_id'] . "</td>";
            echo "<td>" . $producto['nombre'] . "</td>";
            echo "<td>" . $producto['precio'] . "</td>";
            echo "<td>" . $producto['cantidad'] . "</td>";
            echo "<td>" . $producto['vendedor_nombre'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al consultar la tabla: " . $e->getMessage() . "</p>";
}

echo "<br><a href='carrito.php'>Ir al carrito</a> | <a href='index.php'>Ir al catálogo</a>";
?>
