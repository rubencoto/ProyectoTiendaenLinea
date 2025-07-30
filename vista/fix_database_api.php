<?php
// Start output buffering to catch any unexpected output
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, we'll capture them

try {
    require_once '../modelo/conexion.php';
    
    $output = [];
    $output[] = "=== Database Structure Fix ===";

    // First, check current structure
    $result = $conn->query('DESCRIBE clientes');
    $existing_columns = [];
    
    if ($result) {
        $output[] = "Current clientes table structure:";
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
            $output[] = "- " . $row['Field'] . " (" . $row['Type'] . ")";
        }
    } else {
        throw new Exception("Could not describe clientes table: " . $conn->error);
    }
    
    // Define required columns that might be missing
    $required_columns = [
        'apellidos' => "ALTER TABLE clientes ADD COLUMN apellidos VARCHAR(100) AFTER nombre",
        'cedula' => "ALTER TABLE clientes ADD COLUMN cedula VARCHAR(20) UNIQUE",
        'provincia' => "ALTER TABLE clientes ADD COLUMN provincia VARCHAR(50)",
        'fecha_nacimiento' => "ALTER TABLE clientes ADD COLUMN fecha_nacimiento DATE",
        'genero' => "ALTER TABLE clientes ADD COLUMN genero CHAR(1)",
        'newsletter' => "ALTER TABLE clientes ADD COLUMN newsletter BOOLEAN DEFAULT FALSE",
        'reset_token' => "ALTER TABLE clientes ADD COLUMN reset_token VARCHAR(100)",
        'token_expira' => "ALTER TABLE clientes ADD COLUMN token_expira DATETIME",
        'codigo_expira' => "ALTER TABLE clientes ADD COLUMN codigo_expira DATETIME"
    ];
    
    $output[] = "\nAdding missing columns:";
    
    foreach ($required_columns as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $output[] = "Adding column: $column";
            if ($conn->query($sql)) {
                $output[] = "✅ Added column: $column";
            } else {
                $output[] = "❌ Error adding $column: " . $conn->error;
            }
        } else {
            $output[] = "Column $column already exists";
        }
    }
    
    // Fix apellido -> apellidos if needed
    if (in_array('apellido', $existing_columns) && !in_array('apellidos', $existing_columns)) {
        $output[] = "Renaming 'apellido' to 'apellidos'";
        if ($conn->query("ALTER TABLE clientes CHANGE apellido apellidos VARCHAR(100) NOT NULL")) {
            $output[] = "✅ Renamed apellido to apellidos";
        } else {
            $output[] = "❌ Error renaming column: " . $conn->error;
        }
    }
    
    $output[] = "\nFinal table structure:";
    $result = $conn->query('DESCRIBE clientes');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $output[] = "- " . $row['Field'] . " (" . $row['Type'] . ")";
        }
    }
    
    $output[] = "\n✅ Database structure fix completed!";
    $status = 'completed';
    
} catch (Exception $e) {
    $output[] = "❌ Error: " . $e->getMessage();
    $status = 'error';
} catch (Error $e) {
    $output[] = "❌ Fatal Error: " . $e->getMessage();
    $status = 'error';
}

// Clear any unwanted output
ob_clean();

// Output as JSON for easy parsing
header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'output' => $output
], JSON_UNESCAPED_UNICODE);
?>
