<?php
require_once '../modelo/conexion.php';

// Check current unverified accounts
echo "<h2>🔍 Checking Unverified Accounts</h2>";
$stmt = $conn->prepare("SELECT id, nombre, correo, verificado, fecha_registro FROM clientes WHERE verificado = 0 ORDER BY fecha_registro DESC");
$stmt->execute();
$unverified = $stmt->get_result();

if ($unverified->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Nombre</th><th>Correo</th><th>Verificado</th><th>Fecha Registro</th><th>Acciones</th></tr>";
    
    while ($row = $unverified->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($row['correo']) . "</td>";
        echo "<td>" . ($row['verificado'] ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td>" . $row['fecha_registro'] . "</td>";
        echo "<td>";
        echo "<a href='?delete_id=" . $row['id'] . "' style='color: red; margin-right: 10px;' onclick='return confirm(\"¿Estás seguro de eliminar este usuario?\")'>🗑️ Eliminar</a>";
        echo "<a href='?verify_id=" . $row['id'] . "' style='color: green;'>✅ Verificar</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ No hay cuentas sin verificar</p>";
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ Usuario con ID $delete_id eliminado exitosamente";
        echo "</div>";
        echo "<script>setTimeout(function(){ window.location.href = 'manage_database.php'; }, 2000);</script>";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "❌ Error al eliminar el usuario: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
}

// Handle verify action
if (isset($_GET['verify_id'])) {
    $verify_id = intval($_GET['verify_id']);
    $stmt = $conn->prepare("UPDATE clientes SET verificado = 1 WHERE id = ?");
    $stmt->bind_param("i", $verify_id);
    
    if ($stmt->execute()) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ Usuario con ID $verify_id verificado exitosamente";
        echo "</div>";
        echo "<script>setTimeout(function(){ window.location.href = 'manage_database.php'; }, 2000);</script>";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "❌ Error al verificar el usuario: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
}

// Action to delete all unverified accounts older than 1 hour
echo "<hr>";
echo "<h3>🧹 Acciones de Limpieza</h3>";
echo "<a href='?cleanup_old=1' style='background-color: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;' onclick='return confirm(\"¿Estás seguro de eliminar todas las cuentas no verificadas más antiguas de 1 hora?\")'>🗑️ Limpiar Cuentas Antiguas No Verificadas</a>";

if (isset($_GET['cleanup_old'])) {
    $stmt = $conn->prepare("DELETE FROM clientes WHERE verificado = 0 AND fecha_registro < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ Se eliminaron $deleted_count cuentas no verificadas antiguas";
        echo "</div>";
        echo "<script>setTimeout(function(){ window.location.href = 'manage_database.php'; }, 2000);</script>";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "❌ Error al eliminar cuentas: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
}

echo "<hr>";
echo "<h3>📧 Buscar por Correo</h3>";
echo "<form method='GET'>";
echo "<input type='text' name='search_email' placeholder='correo@ejemplo.com' value='" . ($_GET['search_email'] ?? '') . "' style='padding: 8px; width: 300px;'>";
echo "<button type='submit' style='padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 3px; margin-left: 5px;'>🔍 Buscar</button>";
echo "</form>";

if (isset($_GET['search_email']) && !empty($_GET['search_email'])) {
    $search_email = $_GET['search_email'];
    $stmt = $conn->prepare("SELECT id, nombre, correo, verificado, fecha_registro FROM clientes WHERE correo LIKE ?");
    $search_param = "%" . $search_email . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $results = $stmt->get_result();
    
    if ($results->num_rows > 0) {
        echo "<h4>Resultados para: " . htmlspecialchars($search_email) . "</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Nombre</th><th>Correo</th><th>Verificado</th><th>Fecha Registro</th><th>Acciones</th></tr>";
        
        while ($row = $results->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['correo']) . "</td>";
            echo "<td>" . ($row['verificado'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "<td>" . $row['fecha_registro'] . "</td>";
            echo "<td>";
            echo "<a href='?delete_id=" . $row['id'] . "' style='color: red; margin-right: 10px;' onclick='return confirm(\"¿Estás seguro de eliminar este usuario?\")'>🗑️ Eliminar</a>";
            if (!$row['verificado']) {
                echo "<a href='?verify_id=" . $row['id'] . "' style='color: green;'>✅ Verificar</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No se encontraron usuarios con ese correo</p>";
    }
    $stmt->close();
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 15px 0; }
th, td { padding: 8px; text-align: left; }
th { font-weight: bold; }
</style>

<script>
// Auto-refresh every 30 seconds
setTimeout(function() {
    if (!window.location.search.includes('delete_id') && !window.location.search.includes('verify_id') && !window.location.search.includes('cleanup_old')) {
        window.location.reload();
    }
}, 30000);
</script>
