<?php
echo "<h2>Parameter Count Verification</h2>";

// Define the SQL query
$sql = "INSERT INTO productos 
    (nombre, descripcion, precio, categoria, imagen_principal, imagen_secundaria1, imagen_secundaria2, tallas, color, unidades, garantia, dimensiones, peso, tamano_empaque, id_vendedor) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Count the question marks
$placeholder_count = substr_count($sql, '?');
echo "<p><strong>Number of placeholders (?): </strong>" . $placeholder_count . "</p>";

// Define the type string
$type_string = "ssdsbbssissdsi";
echo "<p><strong>Type string: </strong>" . $type_string . "</p>";
echo "<p><strong>Type string length: </strong>" . strlen($type_string) . "</p>";

// Break down the types
$types = [
    1 => 'nombre (s)',
    2 => 'descripcion (s)', 
    3 => 'precio (d)',
    4 => 'categoria (s)',
    5 => 'imagen_principal (b)',
    6 => 'imagen_secundaria1 (b)',
    7 => 'imagen_secundaria2 (b)',
    8 => 'tallas (s)',
    9 => 'color (s)',
    10 => 'unidades (i)',
    11 => 'garantia (s)',
    12 => 'dimensiones (s)',
    13 => 'peso (d)',
    14 => 'tamano_empaque (s)',
    15 => 'id_vendedor (i)'
];

echo "<h3>Parameter Breakdown:</h3>";
echo "<ol>";
foreach ($types as $num => $desc) {
    $type_char = $type_string[$num-1] ?? 'MISSING';
    $color = ($type_char === 'MISSING') ? 'red' : 'green';
    echo "<li style='color: $color'>$desc - Type: <strong>$type_char</strong></li>";
}
echo "</ol>";

if ($placeholder_count === strlen($type_string)) {
    echo "<p style='color: green; font-weight: bold;'>✅ Parameter count matches!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Parameter count mismatch!</p>";
    echo "<p>Placeholders: $placeholder_count, Type string length: " . strlen($type_string) . "</p>";
}

echo "<p><a href='agregarproducto.php'>← Back to Add Product</a></p>";
?>
