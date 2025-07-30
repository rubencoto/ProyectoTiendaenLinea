<?php
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

echo "<h2>Image Binary Data Test</h2>";

try {
    // Set charset
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<h3>Testing Image Columns:</h3>";
    
    // Check productos table columns
    $result = $conn->query("SHOW COLUMNS FROM productos WHERE Field LIKE '%imagen%'");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . ($row['Key'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Test Binary Insert:</h3>";
    
    // Create a small test binary data (1x1 pixel PNG)
    $test_png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU8KKQAAAABJRU5ErkJggg==');
    
    echo "<p>Test PNG size: " . strlen($test_png) . " bytes</p>";
    
    // Test insert with binary data using send_long_data
    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo "<p style='color: red;'>Prepare failed: " . $conn->error . "</p>";
    } else {
        $nombre = 'Test Binary Product';
        $descripcion = 'Test binary data handling';
        $precio = 1.00;
        $categoria = 'Test';
        $null_img = null;
        $tallas = 'S';
        $color = 'Red';
        $unidades = 1;
        $garantia = '1 day';
        $dimensiones = '1x1x1';
        $peso = 0.1;
        $tamano_empaque = 'Mini';
        $id_vendedor = 1;
        
        $stmt->bind_param(
            "ssdsbbbsissdsi",
            $nombre,
            $descripcion,
            $precio,
            $categoria,
            $null_img,
            $null_img,
            $null_img,
            $tallas,
            $color,
            $unidades,
            $garantia,
            $dimensiones,
            $peso,
            $tamano_empaque,
            $id_vendedor
        );
        
        // Send binary data
        $stmt->send_long_data(4, $test_png); // imagen_principal
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Binary test insert successful!</p>";
            $product_id = $conn->insert_id;
            echo "<p>Product ID: " . $product_id . "</p>";
            
            // Verify the data was stored correctly
            $check = $conn->prepare("SELECT LENGTH(imagen_principal) as img_size FROM productos WHERE id = ?");
            $check->bind_param("i", $product_id);
            $check->execute();
            $result = $check->get_result();
            if ($row = $result->fetch_assoc()) {
                echo "<p>Stored image size: " . $row['img_size'] . " bytes</p>";
            }
            
            // Clean up
            $conn->query("DELETE FROM productos WHERE nombre = 'Test Binary Product'");
            echo "<p>Test data cleaned up.</p>";
        } else {
            echo "<p style='color: red;'>❌ Binary test insert failed: " . $stmt->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

echo "<p><a href='" . AppConfig::vistaUrl('agregarproducto.php') . "'> ← Back to Add Product</a></p>";
?>
