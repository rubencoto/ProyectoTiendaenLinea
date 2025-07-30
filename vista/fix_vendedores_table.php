<?php
require_once '../modelo/conexion.php';

echo "<h2>Vendedores Table Structure Fix</h2>";

// First, check current structure
echo "<h3>Current Table Structure:</h3>";
$result = $conn->query("DESCRIBE vendedores");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<p><strong>" . $row['Field'] . "</strong>: " . $row['Type'] . " (" . $row['Null'] . ")</p>";
    }
}

echo "<hr>";

// Check if we need to modify the structure
$columns_to_check = ['nombre', 'apellido', 'nombre_empresa'];
$existing_columns = [];

$result = $conn->query("SHOW COLUMNS FROM vendedores");
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
}

echo "<h3>Applying Structure Fixes:</h3>";

// If we have separate nombre/apellido but no nome_empresa, we need to restructure
if (in_array('nombre', $existing_columns) && in_array('apellido', $existing_columns) && !in_array('nombre_empresa', $existing_columns)) {
    
    // Add nombre_empresa column
    $add_column = "ALTER TABLE vendedores ADD COLUMN nombre_empresa VARCHAR(255) NOT NULL AFTER id";
    if ($conn->query($add_column)) {
        echo "<p style='color: green;'>✅ Added nombre_empresa column</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding nombre_empresa: " . $conn->error . "</p>";
    }
    
    // Make nombre and apellido nullable since we're using nombre_empresa now
    $modify_nombre = "ALTER TABLE vendedores MODIFY COLUMN nombre VARCHAR(100) NULL";
    if ($conn->query($modify_nombre)) {
        echo "<p style='color: green;'>✅ Made nombre nullable</p>";
    } else {
        echo "<p style='color: red;'>❌ Error modifying nombre: " . $conn->error . "</p>";
    }
    
    $modify_apellido = "ALTER TABLE vendedores MODIFY COLUMN apellido VARCHAR(100) NULL";
    if ($conn->query($modify_apellido)) {
        echo "<p style='color: green;'>✅ Made apellido nullable</p>";
    } else {
        echo "<p style='color: red;'>❌ Error modifying apellido: " . $conn->error . "</p>";
    }
    
    echo "<p style='color: blue;'>ℹ️ Table structure updated to support business registration</p>";
    
} else if (!in_array('nombre_empresa', $existing_columns)) {
    
    // Add nombre_empresa column if it doesn't exist
    $add_column = "ALTER TABLE vendedores ADD COLUMN nombre_empresa VARCHAR(255) NOT NULL";
    if ($conn->query($add_column)) {
        echo "<p style='color: green;'>✅ Added nombre_empresa column</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding nombre_empresa: " . $conn->error . "</p>";
    }
    
} else {
    echo "<p style='color: blue;'>ℹ️ Table structure already supports business registration</p>";
}

// Show final structure
echo "<hr>";
echo "<h3>Final Table Structure:</h3>";
$result = $conn->query("DESCRIBE vendedores");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $color = ($row['Field'] === 'nombre_empresa') ? 'background-color: lightgreen;' : '';
        echo "<tr style='$color'>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Key'] ?? '') . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='registroVendedor.php'>← Back to Registration</a> | <a href='check_vendedores_table.php'>Check Structure</a></p>";
?>
