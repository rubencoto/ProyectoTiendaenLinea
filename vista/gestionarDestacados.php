<?php
session_start();
require_once '../modelo/conexion.php';

// Redirect vendors to their panel - this feature is now admin-only
if (isset($_SESSION['id'])) {
    header('Location: inicioVendedor.php');
    exit;
}

// If no admin session, redirect to login
header('Location: loginVendedor.php');
exit;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos Destacados - Solo Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="alert alert-warning">
            <h4>Acceso Restringido</h4>
            <p>La gestión de productos destacados ahora es únicamente para administradores.</p>
            <p>Los productos destacados se administran directamente desde la base de datos.</p>
            <a href="inicioVendedor.php" class="btn btn-primary">Volver al Panel de Vendedor</a>
        </div>
    </div>
</body>
</html>
