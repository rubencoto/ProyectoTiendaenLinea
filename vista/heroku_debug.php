<?php
require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

echo "<h2>Heroku Parameter Debug - Live Server Check</h2>";

// Check if we can access the file
$file_path = '/app/controlador/procesarProducto.php';
echo "<h3>File Status:</h3>";
echo "<p><strong>File exists:</strong> " . (file_exists($file_path) ? "✅ Yes" : "❌ No") . "</p>";

if (file_exists($file_path)) {
    $file_size = filesize($file_path);
    echo "<p><strong>File size:</strong> $file_size bytes</p>";
    echo "<p><strong>Last modified:</strong> " . date('Y-m-d H:i:s', filemtime($file_path)) . "</p>";
}

// Let's examine the exact bind_param line
echo "<h3>Current bind_param Configuration:</h3>";
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    
    // Find the bind_param call
    $pattern = '/bind_param\s*\(\s*"([^"]+)"/';
    if (preg_match($pattern, $content, $matches)) {
        $type_string = $matches[1];
        echo "<p><strong>Found type string:</strong> '<span style='color: blue; font-family: monospace;'>$type_string</span>'</p>";
        echo "<p><strong>Length:</strong> " . strlen($type_string) . "</p>";
        
        // Count each character
        echo "<p><strong>Character breakdown:</strong></p>";
        echo "<div style='font-family: monospace; background: #f0f0f0; padding: 10px;'>";
        for ($i = 0; $i < strlen($type_string); $i++) {
            $char = $type_string[$i];
            $pos = $i + 1;
            echo "[$pos] $char ";
            if ($pos % 10 == 0) echo "<br>";
        }
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Could not find bind_param call in file</p>";
    }
    
    // Count the number of variables being passed
    $var_pattern = '/bind_param\s*\(\s*"[^"]+",\s*((?:\$[^,\)]+(?:\s*,\s*)?)+)/s';
    if (preg_match($var_pattern, $content, $matches)) {
        $vars_section = $matches[1];
        // Count variables by counting $ signs
        $var_count = substr_count($vars_section, '$');
        echo "<p><strong>Number of variables found:</strong> $var_count</p>";
    }
} else {
    echo "<p style='color: red;'>Cannot access procesarProducto.php file</p>";
}

// Test a simple product insert to isolate the issue
echo "<h3>Simple Insert Test:</h3>";

try {
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Simple test with just required fields
    $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria, id_vendedor) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo "<p style='color: red;'>❌ Prepare failed: " . $conn->error . "</p>";
    } else {
        $test_nombre = 'Test Product Simple';
        $test_desc = 'Simple test';
        $test_precio = 10.99;
        $test_categoria = 'Test';
        $test_vendedor = 1;
        
        $stmt->bind_param("ssdsi", $test_nombre, $test_desc, $test_precio, $test_categoria, $test_vendedor);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Simple insert successful!</p>";
            $product_id = $conn->insert_id;
            echo "<p>Product ID: $product_id</p>";
            
            // Clean up
            $conn->query("DELETE FROM productos WHERE id = $product_id");
            echo "<p>Test data cleaned up.</p>";
        } else {
            echo "<p style='color: red;'>❌ Simple insert failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception during test: " . $e->getMessage() . "</p>";
}

echo "<h3>Recommendation:</h3>";
echo "<p>If the simple insert works but the full form doesn't, the issue is specifically with the parameter count mismatch in the full insert statement.</p>";

echo "<p><a href='" . AppConfig::vistaUrl('agregarproducto.php') . "'> ← Back to Add Product</a></p>";
?>
