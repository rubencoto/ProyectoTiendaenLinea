<?php
require_once '../modelo/conexion.php';

$output = [];
$output[] = "=== Fixing Database Column References ===";

try {
    // First, check what columns exist in each table
    $tables_to_check = ['clientes', 'vendedores', 'productos', 'ordenes', 'detalle_pedidos'];
    $table_structures = [];
    
    foreach ($tables_to_check as $table) {
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $table_structures[$table] = $columns;
            $output[] = "Table $table columns: " . implode(', ', $columns);
        } else {
            $output[] = "❌ Table $table does not exist or cannot be accessed";
        }
    }
    
    // Define files that need column fixes
    $files_to_fix = [
        // Files that reference apellidos but should use apellido  
        'vista/misPedidos.php' => [
            'SELECT nombre, apellidos FROM clientes' => 'SELECT nombre, apellido FROM clientes'
        ],
        'vista/cambiarPassword.php' => [
            'SELECT nombre, apellidos, correo FROM clientes' => 'SELECT nombre, apellido, correo FROM clientes'
        ],
        'vista/perfil.php' => [
            'SELECT nombre, apellidos, correo, telefono, cedula, direccion' => 'SELECT nombre, apellido, correo, telefono, direccion',
            'nombre = ?, apellidos = ?, telefono = ?, cedula = ?,' => 'nombre = ?, apellido = ?, telefono = ?,'
        ]
    ];
    
    $output[] = "\nColumn reference fixes needed:";
    
    // Check if clientes table has apellido or apellidos
    if (isset($table_structures['clientes'])) {
        $clientes_columns = $table_structures['clientes'];
        if (in_array('apellido', $clientes_columns)) {
            $output[] = "✅ Clientes table uses 'apellido' (singular)";
        } elseif (in_array('apellidos', $clientes_columns)) {
            $output[] = "✅ Clientes table uses 'apellidos' (plural)";
        } else {
            $output[] = "❌ Neither 'apellido' nor 'apellidos' found in clientes table";
        }
    }
    
    // Check productos table structure
    if (isset($table_structures['productos'])) {
        $productos_columns = $table_structures['productos'];
        $expected_productos_columns = ['id', 'id_vendedor', 'nombre', 'descripcion', 'precio', 'unidades', 'categoria', 'imagen', 'fecha_creacion', 'activo'];
        $missing_columns = array_diff($expected_productos_columns, $productos_columns);
        
        if (empty($missing_columns)) {
            $output[] = "✅ Productos table has all expected columns";
        } else {
            $output[] = "❌ Productos table missing columns: " . implode(', ', $missing_columns);
        }
    }
    
    // Check vendedores table structure
    if (isset($table_structures['vendedores'])) {
        $vendedores_columns = $table_structures['vendedores'];
        $expected_vendedores_columns = ['id', 'nombre_empresa', 'correo', 'contrasena', 'telefono', 'direccion1', 'direccion2', 'categoria', 'cedula_juridica', 'verificado', 'codigo_verificacion', 'fecha_registro'];
        $missing_columns = array_diff($expected_vendedores_columns, $vendedores_columns);
        
        if (empty($missing_columns)) {
            $output[] = "✅ Vendedores table has all expected columns";
        } else {
            $output[] = "❌ Vendedores table missing columns: " . implode(', ', $missing_columns);
        }
    }
    
    $output[] = "\n✅ Database structure analysis completed!";
    $output[] = "\nRecommendations:";
    $output[] = "1. Run the JAWSDB setup script to create proper table structures";
    $output[] = "2. Update all PHP files to use the correct column names";
    $output[] = "3. Test all functionality after the fixes";
    
    $status = 'completed';

} catch (Exception $e) {
    $output[] = "❌ Error: " . $e->getMessage();
    $status = 'error';
}

header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'output' => $output,
    'table_structures' => isset($table_structures) ? $table_structures : []
], JSON_UNESCAPED_UNICODE);
?>
