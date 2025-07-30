<?php
require_once '../modelo/conexion.php';

try {
    $result = $conn->query('DESCRIBE clientes');
    if ($result) {
        echo "Current clientes table columns:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ") " . 
                 ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($row['Default'] !== null ? ' DEFAULT ' . $row['Default'] : '') . "\n";
        }
    } else {
        echo "Error: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
