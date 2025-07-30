<?php
require_once '../modelo/conexion.php';

echo "<h2>Verificar columna 'direccion' en tabla clientes</h2>";

// Check if direccion column exists
$check_column = "SHOW COLUMNS FROM clientes LIKE 'direccion'";
$result = $conn->query($check_column);

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ La columna 'direccion' existe en la tabla clientes</p>";
    
    // Show column details
    $column_info = $result->fetch_assoc();
    echo "<p><strong>Detalles de la columna:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Nombre:</strong> " . $column_info['Field'] . "</li>";
    echo "<li><strong>Tipo:</strong> " . $column_info['Type'] . "</li>";
    echo "<li><strong>Permite NULL:</strong> " . $column_info['Null'] . "</li>";
    echo "<li><strong>Por defecto:</strong> " . ($column_info['Default'] ?? 'NULL') . "</li>";
    echo "</ul>";
    
    // Check some sample data
    echo "<h3>Muestra de datos de clientes (primeros 5 registros):</h3>";
    $sample_query = "SELECT id, nombre, apellido, correo, direccion FROM clientes LIMIT 5";
    $sample_result = $conn->query($sample_query);
    
    if ($sample_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Correo</th><th>Dirección</th></tr>";
        
        while ($row = $sample_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['nombre'] . "</td>";
            echo "<td>" . $row['apellido'] . "</td>";
            echo "<td>" . $row['correo'] . "</td>";
            echo "<td>" . ($row['direccion'] ?? '<em>NULL</em>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count how many records have NULL direccion
        $null_count_query = "SELECT COUNT(*) as count FROM clientes WHERE direccion IS NULL";
        $null_result = $conn->query($null_count_query);
        $null_count = $null_result->fetch_assoc()['count'];
        
        $total_count_query = "SELECT COUNT(*) as count FROM clientes";
        $total_result = $conn->query($total_count_query);
        $total_count = $total_result->fetch_assoc()['count'];
        
        echo "<p><strong>Estadísticas:</strong></p>";
        echo "<ul>";
        echo "<li>Total de clientes: $total_count</li>";
        echo "<li>Clientes con dirección NULL: $null_count</li>";
        echo "<li>Clientes con dirección: " . ($total_count - $null_count) . "</li>";
        echo "</ul>";
        
    } else {
        echo "<p>No hay datos de clientes en la tabla.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ La columna 'direccion' NO existe en la tabla clientes</p>";
    echo "<p>Intentando crear la columna...</p>";
    
    $add_column = "ALTER TABLE clientes ADD COLUMN direccion TEXT";
    if ($conn->query($add_column)) {
        echo "<p style='color: green;'>✅ Columna 'direccion' creada exitosamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al crear la columna: " . $conn->error . "</p>";
    }
}

// Show the complete table structure
echo "<h3>Estructura completa de la tabla clientes:</h3>";
$structure_query = "DESCRIBE clientes";
$structure_result = $conn->query($structure_query);

if ($structure_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>NULL</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>";
    
    while ($row = $structure_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Key'] ?? '') . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='perfil.php'>← Volver al perfil</a></p>";
?>
