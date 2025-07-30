<?php
session_start();
require_once '../modelo/conexion.php';

echo "<h2>Debug: Vendor Registration Data</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";
    
    $required_fields = ['nombre', 'correo', 'contrasena', 'telefono', 'direccion1', 'categoria', 'cedula_juridica'];
    $optional_fields = ['direccion2', 'biografia', 'redes'];
    
    foreach ($required_fields as $field) {
        $value = $_POST[$field] ?? '';
        $status = empty($value) ? '❌ Missing' : '✅ OK';
        echo "<tr>";
        echo "<td><strong>$field</strong></td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    foreach ($optional_fields as $field) {
        $value = $_POST[$field] ?? '';
        $status = empty($value) ? '⚠️ Empty' : '✅ Has Value';
        echo "<tr>";
        echo "<td>$field (optional)</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check file upload
    echo "<h3>File Upload (Logo):</h3>";
    if (isset($_FILES['logo'])) {
        echo "<p><strong>File Name:</strong> " . ($_FILES['logo']['name'] ?? 'Not set') . "</p>";
        echo "<p><strong>File Size:</strong> " . ($_FILES['logo']['size'] ?? 0) . " bytes</p>";
        echo "<p><strong>File Error:</strong> " . ($_FILES['logo']['error'] ?? 'Not set') . "</p>";
        echo "<p><strong>File Type:</strong> " . ($_FILES['logo']['type'] ?? 'Not set') . "</p>";
    } else {
        echo "<p>❌ No file uploaded</p>";
    }
    
    // Check database structure
    echo "<h3>Database Structure Check:</h3>";
    $check_table = "DESCRIBE vendedores";
    $result = $conn->query($check_table);
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . ($row['Key'] ?? '') . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Error checking table structure: " . $conn->error . "</p>";
    }
    
} else {
    echo "<p>No POST data received. This page should be accessed via form submission.</p>";
}

echo "<p><a href='registroVendedor.php'>← Back to Registration Form</a></p>";
?>
