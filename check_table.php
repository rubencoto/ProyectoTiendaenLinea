<?php
require_once 'modelo/conexion.php';

try {
    $result = $conn->query('DESCRIBE clientes');
    if ($result) {
        echo "Columns in clientes table:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
