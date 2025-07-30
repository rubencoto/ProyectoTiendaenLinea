<?php
require_once '../modelo/conexion.php';

echo "<h2>Current Vendedores Table Structure</h2>";

$check_table = "DESCRIBE vendedores";
$result = $conn->query($check_table);

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Key'] ?? '') . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Available Columns:</h3>";
    echo "<p>" . implode(', ', $columns) . "</p>";
    
    // Check for required (NOT NULL) columns without defaults
    echo "<h3>Required Columns Analysis:</h3>";
    $result->data_seek(0); // Reset result pointer
    while ($row = $result->fetch_assoc()) {
        if ($row['Null'] === 'NO' && ($row['Default'] === null || $row['Default'] === '')) {
            if ($row['Extra'] !== 'auto_increment') {
                echo "<p style='color: red;'>⚠️ <strong>" . $row['Field'] . "</strong> is required (NOT NULL) and has no default value</p>";
            }
        }
    }
    
} else {
    echo "<p style='color: red;'>❌ Error checking table: " . $conn->error . "</p>";
}

echo "<h3>Current Registration Form Fields:</h3>";
echo "<p>The registration form sends these fields:</p>";
echo "<ul>";
echo "<li><strong>nombre</strong> (company name)</li>";
echo "<li><strong>correo</strong></li>";
echo "<li><strong>contrasena</strong></li>";
echo "<li><strong>telefono</strong></li>";
echo "<li><strong>direccion1</strong></li>";
echo "<li><strong>direccion2</strong></li>";
echo "<li><strong>categoria</strong></li>";
echo "<li><strong>cedula_juridica</strong></li>";
echo "<li><strong>biografia</strong></li>";
echo "<li><strong>redes</strong></li>";
echo "</ul>";

echo "<p><a href='registroVendedor.php'>← Back to Registration</a></p>";
?>
