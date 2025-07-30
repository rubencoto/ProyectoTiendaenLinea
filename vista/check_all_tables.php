<?php
require_once '../modelo/conexion.php';

$output = [];
$output[] = "=== Current Database Structure ===";

try {
    // Get all tables
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $output[] = "Tables found: " . implode(', ', $tables);
        
        // For each table, get its structure
        foreach ($tables as $table) {
            $output[] = "\n=== Table: $table ===";
            $result = $conn->query("DESCRIBE $table");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = "- " . $row['Field'] . " (" . $row['Type'] . ")";
                }
            }
        }
    } else {
        $output[] = "Error getting tables: " . $conn->error;
    }
    
} catch (Exception $e) {
    $output[] = "Error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'completed',
    'output' => $output
], JSON_UNESCAPED_UNICODE);
?>
