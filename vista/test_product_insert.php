<?php
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

echo "<h2>Product Insert Test</h2>";

// Simple test with ASCII characters only
$test_data = [
    'nombre' => 'Test Product ASCII',
    'descripcion' => 'Simple test description without special characters',
    'precio' => 10.99,
    'categoria' => 'Test Category',
    'tallas' => 'M',
    'color' => 'Blue',
    'unidades' => 1,
    'garantia' => '1 year',
    'dimensiones' => '10x10x10',
    'peso' => 1.5,
    'tamano_empaque' => 'Small',
    'id_vendedor' => 1 // Test vendor ID
];

try {
    // Set charset explicitly
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<h3>Database Charset Status:</h3>";
    $result = $conn->query("SELECT @@character_set_connection, @@collation_connection");
    if ($row = $result->fetch_row()) {
        echo "<p>Connection Charset: " . $row[0] . "</p>";
        echo "<p>Connection Collation: " . $row[1] . "</p>";
    }
    
    echo "<h3>Test Insert:</h3>";
    
    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
        VALUES (?, ?, ?, ?, NULL, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo "<p style='color: red;'>Prepare failed: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param(
            "ssdssssssdsi",
            $test_data['nombre'],
            $test_data['descripcion'],
            $test_data['precio'],
            $test_data['categoria'],
            $test_data['tallas'],
            $test_data['color'],
            $test_data['unidades'],
            $test_data['garantia'],
            $test_data['dimensiones'],
            $test_data['peso'],
            $test_data['tamano_empaque'],
            $test_data['id_vendedor']
        );
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Test product inserted successfully!</p>";
            echo "<p>Product ID: " . $conn->insert_id . "</p>";
            
            // Clean up test data
            $conn->query("DELETE FROM productos WHERE nombre = 'Test Product ASCII'");
            echo "<p>Test data cleaned up.</p>";
        } else {
            echo "<p style='color: red;'>❌ Insert failed: " . $stmt->error . "</p>";
            echo "<p>Error number: " . $stmt->errno . "</p>";
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

echo "<p><a href='" . AppConfig::vistaUrl('agregarproducto.php') . "'> ← Back to Add Product</a></p>";
?>
