<?php
// Debug version - let's see what's happening
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting debug...\n";

try {
    echo "1. Requiring connection file...\n";
    require_once '../modelo/conexion.php';
    echo "2. Connection file loaded\n";
    
    if (isset($conn)) {
        echo "3. Connection variable exists\n";
        
        $result = $conn->query('SELECT 1 as test');
        if ($result) {
            echo "4. Basic query works\n";
            
            $result = $conn->query('SHOW TABLES LIKE "clientes"');
            if ($result && $result->num_rows > 0) {
                echo "5. Clientes table exists\n";
                
                $result = $conn->query('DESCRIBE clientes');
                if ($result) {
                    echo "6. Can describe table\n";
                    $columns = [];
                    while ($row = $result->fetch_assoc()) {
                        $columns[] = $row['Field'];
                    }
                    echo "7. Columns found: " . implode(', ', $columns) . "\n";
                } else {
                    echo "6. ERROR: Cannot describe table: " . $conn->error . "\n";
                }
            } else {
                echo "5. ERROR: Clientes table does not exist\n";
            }
        } else {
            echo "4. ERROR: Basic query failed: " . $conn->error . "\n";
        }
    } else {
        echo "3. ERROR: Connection variable not set\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}

$debug_output = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
    'debug' => $debug_output,
    'status' => 'debug_complete'
]);
?>
