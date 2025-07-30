<?php
require_once 'modelo/conexion.php';

echo "<h2>Database Structure Fix</h2>";

try {
    // First, check current structure
    $result = $conn->query('DESCRIBE clientes');
    $existing_columns = [];
    
    if ($result) {
        echo "<h3>Current clientes table structure:</h3><ul>";
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
            echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
        }
        echo "</ul>";
    }
    
    // Define required columns that might be missing
    $required_columns = [
        'apellidos' => "ALTER TABLE clientes ADD COLUMN apellidos VARCHAR(100) AFTER apellido",
        'cedula' => "ALTER TABLE clientes ADD COLUMN cedula VARCHAR(20) UNIQUE",
        'provincia' => "ALTER TABLE clientes ADD COLUMN provincia VARCHAR(50)",
        'fecha_nacimiento' => "ALTER TABLE clientes ADD COLUMN fecha_nacimiento DATE",
        'genero' => "ALTER TABLE clientes ADD COLUMN genero CHAR(1)",
        'newsletter' => "ALTER TABLE clientes ADD COLUMN newsletter BOOLEAN DEFAULT FALSE",
        'reset_token' => "ALTER TABLE clientes ADD COLUMN reset_token VARCHAR(100)",
        'token_expira' => "ALTER TABLE clientes ADD COLUMN token_expira DATETIME",
        'codigo_expira' => "ALTER TABLE clientes ADD COLUMN codigo_expira DATETIME"
    ];
    
    echo "<h3>Adding missing columns:</h3>";
    
    foreach ($required_columns as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            echo "<p>Adding column: $column</p>";
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✅ Added column: $column</p>";
            } else {
                echo "<p style='color: red;'>❌ Error adding $column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>Column $column already exists</p>";
        }
    }
    
    // Fix apellido -> apellidos if needed
    if (in_array('apellido', $existing_columns) && !in_array('apellidos', $existing_columns)) {
        echo "<p>Renaming 'apellido' to 'apellidos'</p>";
        if ($conn->query("ALTER TABLE clientes CHANGE apellido apellidos VARCHAR(100) NOT NULL")) {
            echo "<p style='color: green;'>✅ Renamed apellido to apellidos</p>";
        } else {
            echo "<p style='color: red;'>❌ Error renaming column: " . $conn->error . "</p>";
        }
    }
    
    echo "<h3>Final table structure:</h3>";
    $result = $conn->query('DESCRIBE clientes');
    if ($result) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
        }
        echo "</ul>";
    }
    
    echo "<p style='color: green; font-weight: bold;'>Database structure fix completed!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
