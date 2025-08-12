<?php
require_once '../modelo/conexion.php';

echo "<h2>Database Structure Check</h2>";

// Check products table structure
echo "<h3>Products Table Structure:</h3>";
try {
    $result = $conn->query("DESCRIBE productos");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error checking products table: " . $e->getMessage();
}

// Check pedidos table structure
echo "<h3>Pedidos Table Structure:</h3>";
try {
    $result = $conn->query("DESCRIBE pedidos");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error checking pedidos table: " . $e->getMessage();
}

// Check detalle_pedidos table structure
echo "<h3>Detalle_Pedidos Table Structure:</h3>";
try {
    $result = $conn->query("DESCRIBE detalle_pedidos");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error checking detalle_pedidos table: " . $e->getMessage();
}

// Check for duplicate orders
echo "<h3>Checking for Duplicate Orders:</h3>";
try {
    $result = $conn->query("
        SELECT numero_orden, COUNT(*) as count 
        FROM pedidos 
        GROUP BY numero_orden 
        HAVING COUNT(*) > 1
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Order Number</th><th>Duplicate Count</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['numero_orden']}</td>";
            echo "<td>{$row['count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No duplicate orders found.</p>";
    }
} catch (Exception $e) {
    echo "Error checking for duplicates: " . $e->getMessage();
}

// Sample data from pedidos
echo "<h3>Sample Pedidos Data (First 5 rows):</h3>";
try {
    $result = $conn->query("SELECT * FROM pedidos LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        $first = true;
        while ($row = $result->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No pedidos data found.</p>";
    }
} catch (Exception $e) {
    echo "Error getting pedidos data: " . $e->getMessage();
}
?>
