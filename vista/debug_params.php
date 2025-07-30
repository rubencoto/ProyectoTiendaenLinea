<?php
echo "<h2>Debug Parameter Count Issue</h2>";

// Exact type string from the code
$type_string = "ssdsbbssissdsi";
echo "<p><strong>Current type string:</strong> '$type_string'</p>";
echo "<p><strong>Length:</strong> " . strlen($type_string) . "</p>";

// Required parameters based on SQL
$required_params = [
    'nombre' => 's',
    'descripcion' => 's', 
    'precio' => 'd',
    'categoria' => 's',
    'imagen_principal' => 'b',
    'imagen_secundaria1' => 'b',
    'imagen_secundaria2' => 'b',
    'tallas' => 's',
    'color' => 's',
    'unidades' => 'i',
    'garantia' => 's',
    'dimensiones' => 's',
    'peso' => 'd',
    'tamano_empaque' => 's',
    'id_vendedor' => 'i'
];

echo "<p><strong>Required parameters:</strong> " . count($required_params) . "</p>";

$correct_type_string = implode('', array_values($required_params));
echo "<p><strong>Correct type string should be:</strong> '$correct_type_string'</p>";
echo "<p><strong>Correct length:</strong> " . strlen($correct_type_string) . "</p>";

echo "<h3>Character by character comparison:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Position</th><th>Parameter</th><th>Required</th><th>Current</th><th>Match</th></tr>";

$i = 0;
foreach ($required_params as $param => $type) {
    $current_type = isset($type_string[$i]) ? $type_string[$i] : 'MISSING';
    $match = ($current_type === $type) ? '✅' : '❌';
    $color = ($current_type === $type) ? 'lightgreen' : 'lightcoral';
    
    echo "<tr style='background-color: $color'>";
    echo "<td>" . ($i + 1) . "</td>";
    echo "<td>$param</td>";
    echo "<td>$type</td>";
    echo "<td>$current_type</td>";
    echo "<td>$match</td>";
    echo "</tr>";
    $i++;
}
echo "</table>";

if ($type_string !== $correct_type_string) {
    echo "<p style='color: red; font-weight: bold;'>❌ Type string needs correction!</p>";
    echo "<p><strong>Fix:</strong> Change '$type_string' to '$correct_type_string'</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ Type string is correct!</p>";
}
?>
