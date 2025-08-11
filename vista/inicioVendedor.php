<?php
session_start(); // Iniciar sesión
require_once '../modelo/config.php';
require_once '../modelo/conexion.php';

// 🚫 Verificar si hay sesión activa del vendedor
if (!isset($_SESSION['id'])) {
    header('Location: ' . AppConfig::vistaUrl('loginVendedor.php'));
    exit;
}

// Obtener información del vendedor
$vendedor_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT nombre_empresa FROM vendedores WHERE id = ?");
$stmt->execute([$vendedor_id]);
$vendedor = $stmt->fetch();

$nombre_empresa = $vendedor ? $vendedor['nombre_empresa'] : 'Vendedor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Vendedor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f2f2f2;
        }

        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .container {
            padding: 20px;
        }

        .opciones {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.03);
        }

        .card h3 {
            margin: 10px 0;
        }

        .card a {
            text-decoration: none;
            color: #007185;
            font-weight: bold;
        }

        .card a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Bienvenido, <?= htmlspecialchars($nombre_empresa) ?></h1>
    </div>

    <div class="container">
        <div class="opciones">
            <div class="card">
                <h3>Agregar Producto</h3>
                <a href="<?= AppConfig::vistaUrl('agregarproducto.php') ?>">Ir al formulario</a>
            </div>
            <div class="card">
                <h3>Ver Mis Productos</h3>
                <a href="<?= AppConfig::vistaUrl('productos.php') ?>">Administrar</a>
            </div>
            <div class="card">
                <h3>Gestión de Pedidos</h3>
                <a href="<?= AppConfig::vistaUrl('gestionPedidos.php') ?>">Ver Pedidos</a>
            </div>
            <div class="card">
                <h3>Mi Perfil</h3>
                <a href="<?= AppConfig::vistaUrl('perfilVendedor.php') ?>">Editar Perfil</a>
            </div>
            <div class="card">
                <h3>Cerrar Sesión</h3>
                <a href="logout.php">Salir</a>
            </div>
        </div>
    </div>

    <!-- Script general JS -->
    <script src="../app.js"></script>
    
</body>
</html>
